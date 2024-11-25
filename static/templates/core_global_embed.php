<?php
namespace IPS\Theme;
class class_core_global_embed extends \IPS\Theme\Template
{	function brightcove( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsEmbeddedBrightcove">
  <div class="ipsEmbeddedBrightcove_inner">
    <iframe src="{$url}"
      allowfullscreen
      webkitallowfullscreen
      mozallowfullscreen
	  class="ipsEmbeddedBrightcove_frame">
    </iframe>
  </div>
</div>

IPSCONTENT;

		return $return;
}

	function embedCommentUnavailable( $type, $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed'>
	<div class='i-text-align_center i-color_soft i-padding_3'>
		<i class="fa-regular fa-face-frown i-font-size_7 i-color_soft i-margin-bottom_3"></i>
		<p>
IPSCONTENT;

$sprintf = array($type, $item->url(), $item->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_comment_unavailable_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedNoPermission(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--no-permission'>
	<div class='i-text-align_center i-color_soft i-padding_3'>
		<i class='fa-regular fa-face-frown i-font-size_6 i-color_soft'></i>
		<p class='i-padding_3'>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_no_perm_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_no_perm_desc_log_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedUnavailable(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--unavailable'>
	<div class='i-text-align_center i-color_soft i-padding_3'>
		<i class='fa-regular fa-face-frown i-font-size_6 i-color_soft'></i>
		<p class='i-padding_3'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_unavailable_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function googleMaps( $q, $mapType, $zoom = NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsEmbeddedOther ipsEmbeddedOther--google-maps' contenteditable="false">
	<iframe height="450"
	
IPSCONTENT;

if ( $mapType == 'place' ):
$return .= <<<IPSCONTENT

		src="https://www.google.com/maps/embed/v1/place?key=
IPSCONTENT;

$return .= \IPS\Settings::i()->google_maps_api_key;
$return .= <<<IPSCONTENT
&q=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $q, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

elseif ( $mapType == 'dir' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $q['waypoints'] ) ):
$return .= <<<IPSCONTENT

			src="https://www.google.com/maps/embed/v1/directions?key=
IPSCONTENT;

$return .= \IPS\Settings::i()->google_maps_api_key;
$return .= <<<IPSCONTENT
&origin=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $q['origin'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&waypoints=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $q['waypoints'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&destination=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $q['destination'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			src="https://www.google.com/maps/embed/v1/directions?key=
IPSCONTENT;

$return .= \IPS\Settings::i()->google_maps_api_key;
$return .= <<<IPSCONTENT
&origin=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $q['origin'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&destination=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $q['destination'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( $mapType == 'search' ):
$return .= <<<IPSCONTENT

		src="https://www.google.com/maps/embed/v1/search?key=
IPSCONTENT;

$return .= \IPS\Settings::i()->google_maps_api_key;
$return .= <<<IPSCONTENT
&q=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $q, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

elseif ( $mapType =='coordinates' ):
$return .= <<<IPSCONTENT

		src="https://www.google.com/maps/embed/v1/view?key=
IPSCONTENT;

$return .= \IPS\Settings::i()->google_maps_api_key;
$return .= <<<IPSCONTENT
&center=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $q, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&zoom=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $zoom, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	</iframe>
</div>

IPSCONTENT;

		return $return;
}

	function iframe( $url, $width=NULL, $height=NULL, $embedId=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsEmbeddedOther' contenteditable="false">
	<iframe
            src="{$url}"
            data-controller="core.front.core.autosizeiframe"
            
IPSCONTENT;

if ( $embedId ):
$return .= <<<IPSCONTENT
data-embedId='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $embedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            allowfullscreen
            allowtransparency="true"
    ></iframe>
</div>
IPSCONTENT;

		return $return;
}

	function internal( $preview, $author, $contentApp, $contentClass, $contentId, $time, $contentComment=null ) {
		$return = '';
		$return .= <<<IPSCONTENT


<iframe
	src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $preview, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-embedContent
    data-internalembed
	data-controller='core.front.core.autosizeiframe'
	data-embedauthorid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $author, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-ipsembed-contentapp='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contentApp, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-ipsembed-contentclass='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contentClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-ipsembed-contentid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contentId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
    data-ipsembed-timestamp="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $time, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

if ( $contentComment AND ($idCol=$contentComment::$databaseColumnId) AND ($commentId = $contentComment->$idCol) ):
$return .= <<<IPSCONTENT

	data-ipsembed-contentcommentid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $commentId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	allowfullscreen=''
></iframe>
IPSCONTENT;

		return $return;
}

	function link( $url, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener' data-fromembed>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function photo( $imageUrl, $linkUrl=NULL, $title=NULL, $width=NULL, $height=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $linkUrl ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $linkUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $width ):
$return .= <<<IPSCONTENT
width="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $height ):
$return .= <<<IPSCONTENT
height="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 loading='lazy'>

IPSCONTENT;

if ( $linkUrl ):
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function video( $html ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsEmbeddedVideo' contenteditable="false">{$html}</div>
IPSCONTENT;

		return $return;
}}