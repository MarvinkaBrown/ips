<?php
namespace IPS\Theme;
class class_cms_front_submit extends \IPS\Theme\Template
{	function createRecord( $form, $category, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack( $title ) );
endif;
$return .= <<<IPSCONTENT

<hr class='ipsHr'>
{$form}
IPSCONTENT;

		return $return;
}

	function createRecordForm( $record, $category, $hasModOptions, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$modOptions = array( 'create_record_state', 'record_publish_date', 'record_expiry_date', 'record_on_homepage', 'record_pinned', 'record_allow_comments', 'record_comment_cutoff' );
$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--create-record" action="
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

if ( $hasModOptions ):
$return .= <<<IPSCONTENT

		<div class='ipsColumns'>
			<div class='ipsColumns__primary'>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class='ipsBox'>
		<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		
IPSCONTENT;

if ( \count( $elements ) > 1 ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !empty( $errorTabs ) ):
$return .= <<<IPSCONTENT

				<p class="ipsMessage ipsMessage--error ipsJS_show">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tab_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<i-tabs class='ipsTabs' id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content'>
				<div role='tablist'>
					
IPSCONTENT;

$checkedTab = NULL;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $elements as $name => $content ):
$return .= <<<IPSCONTENT

						<a href='#ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( !$checkedTab ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

$checkedTab = $name;
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

			</i-tabs>
			<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels ipsTabs__panels--padded'>
				
IPSCONTENT;

foreach ( $elements as $name => $contents ):
$return .= <<<IPSCONTENT

					<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"  
IPSCONTENT;

if ( $checkedTab != $name ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
						
IPSCONTENT;

if ( $hasModOptions && $name == 'topic_mainTab' ):
$return .= <<<IPSCONTENT

							<div class='ipsColumns'>
								<div class='ipsColumns__primary'>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--create-record'>
								
IPSCONTENT;

foreach ( $contents as $inputName => $input ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( !\in_array( $inputName, $modOptions ) ):
$return .= <<<IPSCONTENT

										{$input}
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</ul>
						
IPSCONTENT;

if ( $hasModOptions && $name == 'topic_mainTab' ):
$return .= <<<IPSCONTENT

								</div>
								<div class='ipsColumns__secondary i-basis_280'>
									<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_moderator_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
									<div class='i-background_2 i-padding_3'>
										<ul class='ipsForm ipsForm--vertical ipsForm--create-record-mod'>
											
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

if ( \in_array( $inputName, $modOptions ) ):
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

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>		
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='i-padding_3'>
				
IPSCONTENT;

if ( $hasModOptions ):
$return .= <<<IPSCONTENT

					<div class='ipsColumns'>
						<div class='ipsColumns__primary'>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--create-record'>
						
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( !\in_array( $inputName, $modOptions ) ):
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
				
IPSCONTENT;

if ( $hasModOptions ):
$return .= <<<IPSCONTENT

						</div>
						<div class='ipsColumns__secondary i-basis_280'>
							<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_moderator_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
							<div class='i-background_2 i-padding_3'>
								<ul class='ipsForm ipsForm--vertical ipsForm--create-record-mod'>
									
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( \in_array( $inputName, $modOptions ) ):
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

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsSubmitRow'>
			<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_record_form_save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function topic( $record ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $record->topicFields() as $id => $field ):
$return .= <<<IPSCONTENT

{$field}

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

<br>
<p><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $record->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($record::database()->recordWord( 1 )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_view_record', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></p>
IPSCONTENT;

		return $return;
}}