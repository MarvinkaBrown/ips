<?php
namespace IPS\Theme;
class class_cms_front_records extends \IPS\Theme\Template
{	function categorySelector( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3'>
	{$form}
</div>
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

if ( $input->name == 'guest_name' or $input->name == 'guest_email' ):
$return .= <<<IPSCONTENT

						<ul class='ipsForm ipsForm--vertical ipsForm--guest-comment'>
							<li class='ipsFieldRow ipsFieldRow--fullWidth i-padding_0 i-margin-bottom_2'>
								<div class="ipsFieldRow__content">{$input->html()}</div>
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

							<br>
							<span class="i-color_warning">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
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

if ( !($input instanceof \IPS\Helpers\Form\Editor) && $input->name != 'guest_name' && $input->name != 'guest_email' and ! mb_stristr( $input->name, 'content_field_' ) ):
$return .= <<<IPSCONTENT

								<li class='
IPSCONTENT;

if ( !($input instanceof \IPS\Helpers\Form\Captcha) ):
$return .= <<<IPSCONTENT
ipsComposeArea_formControl
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>{$input->html()}</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( mb_stristr( $input->name, 'content_field_' ) ):
$return .= <<<IPSCONTENT

							{$input->rowHtml()}
							
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
}}