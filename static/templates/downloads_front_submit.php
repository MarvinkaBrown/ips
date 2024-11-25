<?php
namespace IPS\Theme;
class class_downloads_front_submit extends \IPS\Theme\Template
{	function bulkForm( $form, $category ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('submit_bulk_information') );
$return .= <<<IPSCONTENT

<hr class='ipsHr'>


IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--error">
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</div>
	<br>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



<form accept-charset='utf-8' method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" enctype="multipart/form-data" id='elDownloadsSubmit' data-ipsForm data-ipsFormSubmit>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $form->hiddenValues as $k => $v ):
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

foreach ( $form->elements as $fileName => $collection ):
$return .= <<<IPSCONTENT

		<h2 class='ipsBox__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $fileName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
		<div class='i-background_3 i-padding_2'>
			<div class='i-background_1 i-padding_3'>
				<ul class='ipsForm ipsForm--vertical ipsForm--bulk-submit-files'>
					
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\FormAbstract ):
$return .= <<<IPSCONTENT

							{$input}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
		<br><hr class='ipsHr'><br>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


	<div class='i-text-align_end'>
		<button type='submit' class='ipsButton ipsButton--primary' data-role='submitForm'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save_and_submit_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function categorySelector( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('select_category') );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='' data-template='categorySelector'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	{$form}

IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function editDetailsInfo( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsMessage ipsMessage--info'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( array( 'do' => 'newVersion' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upload_new_version_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_versioning_info_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>
IPSCONTENT;

		return $return;
}

	function linkedScreenshotField( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="downloads.front.submit.linkedScreenshots" data-name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-initialValue='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $value ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<ul data-role="fieldsArea"></ul>
	<a class="ipsButton ipsButton--soft ipsButton--small" data-action="addField"><i class="fa-solid fa-plus-circle"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stack_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function submissionForm( $form, $category, $terms, $newSubmission=TRUE, $bulk=0, $postingInformation='', $versioning=0 ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$nonInfoFields = array('files', 'import_files', 'url_files', 'screenshots', 'url_screenshots');
$return .= <<<IPSCONTENT


IPSCONTENT;

$step = 1;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $newSubmission ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $bulk ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack( 'submit_form_desc_bulk', TRUE, array( 'sprintf' => $category->_title ) ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack( 'submit_form_desc', TRUE, array( 'sprintf' => $category->_title ) ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $versioning and !\IPS\Member::loggedIn()->group['idm_bypass_revision'] ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack( 'upload_new_version' ), \IPS\Member::loggedIn()->language()->addToStack( 'new_version_versioning' ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack( 'upload_new_version' ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $postingInformation ):
$return .= <<<IPSCONTENT

{$postingInformation}

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

<div class="ipsMessage ipsMessage--error">
	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

</div>
<br>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<form accept-charset='utf-8' method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='elDownloadsSubmit' enctype="multipart/form-data" 
IPSCONTENT;

if ( $category->bitoptions['allowss'] AND $category->bitoptions['reqss'] ):
$return .= <<<IPSCONTENT
data-screenshotsReq='1'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $bulk ):
$return .= <<<IPSCONTENT
data-bulkUpload='1'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $category->multiple_files ):
$return .= <<<IPSCONTENT
data-multipleFiles='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( !$newSubmission ):
$return .= <<<IPSCONTENT
data-newVersion='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsForm data-ipsFormSubmit data-controller='downloads.front.submit.main'>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $form->hiddenValues as $k => $v ):
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


	<div class='ipsColumns'>
		<div class='ipsColumns__secondary i-basis_50 i-text-align_center'>
			<span class='cDownloadsSubmit_step'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $step, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$step++;
$return .= <<<IPSCONTENT
</span>
		</div>
		<div class='ipsColumns__primary'>
			<div class='ipsBox'>
				<h3 class='ipsBox__header'>
IPSCONTENT;

if ( $category->multiple_files ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_your_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_your_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h3>
				<div class='i-padding_3'>
					
IPSCONTENT;

if ( isset( $form->elements['']['import_files'] ) || isset( $form->elements['']['url_files'] ) ):
$return .= <<<IPSCONTENT

					<ul class='ipsList ipsList--inline i-text-align_end'>
						
IPSCONTENT;

if ( isset( $form->elements['']['url_files'] ) ):
$return .= <<<IPSCONTENT

						<li>
							<a href='#' class='ipsButton ipsButton--soft ipsButton--small' id='elURLFiles' data-ipsMenu data-ipsMenu-closeOnClick='false' data-ipsMenu-appendTo='#elDownloadsSubmit'>
								<i class='fa-solid fa-earth-americas'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_files_by_url', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i>
								<span class='ipsNotification 
IPSCONTENT;

if ( !\count( $form->elements['']['url_files']->value ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='fileCount'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $form->elements['']['url_files']->value ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							</a>
							<div id='elURLFiles_menu' class='ipsMenu ipsMenu_normal ipsHide i-padding_3'>
								<ul class='ipsFieldRow--fullWidth'>
									{$form->elements['']['url_files']}
								</ul>
								<hr class='ipsHr'>
								<a href='#' class='ipsButton ipsButton--wide ipsButton--primary' data-action='confirmUrls'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_menu_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</div>
						</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( isset( $form->elements['']['import_files'] ) ):
$return .= <<<IPSCONTENT

						<li>
							<a href='#' class='ipsButton ipsButton--soft ipsButton--small' id='elImportFiles' data-ipsMenu data-ipsMenu-closeOnClick='false' data-ipsMenu-appendTo='#elDownloadsSubmit'>
								<i class='fa-solid fa-folder'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_files_by_path', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i>
								<span class='ipsNotification 
IPSCONTENT;

if ( !\count( $form->elements['']['import_files']->value ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='fileCount'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $form->elements['']['import_files']->value ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							</a>
							<div id='elImportFiles_menu' class='ipsMenu ipsMenu_normal ipsHide i-padding_3'>
								<ul class='ipsFieldRow--fullWidth'>
									{$form->elements['']['import_files']}
								</ul>
								<hr class='ipsHr'>
								<a href='#' class='ipsButton ipsButton--wide ipsButton--primary' data-action='confirmImports'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_menu_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</div>
						</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div id='elDownloadsSubmit_progress' class='i-position_sticky-top'>
						<div class='ipsProgress ipsProgress--animated' >
							<div class='ipsProgress__progress' data-progress='0%'></div>
						</div>
					</div>
					<div id='elDownloadsSubmit_uploader' class=''>
						{$form->elements['']['files']->html( $form )}
						
IPSCONTENT;

if ( $category->multiple_files ):
$return .= <<<IPSCONTENT

						<button type='button' class='ipsButton ipsButton--soft ipsButton--small ipsHide i-margin-top_3' data-action='uploadMore'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upload_more_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		</div>
	</div>

	
IPSCONTENT;

if ( !$bulk  ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $category->bitoptions['allowss']  ):
$return .= <<<IPSCONTENT

	<div id='elDownloadsSubmit_screenshots'>
		<br><br>
		<div class='ipsColumns'>
			<div class='ipsColumns__secondary i-basis_50 i-text-align_center'>
				<span class='cDownloadsSubmit_step'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $step, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$step++;
$return .= <<<IPSCONTENT
</span>
			</div>
			<div class='ipsColumns__primary'>
				<div class='ipsBox'>
					<h3 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_screenshots', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<div class='i-padding-inline_3 i-padding-block_2'>
						
IPSCONTENT;

if ( isset( $form->elements['']['url_screenshots'] ) ):
$return .= <<<IPSCONTENT

						<ul class='ipsList ipsList--inline i-text-align_end'>
							<li>
								<a href='#' class='ipsButton ipsButton--soft ipsButton--small' id='elURLScreenshots' data-ipsMenu data-ipsMenu-closeOnClick='false' data-ipsMenu-appendTo='#elDownloadsSubmit_screenshots'>
									<i class='fa-solid fa-earth-americas'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_screenshots_by_url', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i>
									<span class='ipsNotification 
IPSCONTENT;

if ( !isset( $form->elements['']['url_screenshots']->value['values'] ) OR !\count( $form->elements['']['url_screenshots']->value['values'] ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='fileCount'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $form->elements['']['url_screenshots']->value['values'] ?? array() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</a>
								<div id='elURLScreenshots_menu' class='ipsMenu ipsMenu_wide ipsHide i-padding_3'>
									<ul class='ipsFieldRow--fullWidth'>
										{$form->elements['']['url_screenshots']}
									</ul>
									<hr class='ipsHr'>
									<a href='#' class='ipsButton ipsButton--wide ipsButton--primary' data-action='confirmScreenshotUrls'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_menu_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</div>
							</li>
						</ul>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( isset( $form->elements['']['screenshots'] ) ):
$return .= <<<IPSCONTENT

						<div id='elDownloadsSubmit_screenshots'>
							{$form->elements['']['screenshots']->html( $form )}
						</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		</div>
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div id='elDownloadsSubmit_otherinfo'>
		<br><br>
		<div class='ipsColumns'>
			<div class='ipsColumns__secondary i-basis_50 i-text-align_center'>
				<span class='cDownloadsSubmit_step'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $step, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$step++;
$return .= <<<IPSCONTENT
</span>
			</div>
			<div class='ipsColumns__primary'>
				<div class='ipsBox'>
					<h3 class='ipsBox__header'>
IPSCONTENT;

if ( $newSubmission ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_file_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_version_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h3>
					<ul class='ipsForm ipsForm--vertical ipsForm--submit-file'>
						
IPSCONTENT;

foreach ( $form->elements as $collection ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $collection as $fieldName => $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !\in_array( $fieldName, $nonInfoFields ) ):
$return .= <<<IPSCONTENT

						{$input}
						
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
				</div>
			</div>
		</div>

		
IPSCONTENT;

if ( $terms ):
$return .= <<<IPSCONTENT

		<br><br>
		<div class='ipsColumns'>
			<div class='ipsColumns__secondary i-basis_50 i-text-align_center'>
				<span class='cDownloadsSubmit_step'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $step, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$step++;
$return .= <<<IPSCONTENT
</span>
			</div>
			<div class='ipsColumns__primary'>
				<div class='ipsBox'>
					<h3 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'csubmissionterms_placeholder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<div class='i-padding_3 ipsRichText'>
						{$terms}
					</div>
				</div>
			</div>
		</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

elseif ( $terms ):
$return .= <<<IPSCONTENT

	<br>
	<div class='ipsColumns'>
		<div class='ipsColumns__secondary i-basis_50 i-text-align_center'>
			<span class='cDownloadsSubmit_step'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $step, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$step++;
$return .= <<<IPSCONTENT
</span>
		</div>
		<div class='ipsColumns__primary'>
			<div class='ipsBox'>
				<h3 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'csubmissionterms_placeholder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<div class='i-padding_3 ipsRichText'>
					{$terms}
				</div>
			</div>
		</div>
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class='ipsColumns'>
		<div class='ipsColumns__secondary i-basis_50'></div>
		<div class='ipsColumns__primary'>
			<div class='ipsBox i-padding_3 i-text-align_center i-margin-top_3'>
				<button type='submit' class='ipsButton ipsButton--primary' data-role='submitForm'>
					
IPSCONTENT;

if ( $bulk ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $newSubmission ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $category->multiple_files ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save_and_submit_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save_and_submit_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save_and_submit_new_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</button>
			</div>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function topic( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>

IPSCONTENT;

if ( $file->desc ):
$return .= <<<IPSCONTENT

{$file->desc}

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsRichTextBox ipsRichTextBox--alwaysopen">
    <div class="ipsRichTextBox__title">
        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
    </div>
    <p>
        <strong><span data-ips-font-size="90">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_submitter', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </span></strong> {$file->author()->link( null, null, $file->isAnonymous() )}
    </p>
    <p>
        <strong><span data-ips-font-size="90">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_submitted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </span></strong> 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $file->submitted )->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

    </p>
    <p>
        <strong><span data-ips-font-size="90">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_cat', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </span></strong> <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
    </p>
    
IPSCONTENT;

foreach ( $file->customFields( TRUE ) as $k => $v ):
$return .= <<<IPSCONTENT

        <p>
            <strong><span data-ips-font-size="90">
IPSCONTENT;

$val = "downloads_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </span></strong> {$v}
        </p>
    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>

<p>
    <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</p>
IPSCONTENT;

		return $return;
}

	function wizardForm( $stepNames, $activeStep, $output, $baseUrl, $showSteps ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $stepNames as $step => $name ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $activeStep == $name ):
$return .= <<<IPSCONTENT

		{$output}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}