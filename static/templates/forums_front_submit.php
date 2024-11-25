<?php
namespace IPS\Theme;
class class_forums_front_submit extends \IPS\Theme\Template
{	function createTopic( $form, $forum, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $forum->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $forum );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id='elClubContainer'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack( $title ) );
endif;
$return .= <<<IPSCONTENT


{$form}


IPSCONTENT;

if ( $club = $forum->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function createTopicForm( $forum, $hasModOptions, $topic, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL, $errorTabs=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$modOptions = array( 'topic_create_state', 'create_topic_locked', 'create_topic_pinned', 'create_topic_hidden', 'create_topic_featured', 'topic_open_time', 'topic_close_time');
$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="ipsFormWrap" action="
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
 data-ipsForm data-ipsFormSubmit>
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

if ( $form->error ):
$return .= <<<IPSCONTENT

		<p class="ipsMessage ipsMessage--error i-margin-bottom_block">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( !empty( $errorTabs ) ):
$return .= <<<IPSCONTENT

		<p class="ipsMessage ipsMessage--error i-margin-bottom_block ipsJS_show">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tab_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class='ipsBox ipsPull'>
		
IPSCONTENT;

if ( \count( $elements ) > 1 ):
$return .= <<<IPSCONTENT

			<i-tabs class='ipsTabs' id='ipsTabs_topicForm' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_topicForm_content'>
				<div role='tablist'>
					
IPSCONTENT;

foreach ( $elements as $name => $content ):
$return .= <<<IPSCONTENT

						<button type="button" id='ipsTabs_topicForm_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsTabs__tab 
IPSCONTENT;

if ( \in_array( $name, $errorTabs ) ):
$return .= <<<IPSCONTENT
ipsTabs__tab--error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" role="tab" aria-controls="ipsTabs_topicForm_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( $name == 'topic_mainTab' ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

if ( \in_array( $name, $errorTabs ) ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle-exclamation"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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
			<div id='ipsTabs_topicForm_content' class='ipsTabs__panels'>
				
IPSCONTENT;

foreach ( $elements as $name => $contents ):
$return .= <<<IPSCONTENT

					<div id='ipsTabs_topicForm_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel ipsTabs__panel--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" role="tabpanel" aria-labelledby="ipsTabs_topicForm_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $name != 'topic_mainTab' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>

						
IPSCONTENT;

if ( $hasModOptions && $name == 'topic_mainTab' ):
$return .= <<<IPSCONTENT

							<div class='ipsColumns ipsColumns--new-topic ipsColumns--lines'>
								<div class='ipsColumns__primary'>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<ul class='ipsForm ipsForm--vertical 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--new-topic'>
								
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
								<div class='ipsColumns__secondary i-basis_300'>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "submit", "forums" )->createTopicModOptions( $elements, $modOptions );
$return .= <<<IPSCONTENT

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

			<div class=''>
				
IPSCONTENT;

if ( $hasModOptions ):
$return .= <<<IPSCONTENT

					<div class='ipsColumns ipsColumns--new-topic ipsColumns--lines'>
						<div class='ipsColumns__primary'>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<ul class='ipsForm ipsForm--vertical 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--new-topic'>
						
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
						<div class='ipsColumns__secondary i-basis_300'>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "submit", "forums" )->createTopicModOptions( $elements, $modOptions );
$return .= <<<IPSCONTENT

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
			
IPSCONTENT;

if ( $topic ):
$return .= <<<IPSCONTENT

			<button type='submit' class='ipsButton ipsButton--primary' tabindex="1" accesskey="s" role="button">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_topic_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<button type='submit' class='ipsButton ipsButton--primary' tabindex="1" accesskey="s" role="button">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>	
</form>
IPSCONTENT;

		return $return;
}

	function createTopicModOptions( $elements, $modOptions ) {
		$return = '';
		$return .= <<<IPSCONTENT


<h3 class='i-padding_3 i-border-bottom_3 ipsTitle ipsTitle--h5' hidden>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_moderator_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<ul class='ipsForm ipsForm--vertical ipsForm--topic-mod-options'>
	
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( $inputName, $modOptions ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $inputName == 'topic_open_time' or $inputName == 'topic_close_time' ):
$return .= <<<IPSCONTENT

					<li class='ipsFieldRow'>
						<label class="ipsFieldRow__label" for="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$input->name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						<ul class='ipsFieldRow__content cCreateTopic_date'>
							<li>
								<input type="date" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput" data-control="date" placeholder='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( 'YYYY', 'MM', 'DD' ), array( \IPS\Member::loggedIn()->language()->addToStack('_date_format_yyyy'), \IPS\Member::loggedIn()->language()->addToStack('_date_format_mm'), \IPS\Member::loggedIn()->language()->addToStack('_date_format_dd') ), str_replace( 'Y', 'YY', \IPS\Member::loggedIn()->language()->preferredDateFormat() ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value="
IPSCONTENT;

if ( $input->value instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->value->format('Y-m-d'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-preferredFormat="
IPSCONTENT;

if ( $input->value instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->value->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							</li>
							<li>
								<input name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_time" type="time" size="12" class="ipsInput" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( '_time_format_hhmm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" step="60" min="00:00" value="
IPSCONTENT;

if ( $input->value instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->value->format('H:i'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							</li>
						</ul>
					</li>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					{$input}
				
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

</ul>

IPSCONTENT;

		return $return;
}}