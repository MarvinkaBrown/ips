<?php
namespace IPS\Theme;
class class_core_admin_global extends \IPS\Theme\Template
{	function appmenu( $menu, $currentTab, $currentItem ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul id='acpAppList' data-controller='core.admin.core.nav'>
	<li id='elLogo'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "&", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
			<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logo.png", "core", 'admin', false );
$return .= <<<IPSCONTENT
' alt=''>
		</a>
	</li>
	
IPSCONTENT;

foreach ( $menu['tabs'] as $tab => $items ):
$return .= <<<IPSCONTENT

		<li class='
IPSCONTENT;

if ( $tab === $currentTab ):
$return .= <<<IPSCONTENT
acpAppList_active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-tab="tab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( $menu['defaults'][$tab], null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

$totalBadge = 0;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $items as $appAndModule => $item ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $item as $key => $url ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$badgeNumber = NULL; try { $badgeNumber = \IPS\Application::load( mb_substr( $appAndModule, 0, mb_strpos( $appAndModule, '_' ) ) )->acpMenuNumber( $url ); } catch( \Exception $ex ){ } 
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $badgeNumber ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$totalBadge += $badgeNumber;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<span class='acpAppList_icon'>
					<i class='fa-solid fa-
IPSCONTENT;

$val = "menutab__{$tab}_icon"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></i>
				</span>
				<span class='acpAppList__label'>
IPSCONTENT;

$val = "menutab__{$tab}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

if ( $totalBadge > 0 ):
$return .= <<<IPSCONTENT

					<span class='ipsNotification'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $totalBadge, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</a>
			<div class='acpAppList__sub'>
				<ul class=''>
					<li class='acpAppList_header'>
IPSCONTENT;

$val = "menutab__{$tab}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

$currentApp = \IPS\Widget\Request::i()->appKey ?? 'core';
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $tab == 'developer' ):
$return .= <<<IPSCONTENT

					    <li data-menuKey='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appAndModule, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					        <h3 data-ipsMenu id='elDevCenterMenu' class='i-cursor_pointer i-margin-bottom_2'>
					            
IPSCONTENT;

$val = "__app_{$currentApp}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa fa-solid fa-caret-down'></i>
					        </h3>
					        <ul class='ipsMenu ipsMenu_auto ipsHide' id='elDevCenterMenu_menu'>
					            
IPSCONTENT;

foreach ( \IPS\Application::applications() as $app ):
$return .= <<<IPSCONTENT

					                <li class='ipsMenu_item'>
                                        
IPSCONTENT;

$controller = (\IPS\Widget\Request::i()->app === "core" and \IPS\Widget\Request::i()->module === "developer" and \IPS\Dispatcher::i()->controller) ? "&controller=" . \IPS\Dispatcher::i()->controller : "";
$return .= <<<IPSCONTENT

					                    <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=developer{$controller}&appKey={$app->directory}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "__app_{$app->directory}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

foreach ( $items as $appAndModule => $item ):
$return .= <<<IPSCONTENT

							<li data-menuKey='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appAndModule, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							    <h3>
IPSCONTENT;

$val = "menu__{$appAndModule}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							    <ul>
									
IPSCONTENT;

foreach ( $item as $key => $url ):
$return .= <<<IPSCONTENT

										<li class='
IPSCONTENT;

if ( $appAndModule . "_" . $key === $currentItem ):
$return .= <<<IPSCONTENT
acpAppListItem_active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
											
IPSCONTENT;

$badgeNumber = NULL; try { $badgeNumber = \IPS\Application::load( mb_substr( $appAndModule, 0, mb_strpos( $appAndModule, '_' ) ) )->acpMenuNumber( $url ); } catch( \Exception $ex ){ } 
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $badgeNumber ):
$return .= <<<IPSCONTENT

												<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( $url, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
												    
IPSCONTENT;

if ( isset( $menu['badges'][ $appAndModule . "_" . $key] ) ):
$return .= <<<IPSCONTENT
<span class="ipsBadge ipsBadge--icon ipsBadge--small ipsBadge--style1">{$menu['badges'][ $appAndModule . "_" . $key]}</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$val = "menu__{$appAndModule}_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

													<span class='ipsNotification'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badgeNumber, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
												</a>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$customLink = NULL; try { $customLink = \IPS\Dispatcher::i()->acpMenuCustom( $url ); } catch( \Exception $ex ){ } 
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( $customLink ):
$return .= <<<IPSCONTENT

													{$customLink}
												
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

													<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( $url, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
													    
IPSCONTENT;

if ( isset( $menu['badges'][ $appAndModule . "_" . $key] ) ):
$return .= <<<IPSCONTENT
<span class="ipsBadge ipsBadge--icon ipsBadge--small ipsBadge--style1">{$menu['badges'][ $appAndModule . "_" . $key]}</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													    
IPSCONTENT;

$val = "menu__{$appAndModule}_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

													</a>
												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
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

	<!-- <li id='elHideMenu' class='ipsJS_show'>
		<a href='#'>
			<i class='fa-solid fa-angle-left' data-action='toggleClose'></i>
			<i class='fa-solid fa-angle-right' data-action='toggleOpen'></i>
		</a>
	</li> -->
	<li id='elReorderAppMenu'>
		<a href='#' data-action='reorder' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reorder_menu', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip><i class="fa-solid fa-bars-staggered"></i></a>
		<a href='#' data-action='saveOrder' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save_reorder_menu', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip class='ipsButton ipsButton--primary ipsButton--icon ipsButton--small ipsHide'><i class='fa-solid fa-check'></i></a>
	</li>
</ul>
IPSCONTENT;

		return $return;
}

	function blankTemplate( $html ) {
		$return = '';
		$return .= <<<IPSCONTENT

<meta charset="utf-8">

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT

{$html}
IPSCONTENT;

		return $return;
}

	function block( $title, $content, $margins=TRUE, $class='', $id=NULL, $showTitle=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $title and $showTitle ):
$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div data-template='block' class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $id !== NULL ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	{$content}
</div>
IPSCONTENT;

		return $return;
}

	function breadcrumb(  ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( \IPS\Output::i()->breadcrumb ) ):
