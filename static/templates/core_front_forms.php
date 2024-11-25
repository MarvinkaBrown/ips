<?php
namespace IPS\Theme;
class class_core_front_forms extends \IPS\Theme\Template
{	function colorSelection( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


<ul class='i-flex i-flex-wrap_wrap i-gap_1 cColorChoices'>
	
IPSCONTENT;

foreach ( array('general', 'warning', 'error', 'success' ) as $type ):
$return .= <<<IPSCONTENT

		<li data-ipsTooltip title='
IPSCONTENT;

$val = "message_type_{$type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			<input type='radio' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $value === $type ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<span class='cColorChoice_chooser ipsMessage--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></span>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function commentTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$minimized = false;
$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--comment" action="
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

	<div data-role='whosTyping' class='ipsHide i-margin-bottom_2'></div>
	<div class='ipsComposeArea'>
		<div class='ipsComposeArea__photo'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::loggedIn(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
		<div class='ipsComposeArea_editor'>
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $input->name == 'guest_name' or $input->name == 'guest_email' or $input instanceof \IPS\Helpers\Form\Select ):
$return .= <<<IPSCONTENT

						<ul class='ipsForm ipsForm--vertical ipsForm--guest-post' data-ipsEditor-toolList>
							<li class='ipsFieldRow ipsFieldRow--fullWidth i-padding_0 i-margin-bottom_2'>
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

if ( !($input instanceof \IPS\Helpers\Form\Select) && !($input instanceof \IPS\Helpers\Form\Editor) && $input->name != 'guest_name' && $input->name != 'guest_email' && $input->name != 'assign_content_to' ):
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

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				        
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

				            
IPSCONTENT;

if ( $input->name == 'assign_content_to' ):
$return .= <<<IPSCONTENT

				                <li>
IPSCONTENT;

if ( $input->value ):
$return .= <<<IPSCONTENT
<span class='ipsHide i-font-weight_bold i-color_soft' data-role='assignmentPrefix'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
{$input->html()}</li>
				            
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
	<div class='ipsComposeArea__photo'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::loggedIn(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
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

	function createItemUnavailable( $lang, $warnings ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 


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

IPSCONTENT;

		return $return;
}

	function editContentForm( $title, $form, $container=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $container and \IPS\IPS::classUsesTrait( $container, 'IPS\Content\ClubContainer' ) and $club = $container->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $container );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

/*
$return .= <<<IPSCONTENT

<!-- This form is used for editing certain types of content: Downloads, Courses, etc -->

IPSCONTENT;

*/
$return .= <<<IPSCONTENT


<header class="ipsPageHeader ipsPageHeader--edit-content">
	<h1 class="ipsPageHeader__title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
</header>
<div data-el='edit-content-form' class='ipsBox'>{$form}</div>
IPSCONTENT;

		return $return;
}

	function editTagsForm( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ips-template="editTagsForm">
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function emptyRow( $contents, $id=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsFieldRow' 
IPSCONTENT;

if ( $id ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	{$contents}
</li>
IPSCONTENT;

		return $return;
}

	function header( $lang, $id=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li 
IPSCONTENT;

if ( $id !== NULL ):
$return .= <<<IPSCONTENT
 id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<h4 class='ipsFieldRow__section'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
</li>
IPSCONTENT;

		return $return;
}

	function popupRegisterTemplate( $login, $postBeforeRegister, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() AND $buttonMethods = $login->buttonMethods() ):
$return .= <<<IPSCONTENT

	<div id='elRegisterSocial' class='i-border-bottom_1 i-padding_3'>
		<h2 class='ipsTitle ipsTitle--h4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_start_faster', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_connect', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<form data-bypassValidation='true' accept-charset='utf-8' method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<div class='i-flex i-flex-wrap_wrap i-gap_2 i-margin-top_3'>
				
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

					<div class="i-flex_11">
						{$method->button()}
					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</form>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--register-popup" action="
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
 data-ipsForm data-el='register-pop-up'>
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

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
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

if ( \count( $form->elements ) < 2 ):
$return .= <<<IPSCONTENT

		<div class="">
			
IPSCONTENT;

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

				<div class='ipsColumns'>
					<div class='ipsColumns__primary'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<ul class=''>
					
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \is_object( $input )  ):
$return .= <<<IPSCONTENT

								{$input->rowHtml($form)}
							
IPSCONTENT;

else:
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

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsColumns__secondary i-basis_180'>
						
IPSCONTENT;

$return .= array_pop( $sidebar );
$return .= <<<IPSCONTENT

					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

else:
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

foreach ( $elements as $name => $content ):
$return .= <<<IPSCONTENT

					<button type="button" id='ipsTabs_
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

if ( $name == \IPS\Widget\Request::i()->tab ):
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

foreach ( $content as $element ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !\is_string( $element ) and $element->error ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle-exclamation"></i> 
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
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
		<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels'>
			
IPSCONTENT;

foreach ( $elements as $name => $content ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $name != \IPS\Widget\Request::i()->tab ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<ul>
						
IPSCONTENT;

foreach ( $content as $input ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \is_object( $input )  ):
$return .= <<<IPSCONTENT

								{$input->rowHtml($form)}
							
IPSCONTENT;

else:
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
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsSubmitRow">
		<ul class='ipsButtons'>
			<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->button( 'register_button', 'submit', null, 'ipsButton ipsButton--primary', array( 'tabindex' => '2', 'accesskey' => 's' ) );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

if ( $postBeforeRegister ):
$return .= <<<IPSCONTENT

				<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register&do=cancelPostBeforeRegister&id={$postBeforeRegister['id']}&pbr={$postBeforeRegister['secret']}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit' data-ipsPbrCancel="true">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_before_register_delete_submission', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function popupTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax()  ):
$return .= <<<IPSCONTENT
<div class="ipsBox">
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm ipsForm--popUpTemplate 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" action="
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
 data-ipsForm>
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

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
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

		<div class="ipsMessage ipsMessage--error">
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $form->elements ) < 2 ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

			<div class='ipsColumns'>
				<div class='ipsColumns__primary'>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<ul class=''>
				
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \is_object( $input )  ):
$return .= <<<IPSCONTENT

							{$input->rowHtml($form)}
						
IPSCONTENT;

else:
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

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

				</div>
				<div class='ipsColumns__secondary i-basis_180'>
					
IPSCONTENT;

$return .= array_pop( $sidebar );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
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

foreach ( $elements as $name => $content ):
$return .= <<<IPSCONTENT

					<button type="button" id='ipsTabs_
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

if ( $name == \IPS\Widget\Request::i()->tab ):
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

foreach ( $content as $element ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !\is_string( $element ) and $element->error ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle-exclamation"></i> 
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
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
		<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels'>
			
IPSCONTENT;

foreach ( $elements as $name => $content ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $name != \IPS\Widget\Request::i()->tab ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<ul>
						
IPSCONTENT;

foreach ( $content as $input ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \is_object( $input )  ):
$return .= <<<IPSCONTENT

								{$input->rowHtml($form)}
							
IPSCONTENT;

else:
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
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<ul class="ipsSubmitRow ipsButtons">
		
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

			<li>{$button}</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</form>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax()  ):
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function postingInformation( $guestPostBeforeRegister, $modQueued, $standalone=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !$standalone ):
$return .= <<<IPSCONTENT

		<div class='cGuestTeaser'>
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_post_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<p>
				
IPSCONTENT;

if ( $guestPostBeforeRegister ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_post_pbr_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_post_normal_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_post_sign_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $modQueued ):
$return .= <<<IPSCONTENT

					<br><span class='i-color_warning'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_post_mod_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
	
IPSCONTENT;

if ( !$standalone ):
$return .= <<<IPSCONTENT

			<hr class="ipsHr i-margin-block_3">
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

elseif ( $modQueued ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--warning">
		<h4 class='i-font-weight_600'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mod_queue_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		
IPSCONTENT;

$warnings = \IPS\Member::loggedIn()->warnings( 1, NULL, 'mq' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $warnings ) and \IPS\Member::loggedIn()->mod_posts > time() ):
$return .= <<<IPSCONTENT

			<p class="i-color_soft">
				
IPSCONTENT;

if ( \count( $warnings ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_will_be_moderated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( \IPS\Member::loggedIn()->mod_posts )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restriction_ends', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsButtons'>
			
IPSCONTENT;

foreach ( $warnings as $idx => $warning ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--small ipsButton--inherit' data-ipsDialog data-ipsDialog-size='narrow'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_warning_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	</div>	

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function profileCompleteTemplate( $step, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--complete-profile" action="
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
 data-ipsForm>
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

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
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

if ( \count( $form->elements ) < 2 ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists( 'profile_step_text_' . $step->id ) and \IPS\Member::loggedIn()->language()->words[ 'profile_step_text_' . $step->id ] ):
$return .= <<<IPSCONTENT

			<div class='i-padding_3'>
				<div class="ipsMessage ipsMessage--info">
					
IPSCONTENT;

$val = "profile_step_text_{$step->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="i-padding_3">
			
IPSCONTENT;

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

				<div class='ipsColumns'>
					<div class='ipsColumns__primary'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<ul>
					
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \is_object( $input )  ):
$return .= <<<IPSCONTENT

								{$input->rowHtml($form)}
							
IPSCONTENT;

else:
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

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsColumns__secondary i-basis_180'>
						
IPSCONTENT;

$return .= array_pop( $sidebar );
$return .= <<<IPSCONTENT

					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists( 'profile_step_text_' . $step->id ) and \IPS\Member::loggedIn()->language()->words[ 'profile_step_text_' . $step->id ] ):
$return .= <<<IPSCONTENT

		<div class='i-padding_3'>
			<div class="ipsMessage ipsMessage--info">
				
IPSCONTENT;

$val = "profile_step_text_{$step->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
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

foreach ( $elements as $name => $content ):
$return .= <<<IPSCONTENT

					<button type="button" id='ipsTabs_
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

if ( $name == \IPS\Widget\Request::i()->tab ):
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

foreach ( $content as $element ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !\is_string( $element ) and $element->error ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle-exclamation"></i> 
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
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
		<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels ipsTabs__panels--padded'>
			
IPSCONTENT;

foreach ( $elements as $name => $content ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $name != \IPS\Widget\Request::i()->tab ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<ul>
						
IPSCONTENT;

foreach ( $content as $input ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \is_object( $input )  ):
$return .= <<<IPSCONTENT

								{$input->rowHtml($form)}
							
IPSCONTENT;

else:
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
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<ul class="ipsSubmitRow ipsButtons">
		
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

			<li>{$button}</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !$step->required OR $step->completed() ):
$return .= <<<IPSCONTENT

			<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action->setQueryString('_moveToStep', $step->getNextStep()), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="wizardLink" class="ipsButton ipsButton--text ipsJS_none">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_complete_skip_step', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings", null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_wizard_cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
</form>
IPSCONTENT;

		return $return;
}

	function ratingTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--rating" action="
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
 data-ipsForm data-controller="core.front.core.rating">
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

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

			{$input->html()}
		
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

	function recommendCommentTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--recommended" action="
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
 data-ipsForm>
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

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
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

	<div class="i-padding_3">
		<ul>
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \is_object( $input )  ):
$return .= <<<IPSCONTENT

						{$input->rowHtml($form)}
					
IPSCONTENT;

else:
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
	<ul class="ipsSubmitRow ipsButtons">
		
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

			<li>{$button}</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</form>
IPSCONTENT;

		return $return;
}

	function reviewTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsComposeArea i-padding_3' 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT
data-controller='core.front.core.reviewForm'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<div class='ipsComposeArea__photo'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::loggedIn(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
	<div class='ipsComposeArea_editor'>
		<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--review" action="
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

				<div data-role='reviewIntro'>
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

			<ul class='ipsForm ipsForm--vertical ipsForm--review 
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->_review ) and \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='reviewForm'>
				
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
		</form>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function reviewUnavailable( $lang, $warnings=array(), $ends=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsPhotoPanel'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::loggedIn(), 'medium' );
$return .= <<<IPSCONTENT

	<div class='ipsPhotoPanel__text'>
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
}

	function row( $label, $element, $desc, $warning, $required=FALSE, $error=NULL, $prefix=NULL, $suffix=NULL, $id=NULL, $object=NULL, $form=NULL, $rowClasses=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsFieldRow
IPSCONTENT;

if ( $object === NULL ):
$return .= <<<IPSCONTENT
 ipsFieldRow_textValue
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $object instanceof \IPS\Helpers\Form\Checkbox and !( $object instanceof \IPS\Helpers\Form\YesNo ) ):
$return .= <<<IPSCONTENT
 ipsFieldRow--checkbox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT
ipsFieldRow_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $rowClasses ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode(' ', $rowClasses), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $id ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( $object instanceof \IPS\Helpers\Form\Checkbox and !( $object instanceof \IPS\Helpers\Form\YesNo ) ):
$return .= <<<IPSCONTENT

		{$prefix}
		{$element}
		{$suffix}
		<div class='ipsFieldRow__content'>
			<label class='ipsFieldRow__label' for='check_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span>{$label}</span> 
IPSCONTENT;

if ( $required ):
$return .= <<<IPSCONTENT
<span class='ipsFieldRow__required'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</label>
			{$desc}
			{$warning}
			
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

				<br>
				<span class="i-color_warning">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $label ):
$return .= <<<IPSCONTENT

			<label class='ipsFieldRow__label' 
IPSCONTENT;

if ( $object !== NULL AND $object->getLabelForAttribute() !== NULL ):
$return .= <<<IPSCONTENT
for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $object->getLabelForAttribute(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<span>{$label}</span> 
IPSCONTENT;

if ( $required ):
$return .= <<<IPSCONTENT
<span class='ipsFieldRow__required'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</label>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsFieldRow__content' 
IPSCONTENT;

if ( $object instanceof \IPS\Helpers\Form\Text && !$required && $object->options['autocomplete'] !== NULL && !$object->value ):
$return .= <<<IPSCONTENT
data-controller='core.global.core.optionalAutocomplete'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			{$prefix}
			{$element}
			{$suffix}
			{$desc}
			{$warning}
			
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

				<br>
				<span class="i-color_warning">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>
IPSCONTENT;

		return $return;
}

	function select( $name, $value, $required, $options, $multiple=FALSE, $class='', $disabled=FALSE, $toggles=array(), $id=NULL, $unlimited=NULL, $unlimitedLang='all', $unlimitedToggles=array(), $toggleOn=TRUE, $userSuppliedInput='', $sort=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT

	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="__EMPTY">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$userInput = array_diff( ( \is_array( $value ) ? $value : array( $value ) ), array_keys( $options ) );
$return .= <<<IPSCONTENT

<select name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--select 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT
multiple
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
required aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled === TRUE ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $id !== NULL ):
$return .= <<<IPSCONTENT
id="elSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $sort ):
$return .= <<<IPSCONTENT
data-sort
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

foreach ( $options as $k => $v ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \is_array( $v ) ):
$return .= <<<IPSCONTENT

			<optgroup label="
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

					<option value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( ( ( $value === 0 and $_k === 0 ) or ( $value !== 0 and $value === $_k ) ) or ( \is_array( $value ) and \in_array( $_k, $value ) ) ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $toggles[ $_k ] ) ):
$return .= <<<IPSCONTENT
data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles[ $_k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-controls="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles[ $_k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>{$_v}</option>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</optgroup>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<option value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'  
IPSCONTENT;

if ( ( ( $value === 0 and $k === 0 ) or ( $value !== 0 and $value === $k ) or ( $value !== 0 and \is_numeric( $value ) and \is_numeric( $k ) and $value == $k ) ) or ( \is_array( $value ) and \in_array( $k, $value ) ) or ( !empty( $userSuppliedInput ) and !\is_array( $value ) and !\in_array( $value, array_keys( $options ) ) and $k == $userSuppliedInput ) ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \is_array( $disabled ) and \in_array( $k, $disabled ) ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $toggles[ $k ] ) ):
$return .= <<<IPSCONTENT
data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles[ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>{$v}</option>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</select>

IPSCONTENT;

if ( $userSuppliedInput ):
$return .= <<<IPSCONTENT

<div id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $userSuppliedInput, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-margin-top_1'>
    <input type='text' class='ipsInput ipsInput--text' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $userSuppliedInput, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value="
IPSCONTENT;

if ( \count( $userInput ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' , ', $userInput ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $unlimited !== NULL ):
$return .= <<<IPSCONTENT

	<br><br>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	&nbsp;
	<input type="checkbox" data-control="unlimited
IPSCONTENT;

if ( \count($unlimitedToggles) ):
$return .= <<<IPSCONTENT
 toggle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" name="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( trim( $name, '[]' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited" id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( trim( $id ?: $name, '[]' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimited, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $unlimited === $value ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled === TRUE ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \count( $unlimitedToggles ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $toggleOn === FALSE ):
$return .= <<<IPSCONTENT
data-togglesOff
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-togglesOn
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $unlimitedToggles ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-controls="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $unlimitedToggles ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( trim( $id ?: $name, '[]' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited_label' class="ipsInput ipsInput--toggle">
	<label for='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( trim( $id ?: $name, '[]' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( trim( $id ?: $name, '[]' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited_label' class='ipsField_unlimited'>
IPSCONTENT;

$val = "{$unlimitedLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function seperator(   ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li>
	<hr class='ipsHr'>
</li>
IPSCONTENT;

		return $return;
}

	function template( $id, $action, $tabs, $activeTab, $error, $errorTabs, $hiddenValues, $actionButtons, $uploadField, $sidebar, $tabClasses=array(), $formClass='', $attributes=array(), $tabArray=array(), $usingIcons=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" accept-charset='utf-8' 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsForm class="ipsFormWrap ipsFormWrap--template" 
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
 
IPSCONTENT;

if ( \count($tabArray) > 1 ):
$return .= <<<IPSCONTENT
novalidate="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-template='front/forms/template'>
	
IPSCONTENT;

 /* 
$return .= <<<IPSCONTENT
<!-- This is used for certain forms, such as "Create a blog" -->
IPSCONTENT;

 */ 
$return .= <<<IPSCONTENT

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

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
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

if ( $error ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--error">
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $tabs ) < 2 ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

			<div class='ipsColumns ipsColumns--compose-form ipsColumns--lines'>
				<div class='ipsColumns__primary'>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--template'>
						
IPSCONTENT;

$return .= array_pop( $tabs );
$return .= <<<IPSCONTENT

						<li class='ipsSubmitRow'>
							
IPSCONTENT;

$return .= implode( '', $actionButtons);
$return .= <<<IPSCONTENT

						</li>
					</ul>
		
IPSCONTENT;

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

				</div>
				<div class='ipsColumns__secondary'>
					
IPSCONTENT;

$return .= array_pop( $sidebar );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
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

		<i-tabs class='ipsTabs
IPSCONTENT;

if ( $usingIcons ):
$return .= <<<IPSCONTENT
 ipsTabs--withIcons
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' id='ipsTabs_
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

foreach ( $tabs as $name => $content ):
$return .= <<<IPSCONTENT

					<button type="button" id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
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
" role="tab" aria-controls="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( $activeTab == $name ):
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

if ( isset($tabArray[$name]['icon']) ):
$return .= <<<IPSCONTENT
<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabArray[$name]['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
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
		<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels'>
			
IPSCONTENT;

foreach ( $tabs as $name => $contents ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel ipsTabs__panel--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" role="tabpanel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $activeTab != $name ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

if ( isset( $sidebar[ $name ] ) ):
$return .= <<<IPSCONTENT

						<div class='ipsColumns ipsColumns--compose-form ipsColumns--lines'>
							<div class='ipsColumns__primary'>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--template 
IPSCONTENT;

if ( isset( $tabClasses[ $name ] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabClasses[ $name ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									<li class='ipsJS_hide'>
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
									{$contents}
								</ul>
					
IPSCONTENT;

if ( isset( $sidebar[ $name ] ) ):
$return .= <<<IPSCONTENT

							</div>
							<div class='ipsColumns__secondary'>
								{$sidebar[ $name ]}
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
		<div class="ipsSubmitRow">
			
IPSCONTENT;

$return .= implode( '', $actionButtons);
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</form>
IPSCONTENT;

		return $return;
}

	function uploadNoScript( $name, $value, $required, $multiple ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}}