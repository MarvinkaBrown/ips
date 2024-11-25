<?php
namespace IPS\Theme;
class class_cms_global_records extends \IPS\Theme\Template
{	function customslug( $collection, $input, $category, $page, $database, $record ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsFieldRow ipsJS_show i-color_soft" data-controller='cms.front.records.form' data-ipsTitleField="content_field_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $database->field_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div>
		
IPSCONTENT;

if ( $collection['record_static_furl']->error ):
$return .= <<<IPSCONTENT

		<p class='i-color_warning'><i class='fa-solid fa-circle-exclamation'></i> 
IPSCONTENT;

$val = "{$collection['record_static_furl']->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<input type="checkbox" class="ipsHide" id="elInput_record_static_furl_set" name="record_static_furl_set_checkbox" 
IPSCONTENT;

if ( $collection['record_static_furl_set']->value ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 value="1">
		<input type="hidden" name="record_static_furl_set" value="
IPSCONTENT;

if ( $collection['record_static_furl_set']->value ):
$return .= <<<IPSCONTENT
1
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
		<i class='fa-solid fa-earth-americas'></i> <strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'url', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</strong> <span data-ipsSlugUrl>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=page&path=$page->full_path", null, "content_page_path", array( $page->full_path ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
</span><span data-ipsSlugCategory>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->full_path, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><span data-ipsSlugSlug></span><span data-ipsSlugManual class="ipsHide">
			/ <input type="text" class='ipsInput ipsInput--text ipsField_short ipsField_tinyText' name="record_static_furl" value="
IPSCONTENT;

if ( $collection['record_static_furl']->value ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $collection['record_static_furl']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
		</span><span data-ipsSlugExt>
IPSCONTENT;

if ( $record !== NULL ):
$return .= <<<IPSCONTENT
-r
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $record->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
-r#
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		<button data-ipsChange class="ipsButton ipsButton--tiny ipsButton--text" type="button">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		<button data-ipsCancel class="ipsHide ipsButton ipsButton--tiny ipsButton--soft" type="button">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function fieldBadge( $label, $value, $classes, $bgcolor=null, $color=null ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class='ipsBadge 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style='
IPSCONTENT;

if ( $bgcolor ):
$return .= <<<IPSCONTENT
background:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $bgcolor, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $color ):
$return .= <<<IPSCONTENT
color:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $label, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
: {$value}</span>
IPSCONTENT;

		return $return;
}

	function fieldDefault( $label, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-color_soft'><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $label, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
:</strong> {$value}</div>
IPSCONTENT;

		return $return;
}

	function soundcloud( $url, $params ) {
		$return = '';
		$return .= <<<IPSCONTENT

<iframe 
IPSCONTENT;

foreach ( $params as $k => $v  ):
$return .= <<<IPSCONTENT

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
 src="https://w.soundcloud.com/player/?url=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&amp;color=ff5500" loading='lazy'></iframe>
IPSCONTENT;

		return $return;
}

	function spotify( $url, $params ) {
		$return = '';
		$return .= <<<IPSCONTENT

<iframe src="https://embed.spotify.com/?uri=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

foreach ( $params as $k => $v  ):
$return .= <<<IPSCONTENT

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
 allowtransparency="true" loading='lazy'></iframe>
IPSCONTENT;

		return $return;
}

	function youtube( $url, $params ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsEmbeddedVideo' contenteditable="false" 
IPSCONTENT;

if ( isset( $params['width'] ) OR isset( $params['height'] ) ):
$return .= <<<IPSCONTENT
style="
IPSCONTENT;

if ( isset( $params['width'] ) ):
$return .= <<<IPSCONTENT
width:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $params['width'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px;max-width:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $params['width'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px;
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( isset( $params['height'] ) ):
$return .= <<<IPSCONTENT
height:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $params['height'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px;
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><div><iframe id="ytplayer" type="text/html" 
IPSCONTENT;

foreach ( $params as $k => $v  ):
$return .= <<<IPSCONTENT

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
 src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading='lazy'></iframe></div></div>
IPSCONTENT;

		return $return;
}}