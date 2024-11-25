<?php
namespace IPS\Theme;
class class_gallery_front_forms extends \IPS\Theme\Template
{	function commentTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$minimized = false;
$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--gallery-comment" action="
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

	<div class='ipsComposeArea'>
		<div class='ipsComposeArea_editor'>
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $input->name == 'guest_name' or $input->name == 'guest_email' ):
$return .= <<<IPSCONTENT

						<ul class='ipsForm ipsForm--vertical ipsForm--guest-gallery-comment' data-ipsEditor-toolList>
							<li class='ipsFieldRow ipsFieldRow--fullWidth'>
								<div class="ipsFieldRow__content">
									{$input->html()}
									
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

										<div class="ipsFieldRow__warning" data-role="commentFormError">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</li>
						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Editor ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $input->options['minimize'] !== NULL ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$minimized = true;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						{$input->html( TRUE )}
						
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

							<div class="ipsFieldRow__warning" data-role="commentFormError">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
						
IPSCONTENT;

endif;
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

			<div class='i-flex i-align-items_center i-flex-wrap_wrap i-gap_3 i-margin-top_2' data-ipsEditor-toolList 
IPSCONTENT;

if ( $minimized ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<ul class='i-flex_91 i-flex i-flex-wrap_wrap i-gap_4 i-row-gap_1'>
					
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !($input instanceof \IPS\Helpers\Form\Editor) && $input->name != 'guest_name' && $input->name != 'guest_email' ):
$return .= <<<IPSCONTENT

								<li class='
IPSCONTENT;

if ( !($input instanceof \IPS\Helpers\Form\Captcha) ):
$return .= <<<IPSCONTENT
ipsComposeArea_formControl
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									{$input->html()}
									
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

										<div class="ipsFieldRow__warning" data-role="commentFormError">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
									
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

endforeach;
$return .= <<<IPSCONTENT

				</ul>
				<ul class='ipsButtons i-flex_11'>
					
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

						<li>{$button}</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function commentUnavailable( $lang, $warnings=array(), $ends=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsComposeArea ipsComposeArea--unavailable'>
	<div class='ipsComposeArea_editor'>
		<div class="ipsComposeArea_dummy">
			<span class='i-color_warning'><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $ends !== NULL AND $ends > 0 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $ends )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restriction_ends', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		
			
IPSCONTENT;

if ( \count( $warnings)  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $warnings as $idx => $warning ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $idx === 0 ):
$return .= <<<IPSCONTENT

						<br><br>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' class='ipsButton ipsButton--small ipsButton--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_warning_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
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
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function imageUpload( $name, $value, $minimize, $maxFileSize, $maxFiles, $maxChunkSize, $totalMaxSize, $allowedFileTypes, $pluploadKey, $multiple=FALSE, $editor=FALSE, $forceNoscript=FALSE, $template='core.attachments.fileItem', $existing=array(), $default=NULL, $supportsDelete = TRUE, $allowStockPhotos=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" type="hidden" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pluploadKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">

IPSCONTENT;

if ( $forceNoscript ):
$return .= <<<IPSCONTENT

	<input name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_noscript[]" type="file" 
IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT
multiple
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<span class="i-color_soft i-font-size_-2">
		
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

			<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_accepted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ', ', $allowedFileTypes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $multiple and $totalMaxSize ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

				&middot;
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_total_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $totalMaxSize * 1048576 );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !$multiple or !$totalMaxSize or $maxChunkSize < $totalMaxSize ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $allowedFileTypes !== NULL or ( $multiple and $totalMaxSize ) ):
$return .= <<<IPSCONTENT

				&middot;
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxChunkSize, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
MB
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $maxFiles ):
$return .= <<<IPSCONTENT

			<br>
IPSCONTENT;

$pluralize = array( $maxFiles ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max_files', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<noscript>
		<input name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_noscript[]" type="file" 
IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT
multiple
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<span class="i-color_soft i-font-size_-2">
			
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

				<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_accepted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ', ', $allowedFileTypes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $multiple and $totalMaxSize ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

					&middot;
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_total_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $totalMaxSize * 1048576 );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !$multiple or !$totalMaxSize or $maxChunkSize < $totalMaxSize ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $allowedFileTypes !== NULL or ( $multiple and $totalMaxSize ) ):
$return .= <<<IPSCONTENT

					&middot;
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxChunkSize, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
MB
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $maxFiles ):
$return .= <<<IPSCONTENT

				<br>
IPSCONTENT;

$pluralize = array( $maxFiles ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max_files', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</span>
	</noscript>
	
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $value as $id => $file ):
$return .= <<<IPSCONTENT

			<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_existing[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->tempId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_drop_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		data-ipsUploader
		
IPSCONTENT;

if ( $maxFileSize ):
$return .= <<<IPSCONTENT
data-ipsUploader-maxFileSize="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( $maxFileSize, 3, '.', '' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $maxFiles ):
$return .= <<<IPSCONTENT
data-ipsUploader-maxFiles="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxFiles, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		data-ipsUploader-maxChunkSize="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( $maxChunkSize, 3, '.', '' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		
IPSCONTENT;

if ( $allowedFileTypes ):
$return .= <<<IPSCONTENT
data-ipsUploader-allowedFileTypes='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $allowedFileTypes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		data-ipsUploader-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		data-ipsUploader-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pluploadKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		
IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT
data-ipsUploader-multiple 
IPSCONTENT;

if ( $totalMaxSize ):
$return .= <<<IPSCONTENT
data-ipsUploader-maxTotalSize="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( $totalMaxSize, 3, '.', '' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $minimize ):
$return .= <<<IPSCONTENT
data-ipsUploader-minimized
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $editor ):
$return .= <<<IPSCONTENT
data-ipsUploader-insertable
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		data-ipsUploader-template='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
		data-ipsUploader-existingFiles='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $existing ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
		
IPSCONTENT;

if ( isset( $default ) ):
$return .= <<<IPSCONTENT
data-ipsUploader-default='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $default, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $supportsDelete ):
$return .= <<<IPSCONTENT
data-ipsUploader-supportsDelete
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ipsUploader-supportsDelete='false'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $allowStockPhotos ):
$return .= <<<IPSCONTENT
data-ipsUploader-allowStockPhotos="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $allowStockPhotos, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		class='ipsUploader'
	>
		<div class="ipsAttachment_dropZone 
