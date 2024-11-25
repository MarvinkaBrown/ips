<?php
namespace IPS\Theme;
class class_cms_front_revisions extends \IPS\Theme\Template
{	function rows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<article class='ipsEntry js-ipsEntry ipsEntry--simple ipsEntry--revisions'>
		<header class='ipsEntry__header'>
			<div class='ipsEntry__header-align'>
				<div class='ipsPhotoPanel'>
					<div>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row['revision_member_id'], 'mini' );
$return .= <<<IPSCONTENT
</div>
					<div class='ipsPhotoPanel__text'>
						<h3 class='ipsPhotoPanel__primary'>
							{$row['revision_member_id']->link()}
						</h3>
						<p class='ipsPhotoPanel__secondary'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['revision_date'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</p>
					</div>
				</div>
				<div class='i-flex i-flex-wrap_wrap i-gap_3'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'do' => 'revisionView', 'revision_id' => $row['revision_id'], 'd' => \IPS\cms\Databases\Dispatcher::i()->databaseId ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_revision_button_view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<a data-confirm href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->csrf()->setQueryString( array( 'do' => 'revisionDelete', 'revision_id' => $row['revision_id'], 'd' => \IPS\cms\Databases\Dispatcher::i()->databaseId, 'ajax' => \IPS\Request::i()->isAjax() ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_revision_button_delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			</div>
		</header>
		<div class='ipsEntry__post'>
			
IPSCONTENT;

$gotDiffs = FALSE;
$return .= <<<IPSCONTENT

			<i-data>
				<ul class='ipsData ipsData--table ipsData--revisions-rows'>
					
IPSCONTENT;

foreach ( $row['revision_data'] as $key => $diff ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<div class='ipsData__main'>
								
IPSCONTENT;

if ( $diff['original'] != $diff['current'] ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$gotDiffs = TRUE;
$return .= <<<IPSCONTENT

									<h3 class='ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $diff['field']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
									
IPSCONTENT;

if ( !\in_array( $diff['field']->type, array( 'Editor', 'CodeMirror', 'TextArea' ) ) ):
$return .= <<<IPSCONTENT

										<div class='ipsSpanGrid' data-key='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['revision_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-method='diff'>
											<div class='ipsSpanGrid__6 i-padding_3'>
												<h3 class='ipsTitle ipsTitle--h3'>Revision</h3>
												<div data-original='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['revision_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $diff['field']->displayValue( $diff['original'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
											</div>
											<div class='ipsSpanGrid__6 i-padding_3'>
												<h3 class='ipsTitle ipsTitle--h3'>Current</h3>
												<div data-current='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['revision_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $diff['field']->displayValue( $diff['current'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
											</div>
										</div>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<div class='ipsSpanGrid'>
											<div class='ipsSpanGrid__6 i-margin-bottom_2'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_record_revision_title_revision', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></div>
											<div class='ipsSpanGrid__6 i-margin-bottom_2'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_record_revision_title_record', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></div>
										</div>

										<div class='ipsPagesDiff' data-key='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['revision_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-method='merge'></div>
										<textarea class='ipsInput ipsInput--text ipsHide' data-original='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['revision_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $diff['original'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
										<textarea class='ipsInput ipsInput--text ipsHide' data-current='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['revision_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $diff['current'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( ! $gotDiffs ):
$return .= <<<IPSCONTENT

						<li>
							<p class="ipsMessage ipsMessage--info">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'record_no_revision_data', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
		</div>
	</article>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function table( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ! \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<header class='ipsPageHeader ipsBox ipsPull ipsPageHeader--revisions-table'>
	<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_view_revisions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
</header>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='ipsBox' data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='cms.front.records.revisions'>
	
IPSCONTENT;

if ( $table->title ):
$return .= <<<IPSCONTENT

		<h2 class='ipsBox__header'>
IPSCONTENT;

$val = "{$table->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
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

		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_view_revisions_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

</div>
IPSCONTENT;

		return $return;
}

	function view( $record, $revision, $conflicts, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $formClass='', $attributes=array(), $sidebar='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class="ipsPageHeader ipsPageHeader--revisions-view">
	<h1 class="ipsPageHeader__title">
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts($revision->date)->relative(), $record->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_record_revision_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
</header>
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
 data-ipsForm class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
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
>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \is_array($v) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $v as $_v ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
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

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	<ol data-controller='cms.front.records.revisions'>
		
IPSCONTENT;

foreach ( $conflicts as $key => $data ):
$return .= <<<IPSCONTENT

		<li>
			<h2 class="ipsTitle ipsTitle--h4 i-padding_3 i-background_3">
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['field']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</h2>
			<i-data>
				<ol class='ipsData ipsData--table ipsData--category' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $record->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="tableRows">
					<li class="ipsData__item">
					
IPSCONTENT;

if ( isset( $data['diff']) OR $data['original'] != $data['current'] ):
$return .= <<<IPSCONTENT

						<div class="ipsData__main">
							<table class="ipsTable diff restrict_height">
								<tr>
									<th><span data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-conflict-name="old">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_record_revision_title_revision', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></th>
									<th><span data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-conflict-name="new">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_record_revision_title_record', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </span></th>
								</tr>
							</table>
							<div data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

if ( !\in_array( $data['field']->type, array( 'Editor', 'CodeMirror', 'TextArea' ) ) ):
$return .= <<<IPSCONTENT

								<div class='ipsSpanGrid' data-key='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-method='diff'>
									<div class='ipsSpanGrid__6 i-padding_3' data-original='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['field']->displayValue( $data['original'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
									<div class='ipsSpanGrid__6 i-padding_3' data-current='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['field']->displayValue( $data['current'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
								</div>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<div class='ipsPagesDiff' data-key='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-method='merge'></div>
								<textarea class='ipsInput ipsInput--text ipsHide' data-original='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['original'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
								<textarea class='ipsInput ipsInput--text ipsHide' data-current='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['current'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
							
IPSCONTENT;

if ( \count($elements) ):
$return .= <<<IPSCONTENT

								<div class="i-background_3 i-padding_3">
									<div class='ipsSpanGrid'>
										<div class='ipsSpanGrid__6'>
											<span class='' data-conflict-name="old">
												
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

foreach ( $collection as $name => $input ):
$return .= <<<IPSCONTENT

														
IPSCONTENT;

if ( $name == 'conflict_' . $data['field']->id ):
$return .= <<<IPSCONTENT

															
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->radio( $name, 'old', FALSE, array( 'old' => \IPS\Member::loggedIn()->language()->addToStack('content_conflict_use_this_revision') ), FALSE, array(), array(), array(), '', NULL, NULL, 'oldrev' );
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

											</span>
										</div>
										<div class='ipsSpanGrid__6'>
											<span data-conflict-name="new">
												
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

foreach ( $collection as $name => $input ):
$return .= <<<IPSCONTENT

														
IPSCONTENT;

if ( $name == 'conflict_' . $data['field']->id ):
$return .= <<<IPSCONTENT

															
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->radio( $name, NULL, FALSE, array( 'new' => \IPS\Member::loggedIn()->language()->addToStack('content_conflict_use_this_record') ), FALSE, array(), array(), array(), '', NULL, NULL, 'newrev' );
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

											</span>
										</div>
									</div>
								</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class="ipsData__main">
							{$data['revision']}
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				</ol>
			</i-data>
		</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ol>	
	<div class="i-background_2 i-padding_3 i-text-align_center">
		
IPSCONTENT;

$return .= implode( '', $actionButtons);
$return .= <<<IPSCONTENT

	</div>
</form>
IPSCONTENT;

		return $return;
}}