$return .= <<<IPSCONTENT

	<nav class='ipsBreadcrumb ipsSubNavigation'>
		<ul id='breadcrumb'>
			
IPSCONTENT;

$i = 0;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( \IPS\Output::i()->breadcrumb as $k => $b ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

if ( $b[0] === NULL ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$val = "{$b[1]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $b[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							<span>
IPSCONTENT;

$val = "{$b[1]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( ( $i + 1 != \count( \IPS\Output::i()->breadcrumb ) ) ):
$return .= <<<IPSCONTENT
<i class='fa-solid fa-angle-right'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
						</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
				
IPSCONTENT;

$i++;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</nav>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function buttons( $buttons ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsButtons ipsButtons--main ipsButtons--admin'>
	
IPSCONTENT;

foreach ( $buttons as $button ):
$return .= <<<IPSCONTENT

		<li class='
IPSCONTENT;

if ( isset( $button['hidden'] ) and $button['hidden'] ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( isset( $button['id'] ) ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<a
				
IPSCONTENT;

if ( isset( $button['link'] ) ):
$return .= <<<IPSCONTENT
href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				title='
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
				class='ipsButton ipsButton--secondary 
IPSCONTENT;

if ( isset( $button['class'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
				role="button"
				
IPSCONTENT;

if ( isset( $button['id'] ) ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_button"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['target'] ) ):
$return .= <<<IPSCONTENT
target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['target'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

if ( $button['target'] == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['data'] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $button['data'] as $k => $v ):
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

if ( isset( $button['hotkey'] ) ):
$return .= <<<IPSCONTENT

					data-keyAction='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['hotkey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			>
				
IPSCONTENT;

if ( $button['icon'] ):
$return .= <<<IPSCONTENT

					<i class='
IPSCONTENT;

if ( ! stristr( $button['icon'], 'regular' ) ):
$return .= <<<IPSCONTENT
fa-solid 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( isset($button['dropdown']) ):
$return .= <<<IPSCONTENT

					<i class='fa-solid fa-caret-down'></i>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</a>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function controlStrip( $buttons, $otherClasses=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsControlStrip 
IPSCONTENT;

if ( $otherClasses ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $otherClasses, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsControlStrip>
	
IPSCONTENT;

foreach ( $buttons as $k => $button ):
$return .= <<<IPSCONTENT

		<li class='ipsControlStrip_button 
IPSCONTENT;

if ( isset( $button['hidden'] ) and $button['hidden'] ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( isset( $button['id'] ) ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<a
				
IPSCONTENT;

if ( isset( $button['link'] ) and $button['link'] !== NULL ):
$return .= <<<IPSCONTENT
href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				title='
IPSCONTENT;

if ( isset( $button['tooltip'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['tooltip'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
				data-ipsTooltip
				aria-label="
IPSCONTENT;

if ( isset( $button['tooltip'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['tooltip'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
				data-controlStrip-action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
				
				
IPSCONTENT;

if ( isset( $button['class'] ) ):
$return .= <<<IPSCONTENT
class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['data'] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $button['data'] as $k => $v ):
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

if ( isset( $button['hotkey'] ) ):
$return .= <<<IPSCONTENT

					data-keyAction='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['hotkey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['target'] ) ):
$return .= <<<IPSCONTENT

					target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['target'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
					
IPSCONTENT;

if ( $button['target'] == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			>
			    <i class='ipsControlStrip_icon fa-fw 
IPSCONTENT;

if ( ! stristr( $button['icon'], 'regular' ) ):
$return .= <<<IPSCONTENT
fa-solid 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( mb_substr( $button['icon'], 0, 3 ) !== 'fa-' ):
$return .= <<<IPSCONTENT
fa-
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
				<span class='ipsControlStrip_item'>
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</a>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>

IPSCONTENT;

		return $return;
}

	function definitionTable( $rows, $sidebar=NULL, $parse=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $sidebar !== NULL ):
$return .= <<<IPSCONTENT

	<div class="ipsColumns">
		<div class="ipsColumns__primary">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<i-data>
				<ul class="ipsData ipsData--table ipsData--compact ipsData--definition-table">
					
IPSCONTENT;

foreach ( $rows as $k => $v ):
$return .= <<<IPSCONTENT

						<li class="ipsData__item">
							<div class="ipsData__content">
								<div class="i-basis_180">
									<strong class="ipsData__title">
										
IPSCONTENT;

if ( $parse ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</strong>
								</div>
								<div class="">
									
IPSCONTENT;

if ( $v ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unknown', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>

IPSCONTENT;

if ( $sidebar !== NULL ):
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsColumns__secondary i-basis_360">
			{$sidebar}
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function error( $title, $message, $code, $extra, $member ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $code === '1X000/X' and \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "cloud", 'admin' )->featureDisabled( 'cloud_service_level_upgrade_needed' );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <div class='ipsMessage ipsMessage--error'>
        <span class="ipsMessage_code">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
        {$message}
    </div>

    <div class="i-padding_3">
        
IPSCONTENT;

if ( ( \IPS\IN_DEV or $member->isAdmin() ) and $extra ):
$return .= <<<IPSCONTENT

            <h3 class="i-font-weight_500 i-color_hard i-margin-bottom_2 i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_technical_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
            <textarea class="ipsInput ipsInput--text" rows="13" style="font-family: monospace;">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $extra, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
            <p class="i-color_soft i-text-align_center i-margin-top_2">
                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_technical_details_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'support', 'system_logs_view' ) ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_technical_details_logs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </p>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'support', 'get_support' ) ):
$return .= <<<IPSCONTENT

            <div class="i-text-align_center i-padding-block_2">
                <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_support', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--inherit">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_support', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
            </div>
        
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

	function genericLink( $url, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function globalTemplate( $title,$html,$location=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset(\IPS\Widget\Request::i()->cookie['acptheme']) AND \IPS\Widget\Request::i()->cookie['acptheme'] == 'dark' ):
$return .= <<<IPSCONTENT
data-ips-scheme="dark"
IPSCONTENT;

elseif ( !isset(\IPS\Widget\Request::i()->cookie['acptheme']) AND isset(\IPS\Widget\Request::i()->cookie['acpthemedefault']) AND \IPS\Widget\Request::i()->cookie['acpthemedefault'] == 'dark' ):
$return .= <<<IPSCONTENT
data-ips-scheme="dark"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-scheme="light"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<head>
		<meta charset="utf-8">
		<title>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</title>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->favico(  );
$return .= <<<IPSCONTENT

		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="referrer" content="origin-when-cross-origin">
	</head>
	<body data-baseurl='
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
' class='ipsApp ipsApp_admin 
IPSCONTENT;

if ( ( \IPS\IN_DEV ) AND !\IPS\DEV_HIDE_DEV_TOOLS ):
$return .= <<<IPSCONTENT
cAdminDevModeOn
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->cookie['hideAdminMenu'] ) ):
$return .= <<<IPSCONTENT
cAdminHideMenu
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( \IPS\Output::i()->bodyClasses as $class ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' data-controller="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', \IPS\Output::i()->globalControllers ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( \IPS\Output::i()->inlineMessage ) ):
$return .= <<<IPSCONTENT
data-message="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->inlineMessage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-pageApp='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location['app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-pageLocation='admin' data-pageModule='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location['module'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-pageController='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location['controller'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->id ) ):
$return .= <<<IPSCONTENT
data-pageID='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( (int) \IPS\Widget\Request::i()->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		
IPSCONTENT;

if ( ( \IPS\IN_DEV ) AND !\IPS\DEV_HIDE_DEV_TOOLS ):
$return .= <<<IPSCONTENT

			<a class='cAdminDevModeWarning' data-ipsDialog data-ipsDialog-content='#elDevModeDialog' data-ipsDialog-size='narrow' data-ipsDialog-title="
IPSCONTENT;

if ( \IPS\IN_DEV ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_indev_on_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

if ( \IPS\IN_DEV ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_indev_on', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-triangle-exclamation'></i></a>
			<!-- <div class='cAdminDevModeBar'></div> -->
			<div id='elDevModeDialog' class='ipsHide i-padding_3'>
				<p class='i-margin-bottom_1'>
					
IPSCONTENT;

if ( \IPS\IN_DEV ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_indev_on_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
				<p>
					<i class='fa-solid fa-triangle-exclamation'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_not_production_tho', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</p>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div id='acpMainLayout'>
			<nav id='acpAppMenu'>
				
IPSCONTENT;

if ( isset( \IPS\Output::i()->sidebar['appmenu'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Output::i()->sidebar['appmenu'];
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</nav>
			<div id='acpMainArea'>

				<div id='ipsLayout_header' role='banner' data-controller='core.admin.core.mobileNav'>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'livesearch', 'livesearch_manage') ):
$return .= <<<IPSCONTENT

					<div class='acpSearch'>
						<i class='fa-solid fa-magnifying-glass'></i>
						<input type='text' class='' id='acpSearchKeyword' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_admincp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
					</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<ul id='elAdminControls'>
						<li class='ipsResponsive_showDesktop'>
							<a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Http\Url::baseUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel="noopener">
								<i class='fa-solid fa-house'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'site', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</a>
						</li>
						
IPSCONTENT;

if ( isset( \IPS\Output::i()->sidebar['quickLinks'] ) ):
$return .= <<<IPSCONTENT

						<li class='ipsResponsive_showDesktop'>
						    <a href='#elAcpTools_menu' id='elAcpTools' data-ipsMenu data-ipsMenu-appendTo='#elAdminControls'>
						        <i class='fa-solid fa-link'></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_quick_links', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<i class='fa-solid fa-angle-down'></i>
						    </a>
						    <ul class='ipsMenu ipsMenu_auto ipsHide' id='elAcpTools_menu'>
						        
IPSCONTENT;

foreach ( \IPS\Output::i()->sidebar['quickLinks'] as $link ):
$return .= <<<IPSCONTENT

						        	{$link}
						        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						    </ul>
						</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\Dispatcher\Admin::showSwitchLink() ):
$return .= <<<IPSCONTENT

							<li class='ipsResponsive_showDesktop'>
								<span class="acpHighlightLink_wrap">
									<a href='https://invisioncommunity.com/services/switch-to-invision/' target='_blank' rel='external noopener nofollow' class='acpHighlightLink'>
										Switch to Cloud
									</a>
									<a class="acpHighlightLink_close" data-ipsToolTip title='Hide this for a while' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=dashboard&do=switchSnooze", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-xmark"></i></a>
								</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$notificationsCount = \IPS\core\AdminNotification::notificationCount();
$return .= <<<IPSCONTENT

						<li class="cAcpNotifications 
IPSCONTENT;

if ( $notificationsCount ):
$return .= <<<IPSCONTENT
cAcpNotifications_active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" id="elFullAcpNotifications" data-ipsMenu data-ipsMenu-closeOnClick="false" data-controller="core.admin.core.notificationMenu">
							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=notifications", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
								<i class="fa-solid fa-bell" data-role="notificationIcon"></i>
								<span class="ipsNotification 
IPSCONTENT;

if ( !$notificationsCount ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="notificationCount">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $notificationsCount );
$return .= <<<IPSCONTENT
</span>
							</a>
							<div id='elFullAcpNotifications_menu' class='ipsMenu ipsMenu_wide ipsHide'>
								<div class='ipsMenu_headerBar'>
									<h4 class='ipsTitle ipsTitle--h4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
									<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=notifications&do=settings", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-color_soft"><i class="fa-solid fa-gear"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</div>
								<div class='ipsMenu_innerContent'>
									<i-data>
										<ol class="ipsData ipsData--table ipsData--notification-list" data-role="notificationList"></ol>
									</i-data>
								</div>
								<div class='ipsMenu_footerBar i-text-align_center'>
									<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=notifications", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-bars'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_all_notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</div>
							</div>
						</li>
						<li id='elAdminUser' class='ipsCursor_pointer ipsResponsive_showDesktop' data-ipsMenu data-controller='core.admin.core.changeTheme'>
							<span class='ipsUserPhoto ipsUserPhoto--tiny'><img src='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt=''></span>
							<i class='fa-solid fa-angle-down'></i>
						</li>
						<li id='elAdminNav' class='ipsResponsive_hideDesktop' data-ipsDrawer data-ipsDrawer-drawerelem="#elMobileDrawer" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'admincp_menu', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
							<a href="#"><i class="fa-solid fa-bars"></i></a>
						</li>
					</ul>
				</div>

				
IPSCONTENT;

if ( !\in_array( \IPS\Widget\Request::i()->controller, array( 'notifications', 'upgrade' ) ) ):
$return .= <<<IPSCONTENT

					<div data-controller="core.global.core.notificationList" class="cNotificationList i-grid i-gap_1">
						
IPSCONTENT;

foreach ( \IPS\core\AdminNotification::notifications( NULL, array( \IPS\core\AdminNotification::SEVERITY_HIGH, \IPS\core\AdminNotification::SEVERITY_CRITICAL ) ) as $notification ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$style = $notification->style();
$return .= <<<IPSCONTENT

							<div class="ipsMessage ipsMessage--acp ipsMessage--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $style, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 cAcpNotificationBanner">
								<i class='fa-solid fa-
IPSCONTENT;

if ( $style == $notification::STYLE_INFORMATION OR $style == $notification::STYLE_EXPIRE ):
$return .= <<<IPSCONTENT
info-circle
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsMessage__icon'></i>
								<div class="i-flex i-justify-content_space-between i-gap_2">
									<div class="i-flex_11">
										<h3 class="ipsMessage__title">{$notification->title()}</h3>
										<div class='i-font-size_-2 i-font-weight_normal'>{$notification->body()}</div>
									</div>
									
IPSCONTENT;

$dismissible = $notification->dismissible();
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $dismissible !== $notification::DISMISSIBLE_NO ):
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=notifications&do=dismiss&id={$notification->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsMessage__close i-flex_00" title='
IPSCONTENT;

$val = "acp_notification_dismiss_{$dismissible}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-action="dismiss">
											<i class="fa-solid fa-times"></i>
										</a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</div>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !\in_array( 'acpHeader', \IPS\Output::i()->hiddenElements) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( \IPS\Output::i()->customHeader ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Output::i()->customHeader;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div id='acpPageHeader'>
							
IPSCONTENT;

if ( isset( \IPS\Output::i()->headerMessage ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Output::i()->headerMessage;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'admin' )->breadcrumb(  );
$return .= <<<IPSCONTENT

							<div class='acpPageHeader_flex'>
								
IPSCONTENT;

if ( \IPS\Output::i()->showTitle ):
$return .= <<<IPSCONTENT

									<h1 class='ipsTitle ipsTitle--h3'>
										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( \IPS\Output::i()->editUrl ):
$return .= <<<IPSCONTENT

											<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->editUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-font-size_1" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-pencil"></i></a>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( \IPS\IN_DEV ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->searchKeywords( 'app=' . \IPS\Request::i()->app . '&module=' . \IPS\Request::i()->module . '&controller=' . \IPS\Request::i()->controller . ( ( isset( \IPS\Request::i()->do ) and \IPS\Request::i()->do != 'do' ) ? ( '&do=' . \IPS\Request::i()->do ) : '' ) . ( ( \IPS\Request::i()->controller == 'enhancements' and ( isset( \IPS\Request::i()->id ) ) ) ? ( '&id=' . \IPS\Request::i()->id ) : '' ) );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</h1>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


								
IPSCONTENT;

if ( isset(\IPS\Output::i()->sidebar['actions']) ):
$return .= <<<IPSCONTENT

									<div class='acpToolbar'>
										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'admin' )->pageButtons( \IPS\Output::i()->sidebar['actions'] );
$return .= <<<IPSCONTENT

									</div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div id='acpContent'>
					
IPSCONTENT;

if ( !\IPS\Output::i()->responsive ):
$return .= <<<IPSCONTENT

						<div class='ipsResponsive_showPhone i-text-align_center ipsBox ipsBox--padding'>
							<i class='ipsLargeIcon fa-solid fa-tablet-screen-button'></i>
							<h2 class='ipsTitle ipsTitle--h4 i-margin-top_2 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'not_mobile', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
							<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'not_mobile_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						</div>
						<div class='ipsResponsive_hidePhone'>
							{$html}
						</div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						{$html}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
		
		<div id='acpLiveSearchResults' class='ipsHide' data-controller='core.admin.core.liveSearch'>
			<div class='cAcpSearch'>
				<div class='cAcpSearch_areas' data-role="searchMenu">
					<div data-ipsSideMenu data-ipsSideMenu-type='radio'>
						<ul class="ipsSideMenu_list">
							
IPSCONTENT;

foreach ( \IPS\Application::allExtensions( 'core', 'LiveSearch', TRUE, 'core', 'Settings' ) as $key => $extension ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $extension->hasAccess() ):
$return .= <<<IPSCONTENT

									<li><a href="#" class="ipsSideMenu_item ipsSideMenu_itemDisabled" data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( method_exists( $extension, 'isDefault' ) and $extension->isDefault() ):
$return .= <<<IPSCONTENT
data-role="defaultTab"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "acp_search_title_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span data-role="resultCount" class='ipsSideMenu_itemCount ipsLoading ipsLoading--tiny'>&nbsp;&nbsp;</span></a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</div>
				<div class='cAcpSearch_results i-padding_3 ipsScrollbar' data-role="searchResults"></div>
			</div>
		</div>
		<ul id='elAdminUser_menu' class='ipsMenu ipsMenu_narrow ipsHide'>
			<li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=adminDetails", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-url='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=adminDetails", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-size='medium'><i class='fa-solid fa-pencil'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li class='ipsMenu_item ipsMenu_subItems elThemeMenu-wrap'>
				<a href='#elThemeMenu_menu' id='elThemeMenu'>
					<i class='fa-solid fa-paintbrush'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</a>
				<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elThemeMenu_menu">
					<li class="elThemeMenu__auto ipsCursor_pointer ipsMenu_item 
IPSCONTENT;

if ( !isset(\IPS\Widget\Request::i()->cookie['acptheme']) OR !\IPS\Widget\Request::i()->cookie['acptheme'] ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="os">
						<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acptheme_os_preference', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
					<li class="ipsCursor_pointer ipsMenu_item 
IPSCONTENT;

if ( isset(\IPS\Widget\Request::i()->cookie['acptheme']) AND \IPS\Widget\Request::i()->cookie['acptheme'] == 'light' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="light">
						<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acptheme_light', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
					<li class="ipsCursor_pointer ipsMenu_item 
IPSCONTENT;

if ( isset(\IPS\Widget\Request::i()->cookie['acptheme']) AND \IPS\Widget\Request::i()->cookie['acptheme'] == 'dark' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="dark">
						<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acptheme_dark', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				</ul>
			</li>
			
IPSCONTENT;

$languages = \IPS\Lang::roots();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $languages ) > 1 ):
$return .= <<<IPSCONTENT

				<li class='ipsMenu_item ipsMenu_subItems'>
					<a href='#elNavLang_menu' id='elNavLang'><i class='fa-solid fa-earth-americas'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_language', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<ul id='elNavLang_menu' class='ipsMenu ipsMenu_selectable ipsHide'>
					
IPSCONTENT;

foreach ( $languages as $id => $lang  ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item
IPSCONTENT;

if ( \IPS\Member::loggedIn()->acp_language == $id || ( $lang->default && \IPS\Member::loggedIn()->acp_language === 0 ) ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=language&id={$id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 )->addRef(\IPS\Request::i()->url()), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $lang->get__icon() ):
$return .= <<<IPSCONTENT
<i class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->get__icon(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $lang->default ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'default', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
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

			<li class='ipsMenu_sep'><hr></li>
			<li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login&do=logout" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-power-off'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		</ul>
		
IPSCONTENT;

if ( isset( \IPS\Output::i()->sidebar['mobilenav'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Output::i()->sidebar['mobilenav'];
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</body>
</html>
IPSCONTENT;

		return $return;
}

	function headerBar( $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h2 class='ipsTitle i-padding_2 i-background_dark'>
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
IPSCONTENT;

		return $return;
}

	function message( $message, $type, $debug=NULL, $parse=TRUE, $pad=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $pad ):
$return .= <<<IPSCONTENT
<div class="i-padding_2">
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $debug !== NULL ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

if ( $parse ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			{$message}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<br><br>
		<pre>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $debug, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

if ( $parse ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			{$message}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $pad ):
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function mobileNavigation( $menu, $currentTab ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elMobileDrawer' class='ipsDrawer ipsHide'>
	<div class='ipsDrawer_menu'>
		<div class='ipsDrawer_content'>
			<ul id='elUserNav_mobile'>
				<li><a href='../'><i class='fa-solid fa-house'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'site', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=adminDetails", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'> <i class='fa-solid fa-pencil'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login&do=logout" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-power-off'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			</ul>
			<ul class='ipsDrawer_list'>
				
IPSCONTENT;

foreach ( $menu['tabs'] as $tab => $items ):
$return .= <<<IPSCONTENT

					<li class='ipsDrawer_itemParent'>
						<h4 class='ipsDrawer_title'>
							<span class='acpAppList_icon'><i class='fa-solid fa-
IPSCONTENT;

$val = "menutab__{$tab}_icon"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></i></span>
							<span class='acpAppList_app'>
IPSCONTENT;

$val = "menutab__{$tab}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</h4>
						<ul class='ipsDrawer_list'>
							<li data-action="back"><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mobile_menu_back', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

foreach ( $items as $appAndModule => $item ):
$return .= <<<IPSCONTENT

								<li class='ipsDrawer_listTitle'>
									
IPSCONTENT;

$val = "menu__{$appAndModule}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</li>
								
IPSCONTENT;

foreach ( $item as $key => $url ):
$return .= <<<IPSCONTENT

									<li>
										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( $url, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "menu__{$appAndModule}_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		
		
IPSCONTENT;

$languages = \IPS\Lang::getEnabledLanguages();
$return .= <<<IPSCONTENT

		<ul class='ipsList ipsList--inline i-text-align_center i-padding_2 cMobileNav_utilities' id='elThemeLangMenuMob'>
			<li><a href='#' data-ipsMenu id='elNavThemeMob'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'skin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-angle-up'></i></a></li>

			
IPSCONTENT;

if ( \count( $languages ) > 1 ):
$return .= <<<IPSCONTENT

				<li><a href='#' data-ipsMenu id='elNavLangMob'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'language', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-angle-up'></i></a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>

		
IPSCONTENT;

if ( \count( $languages ) > 1 ):
$return .= <<<IPSCONTENT

			<ul id='elNavLangMob_menu' class='ipsMenu ipsMenu_selectable ipsHide'>
			
IPSCONTENT;

foreach ( $languages as $id => $lang  ):
$return .= <<<IPSCONTENT

				<li class='ipsMenu_item
IPSCONTENT;

if ( \IPS\Member::loggedIn()->acp_language == $id || ( $lang->default && \IPS\Member::loggedIn()->acp_language === 0 ) ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=language&id=$id" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $lang->get__icon() ):
$return .= <<<IPSCONTENT
<i class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->get__icon(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $lang->default ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'default', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
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


		<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elNavThemeMob_menu">
			<li class="elThemeMenu__auto ipsCursor_pointer ipsMenu_item 
IPSCONTENT;

if ( !isset(\IPS\Widget\Request::i()->cookie['acptheme']) OR !\IPS\Widget\Request::i()->cookie['acptheme'] ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="os">
				<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acptheme_os_preference', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</li>
			<li class="ipsCursor_pointer ipsMenu_item 
IPSCONTENT;

if ( isset(\IPS\Widget\Request::i()->cookie['acptheme']) AND \IPS\Widget\Request::i()->cookie['acptheme'] == 'light' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="light">
				<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acptheme_light', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</li>
			<li class="ipsCursor_pointer ipsMenu_item 
IPSCONTENT;

if ( isset(\IPS\Widget\Request::i()->cookie['acptheme']) AND \IPS\Widget\Request::i()->cookie['acptheme'] == 'dark' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="dark">
				<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acptheme_dark', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</li>
		</ul>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function nodeMoveDeleteContent( $url, $itemLang, $number, $destination ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-background_1">
	<p class="i-margin-bottom_1">
		
IPSCONTENT;

if ( $destination ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$sprintf = array($number, $itemLang, $destination->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_mass_content_move_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$sprintf = array($number, $itemLang); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_mass_content_delete_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>
	<p class="i-margin-bottom_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_mass_content_blurb_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class="ipsSubmitRow">
	<a class="ipsButton ipsButton--primary" 
IPSCONTENT;

if ( $number ):
$return .= <<<IPSCONTENT
href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'confirm', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_mass_content_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function paddedBlock( $content ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_2'>
	{$content}
</div>
IPSCONTENT;

		return $return;
}

	function pageButtons( $buttons ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $buttons ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

usort( $buttons, function ($a, $b ){ if( isset( $a['primary'] ) && $a['primary'] ){ return 1; }else{ return 0;} } );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$hasSecondary = false;
$return .= <<<IPSCONTENT

	<ul class="ipsButtons ipsButtons--main">
		
IPSCONTENT;

foreach ( $buttons as $action ):
$return .= <<<IPSCONTENT

			<li 
IPSCONTENT;

if ( isset( $action['primary'] ) && $action['primary'] ):
$return .= <<<IPSCONTENT
class='acpToolbar_primary'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

if ( !( isset( $action['primary'] ) && $action['primary'] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$hasSecondary = true;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<a
					
IPSCONTENT;

if ( isset( $action['link'] ) ):
$return .= <<<IPSCONTENT

						href='
IPSCONTENT;

if ( mb_substr( $action['link'], 0, 1 ) === '#' or preg_match( '/^[a-z]{3,5}:\/\/.*$/', $action['link'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "{$action['link']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						href='#'
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $action['id'] ) ):
$return .= <<<IPSCONTENT

						id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $action['menu'] ) ):
$return .= <<<IPSCONTENT

						data-ipsMenu
						
IPSCONTENT;

if ( !isset( $action['id'] ) ):
$return .= <<<IPSCONTENT

							id='
IPSCONTENT;

$id = md5( mt_rand() );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					class='ipsButton 
IPSCONTENT;

if ( isset( $action['primary'] ) && $action['primary'] ):
$return .= <<<IPSCONTENT
ipsButton--primary
IPSCONTENT;

elseif ( isset( $action['color'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action['color'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsButton--secondary
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $action['class'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
					
IPSCONTENT;

if ( isset( $action['target'] ) ):
$return .= <<<IPSCONTENT
target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action['target'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

if ( $action['target'] == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $action['tooltip'] ) ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action['tooltip'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $action['data'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $action['data'] as $k => $v ):
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

				>
					<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> <span data-role="title">
IPSCONTENT;

$val = "{$action['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

if ( isset( $action['menu'] ) || isset( $action['dropdown'] ) ):
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</a>
				
IPSCONTENT;

if ( isset( $action['menu'] ) ):
$return .= <<<IPSCONTENT

					<div id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsMenu 
IPSCONTENT;

if ( isset( $action['menuClass'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action['menuClass'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsHide'>
						<ul>
							
IPSCONTENT;

foreach ( $action['menu'] as $item ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( isset( $item['hr'] ) and $item['hr'] ):
$return .= <<<IPSCONTENT

									<li class='ipsMenu_sep'><hr></li>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item 
IPSCONTENT;

if ( isset( $item['class'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $item['target'] ) ):
$return .= <<<IPSCONTENT
target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['target'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

if ( $item['target'] == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $item['data'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $item['data'] as $k => $v ):
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
>
											
IPSCONTENT;

if ( isset( $item['icon'] ) ):
$return .= <<<IPSCONTENT

												<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											<span data-role="title">
IPSCONTENT;

$val = "{$item['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
					</div>
				
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

IPSCONTENT;

		return $return;
}

	function searchKeywords( $url, $lang=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\IN_DEV and !\IPS\DEV_HIDE_DEV_TOOLS ):
$return .= <<<IPSCONTENT

	<span id='acplsi_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsMenu data-ipsMenu-closeOnClick='false' data-ipsMenu-appendTo='.ipsDialog > div, #acpMainLayout' class="ipsBadge cACPkeywords">
		
IPSCONTENT;

if ( isset( \IPS\Dispatcher::i()->searchKeywords[ $url ] ) ):
$return .= <<<IPSCONTENT

			<span>
				<i class="fa-solid fa-magnifying-glass"></i> 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( \IPS\Dispatcher::i()->searchKeywords[ $url ]['keywords'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</span>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<span>
				<i class="fa-solid fa-magnifying-glass"></i> 0
			</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</span>
	<div id='acplsi_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsMenu ipsMenu_wide ipsHide' data-controller="core.admin.core.acpSearchKeywords" data-url="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( base64_encode( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=searchKeywords", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
		<div class="ipsMenu_headerBar">
			<h4 class="ipsTitle ipsTitle--h4"><i class='fa-solid fa-gears i-color_soft i-margin-end_icon'></i> Developer Options</h4>
		</div>
		<div class=''>
			<ul class='ipsForm'>
				<li class='ipsFieldRow ipsFieldRow--fullWidth'>
					<div class='ipsFieldRow__label'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'keywords_language', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsFieldRow__content'>
						<input type='text' data-role="lang_key" value="
IPSCONTENT;

if ( isset( \IPS\Dispatcher::i()->searchKeywords[ $url ] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Dispatcher::i()->searchKeywords[ $url ]['lang_key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $lang ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
menu__
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->module, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->controller, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					</div>
				</li>
				<li class='ipsFieldRow ipsFieldRow--fullWidth'>
					<div class='ipsFieldRow__label'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'keywords_restriction', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsFieldRow__content'>					
						<select class="ipsInput ipsInput--select" data-role="restriction">
							
IPSCONTENT;

$value = isset( \IPS\Dispatcher::i()->searchKeywords[ $url ] ) ? \IPS\Dispatcher::i()->searchKeywords[ $url ]['restriction'] : \IPS\Dispatcher::i()->menuRestriction;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( \IPS\Dispatcher::i()->moduleRestrictions as $k => $v ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \is_array( $v ) ):
$return .= <<<IPSCONTENT

									<optgroup label="
IPSCONTENT;

$val = "r__{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
										
IPSCONTENT;

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

											<option value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $value == $_k ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "r__{$_v}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</optgroup>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<option value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $value == $k ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "{$v}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</select>
					</div>
				</li>
				<li class='ipsFieldRow ipsFieldRow--fullWidth'>
					<div class='ipsFieldRow__label'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'keywords_keywords', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsFieldRow__content'>
						<div class="ipsField_stack" data-ipsStack>
							<ul data-role="stack">
								
IPSCONTENT;

if ( isset( \IPS\Dispatcher::i()->searchKeywords[ $url ] ) ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

foreach ( \IPS\Dispatcher::i()->searchKeywords[ $url ]['keywords'] as $word ):
$return .= <<<IPSCONTENT

										<li class='ipsField_stackItem' data-role="stackItem">
											<span class="ipsField_stackDrag ipsDrag" data-action='stackDrag'>
												<i class='fa-solid fa-bars ipsDrag_dragHandle'></i>
											</span>
											<div data-ipsStack-wrapper><input type='text' data-role="keywords" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $word, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput"></div>
											<span class="ipsField_stackDelete" data-action="stackDelete">
												&times;
											</span>
										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<li class='ipsField_stackItem' data-role="stackItem">
										<span class="ipsField_stackDrag ipsDrag" data-action='stackDrag'>
											<i class='fa-solid fa-bars ipsDrag_dragHandle'></i>
										</span>
										<div data-ipsStack-wrapper><input type='text' data-role="keywords" class="ipsInput"></div>
										<span class="ipsField_stackDelete" data-action="stackDelete">
											&times;
										</span>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
							<div class='i-margin-top_2'>
								<button class="ipsField_stackAdd ipsButton ipsButton--inherit ipsButton--small" data-action="stackAdd" role="button"><i class='fa-solid fa-plus-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stack_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
							</div>
						</div>
					</div>
				</li>
			</ul>
		</div>
		<div class="ipsSubmitRow">
			<button type="button" class="ipsButton ipsButton--primary ipsButton--primary ipsButton--wide" data-action="save">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function shortMessage( $message, $classes=array(), $parse=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $classes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $parse ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
{$message}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function sidebar( $items, $activeItem ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul>
	
IPSCONTENT;

foreach ( $items as $appAndModule => $moduleItems ):
$return .= <<<IPSCONTENT

		<li class='
IPSCONTENT;

if ( \count( $moduleItems ) > 1 ):
$return .= <<<IPSCONTENT
has_sub
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $appAndModule == $activeItem ):
$return .= <<<IPSCONTENT
active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

if ( \count( $moduleItems ) > 1 ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$val = "menu__{$appAndModule}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				<ul>
					
IPSCONTENT;

foreach ( $moduleItems as $key => $url ):
$return .= <<<IPSCONTENT

						<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( $url, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "menu__{$appAndModule}_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $moduleItems as $key => $url ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( $url, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "menu__{$appAndModule}_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function tabs( $tabNames, $activeId, $defaultContent, $url, $tabParam='tab', $tabClasses='', $panelClasses='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox'>
	<i-tabs class='ipsTabs 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabClasses, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 acpFormTabBar' id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url->acpQueryString() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url->acpQueryString() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
data-ipsTabBar-updateURL='false'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<div role='tablist'>
			
IPSCONTENT;

foreach ( $tabNames as $i => $name ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;

if ( $i ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( $tabParam, $i ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url->acpQueryString() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsTabs__tab" title='
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' role="tab" aria-controls='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url->acpQueryString() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' aria-selected="
IPSCONTENT;

if ( $i == $activeId ):
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

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\IN_DEV ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->searchKeywords( $url->setQueryString( 'tab', $i )->acpQueryString(), $name );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</a>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

	</i-tabs>
	<section id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url->acpQueryString() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='acpFormTabContent'>
		
IPSCONTENT;

foreach ( $tabNames as $i => $name ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $i == $activeId ):
$return .= <<<IPSCONTENT

				<div role="tabpanel" id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url->acpQueryString() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $panelClasses, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-labelledby="ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url->acpQueryString() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $i != $activeId ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					{$defaultContent}
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</section>
</div>
IPSCONTENT;

		return $return;
}

	function teamLink( $team ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assignment_team', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span> 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'teams_edit' ) ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $team->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $team->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $team->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function updatebadge( $version, $url, $released='', $blank=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="i-link-color_inherit"
IPSCONTENT;

if ( $blank ):
$return .= <<<IPSCONTENT
target='_blank' rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipstooltip title='
IPSCONTENT;

if ( $released ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($version, $released); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_version_tip_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($version); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_version_tip', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_version_available', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function userLink( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function userLinkWithPhoto( $member, $location='admin', $size='tiny' ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, $size );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $member );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userPhoto( $member, $size='small' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $member->member_id ):
$return .= <<<IPSCONTENT

	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsUserPhoto ipsUserPhoto--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
		<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading="lazy" referrerpolicy='origin-when-cross-origin'>
	</a>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<span class="ipsUserPhoto ipsUserPhoto--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading="lazy" referrerpolicy='origin-when-cross-origin'>
	</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}