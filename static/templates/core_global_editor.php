<?php
namespace IPS\Theme;
class class_core_global_editor extends \IPS\Theme\Template
{	function attachedAudio( $realUrl, $linkUrl, $title, $mimeType, $id ) {
		$return = '';
		$return .= <<<IPSCONTENT

<audio controls src="<fileStore.core_Attachment>/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $realUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mimeType, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-fileid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" preload="metadata">
    <a class="ipsAttachLink" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $linkUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
</audio>
IPSCONTENT;

		return $return;
}

	function attachedFile( $url, $title, $pTag=TRUE, $ext='', $fileId='', $fileKey='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $pTag ):
$return .= <<<IPSCONTENT
<p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<a class="ipsAttachLink" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-fileExt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $ext, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $fileId ):
$return .= <<<IPSCONTENT
data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $fileId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $fileKey ):
$return .= <<<IPSCONTENT
data-filekey='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $fileKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

if ( $pTag ):
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function attachedImage( $url, $thumbnail, $title, $id, $width, $height, $altText=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p><a href="<fileStore.core_Attachment>/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsAttachLink ipsAttachLink_image" 
IPSCONTENT;

if ( $altText ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $altText, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><img data-fileid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" src="<fileStore.core_Attachment>/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thumbnail, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" height="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" width="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsImage ipsImage_thumbnailed" alt="
IPSCONTENT;

if ( $altText ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $altText, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" loading='lazy'></a></p>
IPSCONTENT;

		return $return;
}

	function attachedVideo( $realUrl, $linkUrl, $title, $mimeType, $id ) {
		$return = '';
		$return .= <<<IPSCONTENT

<video controls class="ipsEmbeddedVideo" data-fileid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-controller="core.global.core.embeddedvideo" preload='metadata'>
	<source src="<fileStore.core_Attachment>/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $realUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mimeType, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<a class="ipsAttachLink" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $linkUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
</video>
IPSCONTENT;

		return $return;
}

	function fakeFormTemplate( $id, $action, $tabs, $hiddenValues, $actionButtons, $uploadField ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
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

if ( $uploadField ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<input type="hidden" name="plupload" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $tabs as $elements ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $elements as $element ):
$return .= <<<IPSCONTENT

			{$element->html()}
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</form>

IPSCONTENT;

		return $return;
}

	function image( $editorId, $width, $height, $maximumWidth, $maximumHeight, $float, $link, $ratioWidth, $ratioHeight, $imageAlt, $editorUniqueId ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class=" ipsForm ipsForm--vertical ipsForm--editor-image" data-imageWidthRatio='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $ratioWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-imageHeightRatio='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $ratioHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-editorid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editorId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-editorUniqueId='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editorUniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<form method='get' action='#'>
		<div class='i-padding_3'>
			<div class="ipsFieldRow ipsFieldRow--fullWidth ipsFieldRow--primary">
				<label class='ipsFieldRow__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<input type="text" class="ipsInput ipsInput--text" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="imageLink">
			</div>

			<div class="ipsFieldRow ipsFieldRow--fullWidth ipsFieldRow--primary">
				<label class='ipsFieldRow__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_alt', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<input type="text" class="ipsInput ipsInput--text" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageAlt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="imageAlt">
				<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_alt_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</div>

			<div class="ipsFieldRow ipsFieldRow--primary">
				<label class='ipsFieldRow__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_size', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<div class='ipsComposeArea_imageDims'>
					<input type="number" class="ipsInput ipsInput--text ipsField_short" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" max="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maximumWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="imageWidth">
					<span class='i-font-size_-2 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_width_help', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</div> &times; 
				<div class='ipsComposeArea_imageDims'>
					<input type="number" class="ipsInput ipsInput--text ipsField_short" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" max="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maximumHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="imageHeight">
					<span class='i-font-size_-2 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_height_help', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</div> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'px', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				<p class='i-margin-top_2'>
					<input type='checkbox' name='image_aspect_ratio' id='elEditorImageRatio' 
IPSCONTENT;

if ( round( \IPS\Widget\Request::i()->actualWidth / \IPS\Widget\Request::i()->actualHeight, 2 ) == round( $width / $height, 2 ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
					<label for='elEditorImageRatio'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_aspect', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				</p>
				<br>
				<span class="i-color_warning" data-role="imageSizeWarning"></span>
			</div>
			<div class="ipsFieldRow ipsFieldRow--primary">
				<label class='ipsFieldRow__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_align', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<ul class='ipsButtonGroup ipsButton--small ipsComposeArea_imageAlign'>
					<li>
						<input type='radio' name='image_align' value='left' id='image_align_left' data-role="imageAlign" 
IPSCONTENT;

if ( $float == 'left' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<label for='image_align_left' class='ipsButton 
IPSCONTENT;

if ( $float == 'left' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_align_left', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</label>
					</li>
					<li>
						<input type='radio' name='image_align' value='' id='image_align_none' data-role="imageAlign" 
IPSCONTENT;

if ( $float != 'left' and $float != 'right' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<label for='image_align_none' class='ipsButton 
IPSCONTENT;

if ( $float !== 'left' && $float !=='right' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</label>
					</li>
					<li>
						<input type='radio' name='image_align' value='right' id='image_align_right' data-role="imageAlign" 
IPSCONTENT;

if ( $float == 'right' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<label for='image_align_right' class='ipsButton 
IPSCONTENT;

if ( $float == 'right' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_align_right', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</label>
					</li>
				</ul>
			</div>
		</div>
		<div class='i-padding_3 i-background_3 ipsFieldRow'>
			<button type='submit' class="ipsButton ipsButton--primary ipsButton--wide" autofocus>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'update', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function linkedImage( $imageUrl, $imageName ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p><img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsImage ipsImage_thumbnailed" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading='lazy'></p>
IPSCONTENT;

		return $return;
}

	function myMedia( $editorId, $mediaSources, $currentMediaSource, $url, $results ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$safeEditorId = preg_replace( "/[^a-zA-Z0-9\-_]/", '_', $editorId );
$return .= <<<IPSCONTENT

<div class="cMyMedia" data-controller='core.global.editor.insertable' data-editorid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editorId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<div id="elEditor
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $safeEditorId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Attach" class="cMyMedia__align">
		
IPSCONTENT;

if ( \count( $mediaSources ) > 1 ):
$return .= <<<IPSCONTENT

			<div class="ipsColumns ipsColumns--lines" data-ipsTabBar data-ipsTabBar-contentArea='#elEditor
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $safeEditorId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
AttachTabContent' data-ipsTabBar-itemSelector=".ipsSideMenu_item" data-ipsTabBar-activeClass="ipsSideMenu_itemActive" data-ipsTabBar-updateURL="false">
				<div class="ipsColumns__secondary i-basis_220 i-padding_2 i-background_1">
					<div class="ipsSideMenu" id='elAttachmentsMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $safeEditorId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsSideMenu>
						<h3 class='ipsSideMenu__view'>
							<a href='#elAttachmentsMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $safeEditorId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='openSideMenu'><i class='fa-solid fa-bars'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_attachment_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						</h3>
						<div class="ipsSideMenu__menu">
							<ul class="ipsSideMenu__list">
								
IPSCONTENT;

foreach ( $mediaSources as $k ):
$return .= <<<IPSCONTENT

									<li>
										<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=editor&do=myMedia&tab={$k}&existing=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" id="elEditor
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $safeEditorId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
AttachTabMedia
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsSideMenu_item 
IPSCONTENT;

if ( $currentMediaSource == $k ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "editorMedia_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</ul>
						</div>
					</div>
				</div>
				<div class="ipsColumns__primary">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div id="elEditor
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $safeEditorId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
AttachTabContent" data-role="myMediaContent">
				
IPSCONTENT;

if ( \count( $mediaSources )  ):
$return .= <<<IPSCONTENT

					{$results}
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<p class="ipsEmptyMessage i-text-align_start">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_no_media', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

if ( \count( $mediaSources ) > 1 ):
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsSubmitRow cMyMedia_controls'>
		<ul class='ipsButtons ipsButtons--end'>
			<li><a href='#' data-action="clearAll" class='ipsButton ipsButton--small ipsButton--inherit ipsButton--disabled'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_clear_selection', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li><a href='#' data-action="insertSelected" class='ipsButton ipsButton--small ipsButton--secondary ipsButton--disabled'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_image_upload_insert_selected', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		</ul>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function myMediaContent( $files, $pagination, $url, $extension ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.global.editor.mymediasection' data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&search=1">
	<div class='i-background_3 i-padding_2'>
		<input type="search" class="ipsInput ipsInput--text ipsInput--wide" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_media_search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-role="myMediaSearch">
	</div>
	<div data-role="myMediaResults" data-extension="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $extension, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "editor", "core", 'global' )->myMediaResults( $files, $pagination, $url, $extension );
$return .= <<<IPSCONTENT

	</div>	
</div>
IPSCONTENT;

		return $return;
}

	function myMediaResults( $files, $pagination, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty($files) ):
$return .= <<<IPSCONTENT

	<div class='ipsEmptyMessage'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class="ipsAttachment_fileList ipsAttachment_fileList--grid">
		
IPSCONTENT;

foreach ( $files as $url => $file ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", \IPS\Request::i()->app, 'global' )->uploadFile( preg_replace( '/' . preg_quote( \IPS\Settings::i()->base_url . 'applications/core/interface/file/attachment.php?id=', '/' ) . '(\d+)(&key=[a-z0-9]+)?/i', '$1', $url ), $file, NULL, TRUE, TRUE, $url );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	<div class="i-font-size_-2 i-padding_1">
		{$pagination}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}}