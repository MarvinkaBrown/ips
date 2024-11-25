<?php
namespace IPS\Theme;
class class_core_front_promote extends \IPS\Theme\Template
{	function edit( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function promoteAttachments( $name, $value, $minimize, $maxFileSize, $maxFiles, $maxChunkSize, $totalMaxSize, $allowedFileTypes, $pluploadKey, $multiple=FALSE, $editor=FALSE, $forceNoscript=FALSE, $template='core.attachments.fileItem', $existing=array(), $default=NULL, $supportsDelete = TRUE ) {
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
<label class="ipsFieldRow__label" for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
    <span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_upload_attachment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
</label>

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
				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

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
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="ipsAttachment_fileList">
				<div data-role='fileList' class='cPromote_attachList'></div>
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
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsFieldRow__desc">
    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_upload_attachment_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function promoteDialog( $title, $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elPromoteDialog' class='
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( $title && !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

		<h1 class="ipsTitle ipsTitle--h3">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div>
		{$form}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function promoteDialogImages( $images, $promote=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsFieldRow ">
	<ul class="cPromote_attachList">
		
IPSCONTENT;

foreach ( $images as $image ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $image as $extension => $file ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$checked = ( $promote ? ( $promote->hasImage( $file, $extension ) ? 'checked="checked"' : '' ) : '' );
$return .= <<<IPSCONTENT

				<li class='cPromote_attachImage 
IPSCONTENT;

if ( !empty( $checked ) ):
$return .= <<<IPSCONTENT
cPromote_attachImageSelected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

$image = \IPS\File::get( $extension, $file )->url;
$return .= <<<IPSCONTENT

					<div data-role="preview">
						<img src="
IPSCONTENT;

$return .= \IPS\File::get( $extension, $file )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
					</div>
					<label class="cPromote_attachImage__checkbox" data-action='selectImage'>
						<input type="checkbox" name="attach_files[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $checked, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 value="1" class="ipsInput">
					</label>
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

		return $return;
}

	function promotePublicTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$pinnedTags = $table->getPinnedTagItems();
$return .= <<<IPSCONTENT

<header class='ipsPageHeader ipsPageHeader--promote-public-table'>
	<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_table_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<p class='ipsPageHeader__desc'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_table_header_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</header>

<div class='ipsBox ipsPull'>
	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			<div class="ipsButtonBar__pagination" data-role="tablePagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \is_array( $rows ) AND \count( $rows ) ):
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
			<div class='ipsData ipsData--grid ipsData--featured-content'>
				
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

elseif ( ! $pinnedTags ):
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_table_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
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

	
IPSCONTENT;

if ( $pinnedTags ):
$return .= <<<IPSCONTENT

	    
IPSCONTENT;

foreach ( $pinnedTags as $row ):
$return .= <<<IPSCONTENT

	        
IPSCONTENT;

$tag = $row['tag'];
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $pinnedItems = $tag->getPinnedItems() ):
$return .= <<<IPSCONTENT

                <div id='elTagPinnedItems'>
                    <h3 class="i-color_soft i-text-transform_uppercase i-font-weight_600 i-font-size_-2 i-padding-block_2 i-padding-inline_3 i-background_2 i-border-top_3 i-border-bottom_3">
                        <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($tag->text); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tags__pinned_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span></a>
                    </h3>
                    <i-data>
                        <ol class='ipsData ipsData--grid ipsData--carousel' id="tags-pinned-carousel" tabindex="0">
                            
IPSCONTENT;

foreach ( $pinnedItems as $pinned ):
$return .= <<<IPSCONTENT

                                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "tags", "core", 'front' )->pinnedItem( $pinned, $tag, false );
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                        </ol>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'tags-pinned-carousel' );
$return .= <<<IPSCONTENT

                    </i-data>
                </div>
            
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
IPSCONTENT;

		return $return;
}

	function promotePublicTableRow( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-->

IPSCONTENT;

$photoCount = $item->imageObjects() ? \count( $item->imageObjects() ) : 0;
$return .= <<<IPSCONTENT


IPSCONTENT;

$staff = \IPS\Member::load( $item->added_by );
$return .= <<<IPSCONTENT


IPSCONTENT;

$itemUrl = ( $item->object() instanceof \IPS\Content\Item ) ? $item->object()->url( "getPrefComment" ) : $item->object()->url();
$return .= <<<IPSCONTENT

<article class='cPromoted ipsData__item'>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->ourPicksTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<figure class="ipsData__image">
		
IPSCONTENT;

if ( $photoCount ):
$return .= <<<IPSCONTENT

		    
IPSCONTENT;

$firstPhoto = $item->imageObjects()[0];
$return .= <<<IPSCONTENT

		    <img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $firstPhoto->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->ourPicksTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
		
IPSCONTENT;

elseif ( $image = $item->object()->primaryImage() ):
$return .= <<<IPSCONTENT

		    <img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->ourPicksTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</figure>
	<div class="ipsData__content">
		<div class="ipsData__main">
			<h2 class='ipsData__title cPromotedTitle'>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id and $item->objectIsUnread ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $item->object() instanceof \IPS\Content\Item ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->object()->url( 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsIndicator' data-ipsTooltip>
					
IPSCONTENT;

elseif ( $item->object() instanceof \IPS\Content\Comment ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->object()->item()->url( 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsIndicator' data-ipsTooltip>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->object()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsIndicator'>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->ourPicksTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			</h2>
			<p class='ipsData__meta'>{$item->objectMetaDescription}</p>
			
IPSCONTENT;

if ( $text = $item->getText(true) ):
$return .= <<<IPSCONTENT

				<div class="ipsData__desc ipsRichText" data-ipsTruncate>{$text}</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $photoCount and $photoCount > 1 ):
$return .= <<<IPSCONTENT

				<ul class='ipsGrid ipsGrid--promote-images i-basis_70 i-gap_2 i-margin-top_2 cPromotedImages'>
					
IPSCONTENT;

foreach ( $item->imageObjects() as $file ):
$return .= <<<IPSCONTENT

						<li>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsLightbox data-ipsLightbox-group='g
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsThumb'>
								<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
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

if ( $item->objectReactionClass AND \IPS\IPS::classUsesTrait( $item->objectReactionClass, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

				<div class="i-margin-top_2 i-flex i-justify-content_end">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $item->objectReactionClass, FALSE );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsData__extra">
			
IPSCONTENT;

if ( $counts = $item->objectDataCount ):
$return .= <<<IPSCONTENT

				<ul class='ipsData__stats'>
					<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $counts['words'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $item->added_by ):
$return .= <<<IPSCONTENT

				<div class='ipsData__last'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $staff, 'fluid' );
$return .= <<<IPSCONTENT

					<div class='ipsData__last-text'>
						<h3 class='ipsData__last-primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promoted_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $staff );
$return .= <<<IPSCONTENT
</h3>
						<div class='ipsData__last-secondary'>
IPSCONTENT;

$val = ( $item->added instanceof \IPS\DateTime ) ? $item->added : \IPS\DateTime::ts( $item->added );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</article>
IPSCONTENT;

		return $return;
}

	function promotePublicTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT
 
	
IPSCONTENT;

foreach ( $rows as $item ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( ( $item->object() instanceof \IPS\Content and  $item->object()->hidden() === 0 ) or ( ! $item->object() instanceof \IPS\Content ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "promote", "core" )->promotePublicTableRow( $item );
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

IPSCONTENT;

		return $return;
}}