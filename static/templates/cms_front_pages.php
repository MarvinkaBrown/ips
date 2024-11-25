<?php
namespace IPS\Theme;
class class_cms_front_pages extends \IPS\Theme\Template
{	function globalWrap( $navigation, $content, $page ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elCmsPageWrap" data-pageid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $page->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
{$content}
</div>
IPSCONTENT;

		return $return;
}

	function mainArea( $page, $widgets ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $widgets as $area => $areaWidgets ):
$return .= <<<IPSCONTENT

<div>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "pages", "cms" )->widgetContainer( $area, $widgets );
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function widgetContainer( $id, $widgets, $orientation='horizontal' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( \IPS\Output::i()->sidebar['widgetareas'][$id] ) ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$area = \IPS\Output::i()->sidebar['widgetareas'][$id];
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->widgetArea( $area );
$return .= <<<IPSCONTENT


IPSCONTENT;

elseif ( array_key_exists( $id, $widgets ) or \IPS\Dispatcher::i()->application->canManageWidgets() ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( isset( $widgets[ $id ] ) AND \is_string( $widgets[ $id ] ) ):
$return .= <<<IPSCONTENT

    {$widgets[ $id ]}
    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<section class='cWidgetContainer cWidgetContainer--main 
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->_blockManager and ! isset( $widgets[ $id ] ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Dispatcher::i()->application->canManageWidgets() ):
$return .= <<<IPSCONTENT
data-controller='core.front.widgets.area'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role='widgetReceiver' data-orientation='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $orientation, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-widgetArea='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<ul>
			
IPSCONTENT;

if ( isset( $widgets[ $id ] ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $widgets[ $id ]->getAllWidgets() as $_widget ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $widget = \IPS\Widget::createWidgetFromStoredData( $_widget ) ):
$return .= <<<IPSCONTENT

				<li
                    class='
IPSCONTENT;

if ( \get_class( $widget ) != 'IPS\cms\widgets\Database' ):
$return .= <<<IPSCONTENT
ipsWidget 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $widget->getWrapperClasses() as $class ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

foreach ( $widget->dataAttributes() as $k => $v ):
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

                    data-controller='core.front.widgets.block'
                >
                    {$widget}
                </li>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	</section>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}