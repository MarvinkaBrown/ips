<?php
namespace IPS\Theme;
class class_core_front_menu extends \IPS\Theme\Template
{	function button( $menu ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $menu->elements as $element ):
$return .= <<<IPSCONTENT

    <a id='ipsMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->css, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

foreach ( $element->dataAttributes as $attributeKey => $attributeValue ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attributeKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attributeValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
 title='
IPSCONTENT;

$val = "{$element->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
        
IPSCONTENT;

if ( $element->icon ):
$return .= <<<IPSCONTENT
<i class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 <span>
IPSCONTENT;

$val = "{$element->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span> 
IPSCONTENT;

if ( $element->notificationIcon ):
$return .= <<<IPSCONTENT
<span class='ipsNotification'><i class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->notificationIcon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i></span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </a>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function content( $menu ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $menu->hasContent() ):
$return .= <<<IPSCONTENT

<ul id='ipsMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsMenu ipsMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->menuType, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsHide'>
	{$menu->extraHtmlBeforeLinks}
	
IPSCONTENT;

foreach ( $menu->elements as $key => $linkData ):
$return .= <<<IPSCONTENT

	{$linkData}
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	{$menu->extraHtmlAfterLinks}
</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function link( $menu ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href='#ipsMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' id='ipsMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->css, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsMenu 
IPSCONTENT;

if ( $menu->appendTo ):
$return .= <<<IPSCONTENT
 data-ipsMenu-appendTo='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->appendTo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $menu->tooltip ):
$return .= <<<IPSCONTENT
data-ipstooltip title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->tooltip, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    
IPSCONTENT;

if ( $menu->customLinkHtml ):
$return .= <<<IPSCONTENT

    {$menu->customLinkHtml}
    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	    
IPSCONTENT;

if ( $menu->icon ):
$return .= <<<IPSCONTENT

		    <i class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $menu->icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true"></i>
	    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	    <span class="ipsMenuLabel">
IPSCONTENT;

$val = "{$menu->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

if ( $menu->showCaret ):
$return .= <<<IPSCONTENT
<i class='ipsMenuCaret'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</a>
IPSCONTENT;

		return $return;
}

	function menu( $menu ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $menu->hasContent() ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( count( $menu->elements ) == 1 && $menu->shrinkToButton ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "menu", "core" )->button( $menu );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "menu", "core" )->link( $menu );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "menu", "core" )->content( $menu );
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

	function row( $element ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->css, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $element->menuItem ):
$return .= <<<IPSCONTENT
 data-menuitem="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->menuItem, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $element->wrapperDataAttributes as $attributeKey => $attributeValue ):
$return .= <<<IPSCONTENT
	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attributeKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attributeValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
>
	<a data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
			
IPSCONTENT;

foreach ( $element->dataAttributes as $attributeKey => $attributeValue ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attributeKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attributeValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
>
		
IPSCONTENT;

if ( $element->icon ):
$return .= <<<IPSCONTENT
<i class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 <span>
IPSCONTENT;

$val = "{$element->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span> 
IPSCONTENT;

if ( $element->notificationIcon ):
$return .= <<<IPSCONTENT
<span class='ipsNotification'><i class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->notificationIcon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i></span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</a>
</li>
IPSCONTENT;

		return $return;
}

	function rowWrapper( $content ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class="ipsMenu_item">
	{$content}
</li>
IPSCONTENT;

		return $return;
}

	function separator(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsMenu_sep">
	<hr class='ipsHr'>
</li>
IPSCONTENT;

		return $return;
}

	function titleField( $element ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsMenu_title'>
IPSCONTENT;

$val = "{$element->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

		return $return;
}}