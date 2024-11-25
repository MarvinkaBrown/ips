<?php
namespace IPS\Theme;
class class_downloads_front_view extends \IPS\Theme\Template
{	function changeLog( $file, $version ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$versionNumber = $version['b_version'] ?: (string) \IPS\DateTime::ts( $version['b_backup'] );
$return .= <<<IPSCONTENT

<p class='i-color_soft i-margin-bottom_2'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $version['b_backup'] )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_version_released', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
<div class='ipsRichText'>
	
IPSCONTENT;

if ( $version['b_changelog'] ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $version['b_changelog'] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p><em>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_no_changelog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em></p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

if ( isset( $version['b_id'] ) and ( $file->canDownload() or ( $file->canEdit() and \IPS\Member::loggedIn()->group['idm_bypass_revision'] ) ) ):
$return .= <<<IPSCONTENT

	<ul class='ipsList ipsList--inline'>
		<li><img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "subitem_stem.png", "core", 'front', false );
$return .= <<<IPSCONTENT
' alt=''> <strong>
IPSCONTENT;

$sprintf = array($versionNumber); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'with_version', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong></li>
		
IPSCONTENT;

if ( $file->canDownload() ):
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;

if ( \IPS\Settings::i()->idm_antileech AND !$file->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('download')->setQueryString( array( 'version' => $version['b_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('download')->setQueryString( array( 'version' => $version['b_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $file->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT
data-ipsDialog
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $file->canEdit() and \IPS\Member::loggedIn()->group['idm_bypass_revision'] ):
$return .= <<<IPSCONTENT

			<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( array( 'do' => 'restorePreviousVersion', 'version' => $version['b_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'version_restore_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubMessage="
IPSCONTENT;

$sprintf = array($versionNumber); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'version_restore_confirm_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( array( 'do' => 'deletePreviousVersion', 'version' => $version['b_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'version_delete_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( array( 'do' => 'previousVersionVisibility', 'version' => $version['b_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

if ( $version['b_hidden'] ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unhide_from_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide_from_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</a>
			</li>
		
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

	function commentForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--file-comment" action="
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

	<div class='i-background_2 i-padding_3'>
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'write_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<ul class='ipsForm ipsForm--vertical ipsForm--file-comment'>
			
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
					<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</div>
			</li>
		</ul>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function comments( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.front.core.commentFeed, core.front.core.ignoredComments' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-commentsType='comments' data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $file->isLastPage() ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='comments' class='ipsEntries ipsEntries--comments ipsEntries--download-comments'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->featuredComments( $file->featuredComments(), $file->url()->setQueryString( 'tab', 'comments' )->setQueryString( 'recommended', 'comments' ) );
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $file->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

		<div class='ipsButtonBar'>
			<div class='ipsButtonBar__pagination'>{$file->commentPagination( array('tab') )}</div>
			<div class='ipsButtonBar__end'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimodHeader( $file, '#comments' );
$return .= <<<IPSCONTENT
</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	<div data-role='commentFeed' data-controller='core.front.core.moderation'>
		<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->csrf()->setQueryString( 'do', 'multimodComment' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
		
IPSCONTENT;

if ( \count( $file->comments( NULL, NULL, 'date', 'asc', NULL, NULL, NULL, NULL, FALSE, isset( \IPS\Widget\Request::i()->showDeleted ) ) ) ):
$return .= <<<IPSCONTENT


				
IPSCONTENT;

$commentCount=0; $timeLastRead = $file->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $file->comments( NULL, NULL, 'date', 'asc', NULL, NULL, NULL, NULL, FALSE, isset( \IPS\Widget\Request::i()->showDeleted ) ) as $comment ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$lined and $timeLastRead and $timeLastRead->getTimestamp() < $comment->mapped('date') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $lined = TRUE and $commentCount ):
$return .= <<<IPSCONTENT

							<hr class="ipsUnreadBar">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$commentCount++;
$return .= <<<IPSCONTENT

					{$comment->html()}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='ipsEmptyMessage' data-role='noComments'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $file );
$return .= <<<IPSCONTENT

			</form>
	</div>
	
IPSCONTENT;

if ( $file->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

		<div class='ipsButtonBar ipsButtonBar--bottom'>
			<div class='ipsButtonBar__pagination'>{$file->commentPagination( array('tab') )}</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $file->commentForm() || $file->locked() || \IPS\Member::loggedin()->restrict_post || \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] || !\IPS\Member::loggedIn()->checkPostsPerDay() ):
$return .= <<<IPSCONTENT

		<div class='ipsComposeAreaWrapper' data-role='replyArea'>
			
IPSCONTENT;

if ( $file->commentForm() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $file->locked() ):
$return .= <<<IPSCONTENT

					<p class='ipsComposeArea_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_locked_can_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				{$file->commentForm()}
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $file->locked() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'file_locked_cannot_comment' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( \IPS\Member::loggedin()->restrict_post ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'restricted_cannot_comment', \IPS\Member::loggedIn()->warnings(5,NULL,'rpa'), \IPS\Member::loggedIn()->restrict_post );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'unacknowledged_warning_cannot_post', \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( !\IPS\Member::loggedIn()->checkPostsPerDay() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'member_exceeded_posts_per_day' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function download( $file, $terms=NULL, $download=NULL, $confirmUrl=NULL, $multipleFiles=NULL, $waitingOn=NULL, $waitingFor=0 ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='downloads.front.view.download'>
	
IPSCONTENT;

if ( $terms ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "downloads" )->downloadTerms( $file, $terms, $confirmUrl, $multipleFiles );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "downloads" )->multipleFiles( $file, $download, $waitingOn, $waitingFor );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function downloadButton( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/downloadButton", "downloadButton:before", [ $file ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="downloadButton">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/downloadButton", "downloadButton:inside-start", [ $file ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !$file->canDownload() AND !( !$file->container()->can( 'download' ) AND $file->container()->message('npd') ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<button class="ipsButton ipsButton--inherit ipsResponsive_hidePhone" disabled type="button"><i class="fa-solid fa-circle-info"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_no_permission_guest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--soft ipsButton--wide i-margin-top_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<button class="ipsButton ipsButton--inherit ipsResponsive_hidePhone" disabled type="button"><i class="fa-solid fa-circle-info"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_no_permission', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

if ( \IPS\Settings::i()->idm_antileech AND !$file->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('download')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('download'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--large i-width_100p" 
IPSCONTENT;

if ( $file->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT
data-ipsdialog
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-datalayer-postfetch><i class="fa-solid fa-download"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/downloadButton", "downloadButton:inside-end", [ $file ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/downloadButton", "downloadButton:after", [ $file ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function downloadSidebar( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function downloadTeaser(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<span class="i-color_soft ipsResponsive_hidePhone"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_teaser', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

		return $return;
}

	function downloadTerms( $file, $downloadTerms, $confirmUrl, $multipleFiles ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_terms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_terms_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>

	<hr class='ipsHr'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $downloadTerms, array('ipsRichText--download-terms') );
$return .= <<<IPSCONTENT

</div>
<div class='i-background_3 i-text-align_end i-padding_3'>
	<ul class='ipsList ipsList--inline'>
		<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text' data-action='dialogClose' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel_downloading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $confirmUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' data-action='
IPSCONTENT;

if ( $multipleFiles or \IPS\Member::loggedIn()->group['idm_wait_period'] ):
$return .= <<<IPSCONTENT
selectFile
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
download
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'agree_and_download_full', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'agree_and_download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function log( $file, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $file->container()->log ):
$return .= <<<IPSCONTENT

	<div class='i-padding_2'><div class='ipsMessage ipsMessage--info'>
IPSCONTENT;

$pluralize = array( $file->container()->log ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'log_days', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div></div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

{$table}
IPSCONTENT;

		return $return;
}

	function logRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li class='ipsSpanGrid__6 ipsPhotoPanel ipsPhotoPanel--mini i-padding_2'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $row['dmid'] ), 'mini' );
$return .= <<<IPSCONTENT

		<div>
			<h3 class='ipsTruncate_1'>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $row['dmid'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
			<span class='i-color_soft'>
IPSCONTENT;

$val = ( $row['dtime'] instanceof \IPS\DateTime ) ? $row['dtime'] : \IPS\DateTime::ts( $row['dtime'] );$return .= (string) $val;
$return .= <<<IPSCONTENT
</span>
		</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function logTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.global.core.table' 
IPSCONTENT;

if ( $table->getPaginationKey() != 'page' ):
$return .= <<<IPSCONTENT
data-pageParam='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getPaginationKey(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>

	
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

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

		<ol class='ipsGrid 
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
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='i-padding_3 i-text-align_center'>
			<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rows_in_table', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

if ( method_exists( $table, 'container' ) AND $table->container() !== NULL ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $table->container()->can('add') ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->container()->url()->setQueryString( 'do', 'add' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_first_row', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
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

</div>
IPSCONTENT;

		return $return;
}

	function multipleFiles( $fileObject, $files, $waitingOn, $waitingFor ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_your_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<p class='i-color_soft'>
IPSCONTENT;

$pluralize = array( \count( $files ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_file_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</p>
	<hr class='ipsHr'>
	<i-data>
		<ul class='ipsData ipsData--table ipsData--multiple-files'>
			
IPSCONTENT;

foreach ( $files as $k => $file ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$data = $files->data();
$return .= <<<IPSCONTENT

				<li class='ipsData__item'>
					<div class='ipsData__main'>
						<h4 class='ipsData__title'><span class=''>
IPSCONTENT;

if ( $data['record_realname'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['record_realname'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$pathBits = array_filter( explode( '/', \IPS\Http\Url::external( $data['record_location'] )->data[ \IPS\Http\Url::COMPONENT_PATH ] ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $pathBits ) ? array_pop( $pathBits ) : $data['record_location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></h4>
						
IPSCONTENT;

if ( $data['record_size'] ):
$return .= <<<IPSCONTENT
<p class='ipsData__meta'>
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $data['record_size'] );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div>
						<span class="ipsHide" data-role="downloadCounterContainer">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_begins_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span data-role="downloadCounter"></span> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'seconds', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $fileObject->url('download')->setQueryString( array( 'r' => $k, 'confirm' => 1, 't' => 1, 'version' => isset( \IPS\Request::i()->version ) ? \IPS\Request::i()->version : NULL ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small' data-action="download" 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['idm_wait_period'] ):
$return .= <<<IPSCONTENT
data-wait='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
</div>
IPSCONTENT;

		return $return;
}

	function notify( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id and $file->author()->member_id !== \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

<div data-controller='downloads.front.view.subscribe'>
	
IPSCONTENT;

if ( $file->subscribed() ):
$return .= <<<IPSCONTENT

		<a class="ipsButton ipsButton--text" data-action="subscribe" data-ipsHover data-ipsHover-target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( array( 'do' => 'subscribeBlurb' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=view&id={$file->id}&do=toggleSubscription" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_unsubscribe', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</i></a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<a class="ipsButton ipsButton--text" data-action="subscribe" data-ipsHover data-ipsHover-target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( array( 'do' => 'subscribeBlurb' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=view&id={$file->id}&do=toggleSubscription" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_subscribe', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
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

	function notifyBlurb( $file, $options ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_notify_heading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<br><br>
	
IPSCONTENT;

if ( $options ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$sprintf = array($options); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_notify_current', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_notify_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<br>
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=options&type=new_file_version", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_type_immediate_change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function pendingView( $file, $pendingVersion ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $file->container()->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $file->container() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id='elClubContainer'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div>
	<div class='
IPSCONTENT;

if ( $file->primary_screenshot ):
$return .= <<<IPSCONTENT
ipsColumns
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		<div 
IPSCONTENT;

if ( $file->primary_screenshot_thumb ):
$return .= <<<IPSCONTENT
class='ipsColumns__primary'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
            <header class='ipsPageHeader ipsBox ipsPull ipsPageHeader--downloads-pending'>
                <div class="ipsPageHeader__row">
					<div class='ipsPageHeader__primary'>
						<h1 class='ipsPageHeader__title'>
							
IPSCONTENT;

if ( $pendingVersion->hidden() === 1 ):
$return .= <<<IPSCONTENT

								<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-triangle-exclamation'></i></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<span class=''>
IPSCONTENT;

if ( $file->locked() ):
$return .= <<<IPSCONTENT
<i class='fa-solid fa-lock'></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $file->container()->version_numbers ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingVersion->form_values['file_version'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
						</h1>	
						
IPSCONTENT;

if ( $file->isPaid() ):
$return .= <<<IPSCONTENT

							<p class="ipsPageHeader__desc">
								
IPSCONTENT;

if ( $price = $file->price() ):
$return .= <<<IPSCONTENT

									<span class='cFilePrice'>{$price}</span>
									
IPSCONTENT;

if ( $renewalTerm = $file->renewalTerm() ):
$return .= <<<IPSCONTENT

										<span class=''> &middot; 
IPSCONTENT;

$sprintf = array($renewalTerm); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_renewal_term_val', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class='i-color_soft'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts($pendingVersion->date)->html()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_new_version_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
				</div>
                <div class="ipsPageHeader__row">
                    <div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
                        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $file->author(), 'tiny', $file->warningRef() );
$return .= <<<IPSCONTENT

                        <div>
                            <p class='i-font-size_2 i-link-color_inherit'>
                                
IPSCONTENT;

$htmlsprintf = array($file->author()->link( $file->warningRef(), NULL, $file->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate_itemprop', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['idm_view_approvers'] and $file->approver ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $file->approver )->name); $htmlsprintf = array(\IPS\DateTime::ts( $file->approvedon )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_approved_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                            </p>
                            
IPSCONTENT;

if ( $file->author()->member_id ):
$return .= <<<IPSCONTENT

                            <ul class='ipsList ipsList--inline'>
                                
IPSCONTENT;

if ( $file->author()->member_id ):
$return .= <<<IPSCONTENT

                                    <li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$file->author()->member_id}&do=content&type=downloads_file", "front", "profile_content", $file->author()->members_seo_name, 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'find_users_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
                    </div>
                </div>
            </header>
        </div>
	</div>

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->contentItemMessages( $file->getMessages(), $file );
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

if ( $pendingVersion->hidden() === 1 and $pendingVersion->canUnhide() ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--warning i-margin-block_2">
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_version_pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
            
IPSCONTENT;

if ( $file->hidden() === 1 ):
$return .= <<<IPSCONTENT

            <br>
            <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_version_pending_cannot_approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<br>
			<ul class='ipsList ipsList--inline' data-controller="downloads.front.pending.buttons">
                
IPSCONTENT;

if ( $file->hidden() !== 1 ):
$return .= <<<IPSCONTENT

				<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingVersion->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unhide' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--positive ipsButton--small" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve_title_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                <li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingVersion->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm  title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_version_reject_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_version_reject_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_version_reject_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--negative ipsButton--small'><i class='fa-solid fa-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_version_reject', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			</ul>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class='ipsBox i-margin-top_block'>
		
IPSCONTENT;

if ( $file->screenshots( 0, TRUE, NULL, TRUE )->getInnerIterator()->count() ):
$return .= <<<IPSCONTENT

			<h2 class='ipsBox__header ipsHide'>
IPSCONTENT;

$pluralize = array( $file->screenshots()->getInnerIterator()->count() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'screenshots_ct', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
			<div class='ipsBox__content'>
				<ul class='ipsCarousel ipsCarousel--images i-gap_2 ipsCarousel--downloads-pending-screenshots' id='downloads-pending-screenshots' tabindex="0">
					
IPSCONTENT;

$fullScreenshots = iterator_to_array( $file->screenshots( 0, TRUE, NULL, TRUE) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $file->screenshots( 1, TRUE, NULL, TRUE ) as $id => $screenshot ):
$return .= <<<IPSCONTENT

						<li class='i-background_1 i-padding_2'>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $fullScreenshots[ $id ]->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsThumb" data-ipsLightbox data-ipsLightbox-group="download_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $screenshot->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading='lazy'>
							</a>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'downloads-pending-screenshots' );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
		<div class='ipsColumns'>
			<article class='ipsColumns__primary'>
				<div class='i-padding_3'>
					<section class='i-margin-top_3'>
						<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_changelog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						
IPSCONTENT;

if ( empty( $pendingVersion->content() ) ):
$return .= <<<IPSCONTENT

							<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_no_changelog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class='ipsRichText ' data-controller='core.front.core.lightboxedImages'>
								{$pendingVersion->content()}
						</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


						<h2 class='ipsTitle ipsTitle--h3 i-margin-top_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads_file_pl', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						
IPSCONTENT;

$files = $file->files( NULL, TRUE, TRUE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \count( $files ) ):
$return .= <<<IPSCONTENT

						<i-data>
							<ul class='ipsData ipsData--table ipsData--pending-view'>
								
IPSCONTENT;

foreach ( $files as $f ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$data = $files->data();
$return .= <<<IPSCONTENT

								<li class='ipsData__item'>
									<div class='ipsData__main'>
										<h4 class='ipsData__title'><a href="
IPSCONTENT;

if ( \IPS\Settings::i()->idm_antileech AND !$file->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingVersion->url('download')->csrf()->setQueryString( 'fileId', $data['record_id'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingVersion->url('download')->setQueryString( 'fileId', $data['record_id'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $f->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
										
IPSCONTENT;

if ( $data['record_size'] ):
$return .= <<<IPSCONTENT
<p class='ipsData__meta'>
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $data['record_size'] );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
									<div class=''>
										<span class="ipsHide" data-role="downloadCounterContainer">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_begins_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span data-role="downloadCounter"></span> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'seconds', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
										<a href="
IPSCONTENT;

if ( \IPS\Settings::i()->idm_antileech AND !$file->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingVersion->url('download')->csrf()->setQueryString( 'fileId', $data['record_id'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingVersion->url('download')->setQueryString( 'fileId', $data['record_id'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</div>
								</li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</ul>
						</i-data>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<p class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_no_changelog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</section>
				</div>
			</article>
			<aside class='ipsColumns__secondary i-basis_280'>
				<div class='i-padding_3'>
					
IPSCONTENT;

if ( $file->topic() ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->topic()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dl_get_support_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--wide ipsButton--secondary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dl_get_support', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						<hr class='ipsHr'>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<i-data>
						<ul class="ipsData ipsData--table ipsData--file-information">
							<li class="ipsData__item">
								<div class='ipsData__main'>
									<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submitted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								</div>
								<div class="i-basis_160">
IPSCONTENT;

$val = ( $file->submitted instanceof \IPS\DateTime ) ? $file->submitted : \IPS\DateTime::ts( $file->submitted );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
							</li>
							
IPSCONTENT;

if ( $file->updated != $file->submitted ):
$return .= <<<IPSCONTENT

								<li class="ipsData__item">
									<div class='ipsData__main'>
										<div class='ipsData__main'>
											<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
										</div>
										<div class="i-basis_160">
IPSCONTENT;

$val = ( $file->updated instanceof \IPS\DateTime ) ? $file->updated : \IPS\DateTime::ts( $file->updated );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
									</div>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					</i-data>
				</div>
			</aside>
		</div>
	</div>

</div>


IPSCONTENT;

if ( $file->container()->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function purchaseTerms( $file, $purchaseTerms, $confirmUrl ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_purchase_terms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_purchase_terms_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>

	<hr class='ipsHr'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $purchaseTerms, array('ipsRichText--purchase-terms') );
$return .= <<<IPSCONTENT

</div>
<div class='i-background_3 i-text-align_end i-padding_3'>
	<ul class='ipsList ipsList--inline'>
		<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text' data-action='dialogClose' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $confirmUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'agree_and_purchase_full', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'agree_and_purchase', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function reviews( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='core.front.core.commentFeed' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-commentsType='reviews' data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $file->isLastPage('reviews') ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->reviewFeedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='reviews' class='ipsEntries ipsEntries--reviews ipsEntries--download-reviews'>
	
IPSCONTENT;

if ( $file->reviewForm() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $file->locked() ):
$return .= <<<IPSCONTENT

			<strong class='i-color_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'item_locked_can_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div id='elFileReviewForm'>
			{$file->reviewForm()}
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $file->hasReviewed() ):
$return .= <<<IPSCONTENT

			<!-- Already reviewed -->
		
IPSCONTENT;

elseif ( $file->locked() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'item_locked_cannot_review' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( \IPS\Member::loggedin()->restrict_post ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->restrict_post == -1 ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'restricted_cannot_comment' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'restricted_cannot_comment', \IPS\Member::loggedIn()->warnings(5,NULL,'rpa'), \IPS\Member::loggedIn()->restrict_post );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( $file->mustDownloadBeforeReview() ):
$return .= <<<IPSCONTENT

			<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_download_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'unacknowledged_warning_cannot_post', \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $file->reviews( NULL, NULL, 'date', 'desc', NULL, NULL, NULL, NULL, isset( \IPS\Widget\Request::i()->showDeleted ) ) ) ):
$return .= <<<IPSCONTENT


		<div class='ipsButtonBar ipsButtonBar--top'>
			
IPSCONTENT;

if ( $file->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

				<div class='ipsButtonBar__pagination'>{$file->reviewPagination( array( 'tab', 'sort' ) )}</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end'>
				<ul class="ipsDataFilters">
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( array( 'tab' => 'reviews', 'sort' => 'helpful' ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->sort ) or \IPS\Widget\Request::i()->sort != 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'most_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( array( 'tab' => 'reviews', 'sort' => 'newest' ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->sort ) and \IPS\Widget\Request::i()->sort == 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'newest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				</ul>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimodHeader( $file, '#reviews', 'review' );
$return .= <<<IPSCONTENT

			</div>
		</div>		
		<div data-role='commentFeed' data-controller='core.front.core.moderation'>
			<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->csrf()->setQueryString( 'do', 'multimodReview' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
				
IPSCONTENT;

$reviewCount=0; $timeLastRead = $file->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $file->reviews( NULL, NULL, 'date', 'desc', NULL, NULL, NULL, NULL, isset( \IPS\Widget\Request::i()->showDeleted ) ) as $review ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$lined and $timeLastRead and $timeLastRead->getTimestamp() < $review->mapped('date') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $lined = TRUE and $reviewCount ):
$return .= <<<IPSCONTENT

							<hr class="ipsUnreadBar">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$reviewCount++;
$return .= <<<IPSCONTENT

					{$review->html()}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $file, 'review' );
$return .= <<<IPSCONTENT

			</form>
		</div>
		
IPSCONTENT;

if ( $file->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar ipsButtonBar--bottom'>
				<div class='ipsButtonBar__pagination'>{$file->reviewPagination( array( 'tab', 'sort' ) )}</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( !$file->canReview() ):
$return .= <<<IPSCONTENT

		<div class='ipsBox__padding'>
			<p class='ipsEmptyMessage' data-role='noReviews'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function view( $file, $commentsAndReviews, $versionData, $previousVersions, $next=NULL, $prev=NULL, $cfields=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $file->container()->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $file->container() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id="elClubContainer">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class="ipsBlockSpacer">
	<div class="ipsBox ipsPull">
		<header class="ipsPageHeader">
			<div class="ipsPageHeader__row">
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "header:before", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "header:inside-start", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "title:before", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "title:inside-start", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "badges:before", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="badges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "badges:inside-start", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $file->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "badges:inside-end", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "badges:after", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

						<h1>
							
IPSCONTENT;

if ( $file->canEdit() ):
$return .= <<<IPSCONTENT

								<span data-controller="core.front.core.moderation">
									<span data-role="editableTitle" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

if ( $file->container()->version_numbers ):
$return .= <<<IPSCONTENT
 <span class="i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</span>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $file->container()->version_numbers ):
$return .= <<<IPSCONTENT
 <span class="i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</h1>
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "title:inside-end", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "title:after", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $file->isPaid() ):
$return .= <<<IPSCONTENT

						<p class="i-margin-top_2">
							
IPSCONTENT;

if ( $price = $file->price() ):
$return .= <<<IPSCONTENT

								<span class="cFilePrice">{$price}</span>
								
IPSCONTENT;

if ( $renewalTerm = $file->renewalTerm() ):
$return .= <<<IPSCONTENT

									<span class="i-color_soft">· 
IPSCONTENT;

$sprintf = array($renewalTerm); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_renewal_term_val', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $file->container()->bitoptions['reviews'] ):
$return .= <<<IPSCONTENT

						<div class="ipsPageHeader__rating">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $file->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $file->memberReviewRating() );
$return .= <<<IPSCONTENT
 <span class="i-color_soft">(
IPSCONTENT;

$pluralize = array( $file->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span></div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \count( $file->tags() ) OR ( $file->canEdit() AND $file::canTag( NULL, $file->container() ) ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tagsWithPrefix( $file->tags(), $file->prefix(), FALSE, FALSE, ( $file->canEdit() AND ( \count( $file->tags() ) OR $file::canTag( NULL, $file->container() ) ) ) ? $file->url() : NULL );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "header:inside-end", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "header:after", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

				<div class="ipsButtons">
					
IPSCONTENT;

if ( \count( $file->shareLinks() ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $file );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "downloads" )->notify( $file );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $file );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'downloads', 'file', $file->id, $file->followersCount() );
$return .= <<<IPSCONTENT

				</div>
			</div>
			<div class="ipsPageHeader__row ipsPageHeader__row--footer">
				<div class="ipsPageHeader__primary">
					<div class="ipsPhotoPanel ipsPhotoPanel--inline">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $file->author(), 'tiny', $file->warningRef() );
$return .= <<<IPSCONTENT

						<div class="ipsPhotoPanel__text">
							<p class="ipsPhotoPanel__primary">
								
IPSCONTENT;

$htmlsprintf = array($file->author()->link( $file->warningRef(), NULL, $file->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate_itemprop', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['idm_view_approvers'] and $file->approver ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $file->approver )->name); $htmlsprintf = array(\IPS\DateTime::ts( $file->approvedon )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_approved_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $file->isAnonymous() and \IPS\Member::loggedIn()->modPermission('can_view_anonymous_posters') ):
$return .= <<<IPSCONTENT

									<a data-ipshover data-ipshover-width="370" data-ipshover-onclick href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( 'reveal' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><span class="cAuthorPane_badge cAuthorPane_badge_small cAuthorPane_badge--anon" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_anonymously_reveal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span></a>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</p>
							
IPSCONTENT;

if ( $file->author()->member_id OR $file->canChangeAuthor() ):
$return .= <<<IPSCONTENT

								<ul class="ipsPhotoPanel__secondary ipsList ipsList--sep">
									
IPSCONTENT;

if ( $file->author()->member_id ):
$return .= <<<IPSCONTENT

										<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$file->author()->member_id}&do=content&type=downloads_file", "front", "profile_content", $file->author()->members_seo_name, 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'find_users_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $file->canChangeAuthor() ):
$return .= <<<IPSCONTENT

										<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( array( 'do' => 'changeAuthor' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_author_d', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_author_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_author_d', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
					</div>
				</div>
			</div>
		</header>
		
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->contentItemMessages( $file->getMessages(), $file );
$return .= <<<IPSCONTENT


	    
IPSCONTENT;

if ( ( \IPS\Member::loggedIn()->member_id == $file->author()->member_id OR $file->canUnhide() OR $file->canHide() ) AND $file->hasPendingVersion() ):
$return .= <<<IPSCONTENT

			<div class="ipsMessage ipsMessage--warning">
				<div class="ipsColumns">
					<p class="ipsColumns__primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_pending_approval_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					<ul class="ipsColumns__secondary ipsButtons" data-controller="downloads.front.pending.buttons">
						
IPSCONTENT;

if ( $file->canUnhide() OR $file->canHide() ):
$return .= <<<IPSCONTENT

							<li>
								<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\downloads\File\PendingVersion::load($file->id, 'pending_file_id')->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--positive ipsButton--small" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_pending_version_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_pending_version_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $file->canDeletePendingVersion()  ):
$return .= <<<IPSCONTENT

							<li>
								<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\downloads\File\PendingVersion::load($file->id, 'pending_file_id')->url()->setQueryString('do','delete')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--small" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			</div>
	    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( $file->hidden() === 1 and $file->canUnhide() ):
$return .= <<<IPSCONTENT

			<div class="ipsMessage ipsMessage--warning">
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				<br>
				<ul class="ipsList ipsList--inline">
					<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unhide' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--positive ipsButton--small" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve_title_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

if ( $file->canDelete() ):
$return .= <<<IPSCONTENT

						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads_delete_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--small"><i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div class="ipsColumns ipsColumns--lines">
			
IPSCONTENT;

if ( $file->screenshots()->getInnerIterator()->count() ):
$return .= <<<IPSCONTENT

				<section class="i-basis_100p">
					<h2 class="ipsBox__header ipsHide">
IPSCONTENT;

$pluralize = array( $file->screenshots()->getInnerIterator()->count() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'screenshots_ct', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
					<div class="">
						<ul class="ipsCarousel ipsCarousel--images ipsCarousel--downloads-file-screenshots i-gap_2 i-padding_2" id="downloads-file-screenshots" tabindex="0">
							
IPSCONTENT;

$fullScreenshots = iterator_to_array( $file->screenshots() );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $file->screenshots( 1 ) as $id => $screenshot ):
$return .= <<<IPSCONTENT

								<li>
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $fullScreenshots[ $id ]->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsThumb" data-ipslightbox data-ipslightbox-group="download_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
										<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $screenshot->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
									</a>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'downloads-file-screenshots' );
$return .= <<<IPSCONTENT

					</div>
				</section>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
			<article class="ipsColumns__primary i-flex i-flex-direction_column">
				
IPSCONTENT;

$tabs = array();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $cfields as $field ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $field['location'] == 'tab' ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$tabs[] = "downloads_{$field['key']}";
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


				<div class="i-flex_11">
					
IPSCONTENT;

if ( \count( $tabs ) ):
$return .= <<<IPSCONTENT

						<i-tabs class="ipsTabs" id="ipsTabs_file" data-ipstabbar data-ipstabbar-contentarea="#ipsTabs_file_content">
							<div role="tablist">
								<button type="button" id="ipsTabs_file_desc" class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_file_desc_panel" aria-selected="true">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								
IPSCONTENT;

foreach ( $tabs as $name ):
$return .= <<<IPSCONTENT

									<button type="button" id="ipsTabs_file_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_file_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="false">
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</div>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

						</i-tabs>
						<div id="ipsTabs_file_content" class="ipsTabs__panels ipsTabs__panels--padded">
							<div id="ipsTabs_file_desc_panel" class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_file_desc">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<section class="ipsEntry i-padding_3">
							<div class="ipsEntry__post">
								<div class="ipsRichText ipsRichText--user" data-controller="core.front.core.lightboxedImages">{$file->content()}</div>
								
IPSCONTENT;

if ( $file->editLine() ):
$return .= <<<IPSCONTENT

								    {$file->editLine()}
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $versionData['b_changelog'] or !empty( $previousVersions ) ):
$return .= <<<IPSCONTENT

									<section data-controller="downloads.front.view.changeLog" class="i-margin-top_4 i-background_2 i-padding_3 i-border-radius_box">
										<div class="i-flex i-align-items_center i-gap_1 i-flex-wrap_wrap i-justify-content_space-between i-margin-bottom_3">
											<h2 class="ipsTitle ipsTitle--h5">
IPSCONTENT;

$sprintf = array($versionData['b_version'] ?: (string) \IPS\DateTime::ts( $versionData['b_backup'] )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'whats_new_in_version', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h2>
											
IPSCONTENT;

if ( !empty( $previousVersions ) ):
$return .= <<<IPSCONTENT
<a href="#" id="elChangelog" data-ipsmenu data-ipsmenu-selectable="radio" class="i-color_soft i-font-weight_500" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_changelog_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_changelog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down i-margin-start_icon"></i></a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</div>
										<div data-role="changeLogData">
											
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", \IPS\Request::i()->app )->changeLog( $file, $versionData );
$return .= <<<IPSCONTENT

										</div>
										<ul class="ipsMenu ipsMenu_selectable ipsMenu_auto ipsHide" id="elChangelog_menu">
											
IPSCONTENT;

$versionNumber = $file->version ?: (string) \IPS\DateTime::ts( $file->published );
$return .= <<<IPSCONTENT

											<li class="ipsMenu_item 
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->changelog ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsmenuvalue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $versionNumber, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQuerystring( 'changelog', 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($versionNumber); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_changelog_for', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $versionNumber, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
											
IPSCONTENT;

foreach ( $previousVersions as $version ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$versionNumber = $version['b_version'] ?: (string) \IPS\DateTime::ts( $version['b_backup'] );
$return .= <<<IPSCONTENT

												<li class="ipsMenu_item 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->changelog == $version['b_id'] ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $version['b_hidden'] ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsmenuvalue="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $versionNumber . $version['b_backup'] . mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-changelogtitle="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $versionNumber, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
													<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( 'changelog', $version['b_id'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($versionNumber); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_changelog_for', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
														
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $versionNumber, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
														<span class="i-color_soft">
IPSCONTENT;

$return .= \IPS\DateTime::ts( $version['b_backup'] )->html();
$return .= <<<IPSCONTENT
</span>
													</a>
												</li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									</section>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


								
IPSCONTENT;

foreach ( $cfields as $field ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $field['location'] == 'below' ):
$return .= <<<IPSCONTENT

										<hr class="i-margin-top_4">
										<h2 class="ipsTitle ipsTitle--h3 ipsTitle--margin">
IPSCONTENT;

$val = "downloads_{$field['key']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
										<div class="ipsRichText" data-controller="core.front.core.lightboxedImages">
											{$field['value']}
										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</div>
						</section>
					
IPSCONTENT;

if ( \count( $tabs ) ):
$return .= <<<IPSCONTENT

						</div>
						
IPSCONTENT;

foreach ( $cfields as $field ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $field['location'] == 'tab' ):
$return .= <<<IPSCONTENT

								<div id="ipsTabs_file_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field['key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_file_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field['key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" hidden>
									<div class="ipsRichText" data-controller="core.front.core.lightboxedImages">
										{$field['value']}
									</div>
								</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

$menu = $file->menu();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $menu->hasContent() || ( \IPS\IPS::classUsesTrait( $file, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ) ):
$return .= <<<IPSCONTENT

					<div class="ipsEntry__footer">
					    
IPSCONTENT;

if ( $menu->hasContent() ):
$return .= <<<IPSCONTENT

						<menu class="ipsEntry__controls">
							<li>{$menu}</li>
						</menu>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $file, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $file );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</article>
			<aside class="ipsColumns__secondary i-basis_380">
				<div class="i-padding_3">
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "downloadButtonList:before", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
<ul class="ipsButtons i-margin-bottom_4" data-ips-hook="downloadButtonList">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "downloadButtonList:inside-start", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $file->canBuy() ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $file->canDownload() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "downloads" )->downloadButton( $file );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

if ( !$file->isPurchasable( FALSE ) ):
$return .= <<<IPSCONTENT

									<button class="ipsButton ipsButton--inherit ipsResponsive_hidePhone" disabled type="button"><i class="fa-solid fa-circle-info"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchasing_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<a href="
IPSCONTENT;

if ( !$file->container()->message('disclaimer') OR !\in_array( $file->container()->disclaimer_location, [ 'purchase', 'both' ] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('buy')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('buy'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--large i-width_100p" 
IPSCONTENT;

if ( $file->container()->message('disclaimer') AND \in_array( $file->container()->disclaimer_location, [ 'purchase', 'both']) ):
$return .= <<<IPSCONTENT
data-ipsdialog
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class="fa-solid fa-cart-shopping"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'buy_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $price = $file->price() ):
$return .= <<<IPSCONTENT
 - {$price}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</li>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $file->canDownload() or !$file->downloadTeaser() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "downloads" )->downloadButton( $file );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								{$file->downloadTeaser()}
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( ( isset( $purchasesToRenew ) or $purchasesToRenew = $file->purchasesToRenew() ) and \count( $purchasesToRenew ) ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

if ( \count( $purchasesToRenew ) === 1 ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

foreach ( $purchasesToRenew as $purchase ):
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url()->setQueryString('do', 'renew')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--large i-width_100p"><i class="fa-solid fa-arrows-rotate"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 - 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->renewals->cost, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<a href="#elFileRenew_menu" id="elFileRenew" class="ipsButton ipsButton--primary ipsButton--large i-width_100p" data-ipsmenu><i class="fa-solid fa-arrows-rotate"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
									<ul id="elFileRenew_menu" class="ipsMenu ipsMenu_auto ipsHide">
										
IPSCONTENT;

foreach ( $purchasesToRenew as $purchase ):
$return .= <<<IPSCONTENT

											<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url()->setQueryString('do', 'renew')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br><span class="i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->renewals, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a></li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>		
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
						
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $file->topic() ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->topic()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dl_get_support_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--large ipsButton--text i-width_100p"><i class="fa-regular fa-life-ring"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dl_get_support', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "downloadButtonList:inside-end", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "downloadButtonList:after", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

					<h2 class="ipsTitle ipsTitle--h4 ipsTitle--margin">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "downloadsFileStats:before", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
<ul class="ipsList ipsList--label-value ipsList--border ipsList--icons" data-ips-hook="downloadsFileStats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "downloadsFileStats:inside-start", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

						<li>
							<i class="fa-solid fa-eye"></i>
							<strong class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'views', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							<span class="ipsList__value">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->views );
$return .= <<<IPSCONTENT
</span>
						</li>
						
IPSCONTENT;

if ( $file->isPaid() and !$file->nexus and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) ):
$return .= <<<IPSCONTENT

							<li>
								<i class="fa-solid fa-cart-shopping"></i>
								<strong class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'idm_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								<span class="ipsList__value">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !$file->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) )  ):
$return .= <<<IPSCONTENT

							<li>
								<i class="fa-solid fa-download"></i>
								<strong class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads_file_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								<span class="ipsList__value">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<li>
							<i class="fa-solid fa-calendar-plus"></i>
							<strong class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submitted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							<span class="ipsList__value">
IPSCONTENT;

$val = ( $file->submitted instanceof \IPS\DateTime ) ? $file->submitted : \IPS\DateTime::ts( $file->submitted );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
						</li>
						
IPSCONTENT;

if ( $file->published ):
$return .= <<<IPSCONTENT

							<li>
								<i class="fa-solid fa-calendar-check"></i>
								<strong class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_published', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								<span class="ipsList__value">
IPSCONTENT;

$val = ( $file->published instanceof \IPS\DateTime ) ? $file->published : \IPS\DateTime::ts( $file->published );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $file->updated != $file->submitted ):
$return .= <<<IPSCONTENT

							<li>
								<i class="fa-solid fa-arrows-rotate"></i>
								<strong class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								<span class="ipsList__value">
IPSCONTENT;

$val = ( $file->updated instanceof \IPS\DateTime ) ? $file->updated : \IPS\DateTime::ts( $file->updated );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $file->filesize() ):
$return .= <<<IPSCONTENT

							<li>
								<i class="fa-solid fa-file-lines"></i>
								<strong class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filesize', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								<span class="ipsList__value">
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $file->filesize() );
$return .= <<<IPSCONTENT
</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $cfields as $field ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $field['location'] == 'sidebar' ):
$return .= <<<IPSCONTENT

								<li>
									<i class="fa-solid fa-list"></i>
									<strong class="ipsList__label">
IPSCONTENT;

$val = "downloads_{$field['key']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
									<span class="ipsList__value">{$field['value']}</span>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "downloadsFileStats:inside-end", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/view/view", "downloadsFileStats:after", [ $file,$commentsAndReviews,$versionData,$previousVersions,$next,$prev,$cfields ] );
$return .= <<<IPSCONTENT

					
					
IPSCONTENT;

if ( $file->canViewDownloaders() and $file->downloads ):
$return .= <<<IPSCONTENT

						<ul class="ipsButtons i-margin-top_3">
							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('log'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_downloader_list', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-width_100p" data-ipsdialog data-ipsdialog-size="wide" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloaders', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-circle-user"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'who_downloaded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
				</div>
			</aside>
		</div>
	</div>

	<div class="ipsPageActions ipsBox i-padding_2 ipsPull ipsResponsive_showPhone">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "downloads" )->notify( $file );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $file->shareLinks() ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $file, 'verySmall', 'light' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $file );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'downloads', 'file', $file->id, $file->followersCount() );
$return .= <<<IPSCONTENT

	</div>
	
	
IPSCONTENT;

if ( $prev || $next ):
$return .= <<<IPSCONTENT

		<div class="ipsPager">
			<div class="ipsPager_prev">
				
IPSCONTENT;

if ( $prev !== NULL ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $prev->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
						<span class="ipsPager_type">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						<span class="ipsPager_title ipsTruncate_1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $prev->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( $next !== NULL ):
$return .= <<<IPSCONTENT

				<div class="ipsPager_next">
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $next->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
						<span class="ipsPager_type">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						<span class="ipsPager_title ipsTruncate_1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $next->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
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

if ( $commentsAndReviews ):
$return .= <<<IPSCONTENT

		<a id="replies"></a>
		<h2 class="ipsHide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_feedback', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="">{$commentsAndReviews}</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>


IPSCONTENT;

if ( $file->container()->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}