IPSCONTENT;

if ( $minimize ):
$return .= <<<IPSCONTENT
ipsAttachment_dropZoneSmall
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
			<i class="fa-solid fa-upload ipsUploader__icon"></i>
			
IPSCONTENT;

if ( $minimize ):
$return .= <<<IPSCONTENT

				<div class="ipsAttachment_loading ipsLoading--small ipsHide"><i class='fa-solid fa-circle-notch fa-spin fa-fw'></i></div>
                <div class='ipsAttachment_dropZoneSmall_info'>
					<span class="ipsAttachment_supportDrag">
IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_dad_mini', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_dad_mini_one', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<br></span>
					<span class="i-color_soft i-font-size_-2">
						
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_accepted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ', ', $allowedFileTypes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $multiple and $totalMaxSize ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

								&middot;
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_total_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $totalMaxSize * 1048576 );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $maxFileSize and ( !$multiple or !$totalMaxSize or $maxFileSize < $totalMaxSize ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $allowedFileTypes !== NULL or ( $multiple and $totalMaxSize ) ):
$return .= <<<IPSCONTENT

								&middot;
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round($maxFileSize,2), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
MB
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $maxFiles ):
$return .= <<<IPSCONTENT

							<br>
IPSCONTENT;

$pluralize = array( $maxFiles ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max_files', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				</div>
				<div class='ipsUploader__buttons'>
					<a href="#" data-action='uploadFile' class="ipsButton ipsButton--small ipsButton--primary" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_browse_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_choose', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_choose_one', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

if ( $allowStockPhotos ):
$return .= <<<IPSCONTENT

						<a href="#" data-action='stockPhoto' class="ipsButton ipsButton--small ipsButton--soft" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_stockphoto_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_stockart_choose', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class='ipsAttachment_dropZoneSmall_info'>
					<span class="ipsAttachment_supportDrag">
IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_dad', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_dad_one', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<br></span>
					<div class="ipsAttachment_loading ipsLoading--small ipsHide"><i class='fa-solid fa-circle-notch fa-spin fa-fw'></i></div>
					<br>
					<span class="i-color_soft i-font-size_-2">
						
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_accepted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ', ', $allowedFileTypes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $multiple and $totalMaxSize ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

								&middot;
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_total_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $totalMaxSize * 1048576 );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $maxFileSize and ( !$multiple or !$totalMaxSize or $maxFileSize < $totalMaxSize ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $allowedFileTypes !== NULL or ( $multiple and $totalMaxSize ) ):
$return .= <<<IPSCONTENT

								&middot;
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round($maxFileSize,2), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
MB
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $maxFiles ):
$return .= <<<IPSCONTENT

							<br>
IPSCONTENT;

$pluralize = array( $maxFiles ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max_files', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				</div>
				<div class='ipsUploader__buttons'>
					<a href="#" data-action='uploadFile' class="ipsButton ipsButton--small ipsButton--primary" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_browse_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_choose', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_choose_one', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

if ( $allowStockPhotos ):
$return .= <<<IPSCONTENT

						<a href="#" data-action='stockPhoto' class="ipsButton ipsButton--small ipsButton--soft" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_stockphoto_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_stockart_choose', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsAttachment_fileList">
			<div data-role='fileList'></div>
			<noscript>
				
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $value as $id => $file ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->uploadFile( $id, $file, $name, $editor, ( $template === 'core.attachments.imageItem' ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</noscript>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reviewTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.front.core.reviewForm'>
	<div>
		<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--gallery-review" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action->setQueryString( '_review', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

if ( !isset( \IPS\Widget\Request::i()->_review ) and \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				<div data-role='reviewIntro' class="i-padding_3">
					<h3 class="i-margin-bottom_2"><strong class="i-color_hard i-font-weight_600">
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_intro_1', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong> <span class='i-color_soft i-font-weight_500'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_intro_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></h3>
					<a href='#' class='ipsButton ipsButton--primary ipsButton--small ipsJS_show' data-action='writeReview'><i class="fa-regular fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'write_a_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<ul class='ipsForm ipsForm--vertical ipsForm--gallery-review 
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->_review ) and \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-padding_3' data-role='reviewForm'>
				
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

						{$input}
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<li class='ipsFieldRow'>
					<div class='ipsFieldRow__content'>
						<button type='submit' class='ipsButton ipsButton--primary'><i class="fa-regular fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
					</div>
				</li>
			</ul>
		</form>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function reviewUnavailable( $lang, $warnings=array(), $ends=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class=''>
	<div>
		<strong class='i-color_warning'><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $ends !== NULL AND $ends > 0 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $ends )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restriction_ends', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</strong>
		
IPSCONTENT;

if ( \count( $warnings)  ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $warnings as $idx => $warning ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $idx === 0 ):
$return .= <<<IPSCONTENT

					<br><br>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' class='ipsButton ipsButton--small ipsButton--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_warning_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
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
</div>
<hr class='ipsHr'>
IPSCONTENT;

		return $return;
}}