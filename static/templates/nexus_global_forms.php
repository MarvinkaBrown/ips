<?php
namespace IPS\Theme;
class class_nexus_global_forms extends \IPS\Theme\Template
{	function addPaymentMethodForm( $showSubmitButton, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsBox">
	<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--add-payment" action="
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

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--add-payment'>
					
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

						{$input->rowHtml()}
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
		
		
IPSCONTENT;

if ( $showSubmitButton ):
$return .= <<<IPSCONTENT

			<div class='i-background_2 i-padding_3 i-text-align_center' id="paymentMethodSubmit">
				<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</form>
</div>
IPSCONTENT;

		return $return;
}

	function businessAddress( $name, $value, $googleApiKey, $minimize=FALSE, $requireFullAddress=TRUE, $htmlId=NULL, $vat=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class="ipsFieldList" data-ipsAddressForm 
IPSCONTENT;

if ( $googleApiKey ):
$return .= <<<IPSCONTENT
data-ipsAddressForm-googlePlaces data-ipsAddressForm-googleApiKey="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $googleApiKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsAddressForm-requireFullAddress="
IPSCONTENT;

if ( $requireFullAddress ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $minimize ):
$return .= <<<IPSCONTENT
data-ipsAddressForm-minimize
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $value->country AND !$value->city AND !$value->region AND !$value->postalCode ):
$return .= <<<IPSCONTENT
 data-ipsAddressForm-country="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->country, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-controller="nexus.global.forms.businessAddressVat">
	<li>
		<ul class="ipsFieldList" role="radiogroup" id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_type">
			<li>
				<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[type]" value="consumer" data-role="addressTypeRadio" 
IPSCONTENT;

if ( !isset( $value->business ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_type_consumer_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
				<div class='ipsFieldList__content'>
					<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_type_consumer_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_type_label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_address_consumer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				</div>
			</li>
			<li>
				<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[type]" value="business" data-role="addressTypeRadio" 
IPSCONTENT;

if ( isset( $value->business ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_type_business_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-control="toggle" data-toggles="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_businessName" class="ipsInput ipsInput--toggle">
				<div class='ipsFieldList__content'>
					<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_type_business_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_type_label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_address_business', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				</div>
			</li>
		</ul>
	</li>	
	<li>
		<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[country]" data-role="countrySelect" data-sort>
			<option value='' 
IPSCONTENT;

if ( !$value->country OR (!$value->city AND !$value->region AND !$value->postalCode) ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'country', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
			
IPSCONTENT;

foreach ( \IPS\GeoLocation::$countries as $k ):
$return .= <<<IPSCONTENT

				<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $k == $value->country AND ( ( $value->city AND ( $value->postalCode OR $value->region ) ) OR !$minimize ) ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

$val = "country-{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</option>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</select>
	</li>
	<li id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_businessName">
		<input type="text" class="ipsInput ipsInput--text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[business]" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_business_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;

if ( isset( $value->business ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->business, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="business" autocomplete="organization">
	</li>
	
IPSCONTENT;

foreach ( $value->addressLines as $i => $line ):
$return .= <<<IPSCONTENT

		<li>
			<input type="text" class="ipsInput ipsInput--text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[address][]" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_line', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $line, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="addressLine">
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	<li>
		<input type="text" class="ipsInput ipsInput--text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[city]" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'city', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->city, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="city" autocomplete="address-level2">
	</li>
	<li>
		<input type="text" class="ipsInput ipsInput--text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[region]" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'region', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->region, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="regionText" autocomplete="address-level1">
	</li>
	<li>
		<input type="text" class="ipsInput ipsInput--text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[postalCode]" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'zip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->postalCode, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="postalCode" autocomplete="postal-code">
	</li>
	
IPSCONTENT;

if ( $vat ):
$return .= <<<IPSCONTENT

		<li data-role="vatField" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_vat">
			<input type="text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[vat]" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_checkout_vat_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;

if ( isset( $value->vat ) and $value->vat ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->vat, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="vat" class="ipsInput ipsInput--text ipsFieldRow_errorExclude">
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function combined( $name, $field1, $field2 ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="">
<div class="i-flex i-flex-wrap_wrap i-gap_3">
	<div>
		{$field1->html()}
	</div>
	<div>
		<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unit">
			{$field2->html()}
			
IPSCONTENT;

if ( $name === 'f_maxmind' ):
$return .= <<<IPSCONTENT

				&nbsp;/ 100
			
IPSCONTENT;

elseif ( $name === 'f_maxmind_proxy' ):
$return .= <<<IPSCONTENT

				&nbsp;/ 4
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function creditCard( $field, $types, $number, $expMonth, $expYear, $ccv, $storedCards ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div 
IPSCONTENT;

if ( $field->htmlId ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class=''>
	
IPSCONTENT;

if ( $field->options['attr'] ):
$return .= <<<IPSCONTENT

		<div 
IPSCONTENT;

foreach ( $field->options['attr'] as $k => $v ):
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
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $field->options['jsRequired'] ):
$return .= <<<IPSCONTENT

		<div class="ipsJS_hide" data-role="error">
			<div class="ipsMessage ipsMessage--error" data-role="errorMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_requires_js', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsJS_show ipsBox i-padding_3" data-role="paymentMethodForm">
		<div 
IPSCONTENT;

if ( $field->options['loading'] and !\count( $storedCards ) ):
$return .= <<<IPSCONTENT
class="ipsLoading i-opacity_3"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

if ( \count( $storedCards ) ):
$return .= <<<IPSCONTENT

					<div class='ipsFieldRow '>
						<ul class='ipsFieldRow__content ipsFieldList'>
							
IPSCONTENT;

$j = 0;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $storedCards as $i => $card ):
$return .= <<<IPSCONTENT

								<li>
									<input type='radio' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[stored]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $card->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_stored
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $j === 0 ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<div class='ipsFieldList__content'>
										<label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_stored
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span class='i-color_soft'>xxxx</span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $card->card->lastFour, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
										<div class='ipsFieldRow__desc'>
IPSCONTENT;

if ( $card->card->type ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "card_type_{$card->card->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $card->card->expMonth ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_expires_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $card->card->expMonth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $card->card->expYear, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
									</div>
								</li>
								
IPSCONTENT;

$j++;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							<li>
								<input type='radio' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[stored]" value="0" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_stored0" data-control="toggle" data-toggles="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_new">
								<div class='ipsFieldList__content'>
									<label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_stored0'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'use_new_card', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
								</div>
							</li>
						</ul>
					</div>
					<div id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_new' class="ipsHide">
						<hr class='ipsHr'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $types ) ):
$return .= <<<IPSCONTENT

				<ul class='ipsList ipsList--inline i-gap_0 i-margin-bottom_3 cPayment_cardTypeList'>
					
IPSCONTENT;

foreach ( $types as $key => $lang ):
$return .= <<<IPSCONTENT

						<li><span class='cPayment cPayment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span class='ipsInvisible'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></span></li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsFieldRow 
IPSCONTENT;

if ( $field->error == 'card_number_invalid' ):
$return .= <<<IPSCONTENT
ipsFieldRow_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				<label class='ipsFieldRow__label' for='elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-number'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span class='ipsFieldRow__required'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</label>
				<div class='ipsFieldRow__content'>
					
IPSCONTENT;

if ( $field->options['dummy'] ):
$return .= <<<IPSCONTENT

						<div class="ipsInput ipsInput--text ipsInput--dummy ipsInput--primary" id="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-number" data-role="dummyCard"></div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<input type="text" class='ipsInput ipsInput--text ipsInput--primary' 
IPSCONTENT;

if ( $field->options['names'] ):
$return .= <<<IPSCONTENT
name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[number]"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-card="number" id="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-number" maxlength="16" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $number, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" autocomplete="cc-number">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div class="ipsFieldRow__warning" data-warning="number">
IPSCONTENT;

if ( $field->error and !\in_array( $field->error, array( 'card_expire_expired', 'ccv_invalid', 'ccv_invalid_3', 'ccv_invalid_4' ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$field->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
				</div>
			</div>
			<div class='ipsSpanGrid i-margin-bottom_3'>
				<div class='ipsSpanGrid__7 cNexusCard_expiry'>
					<div class='ipsFieldRow 
IPSCONTENT;

if ( $field->error == 'card_expire_expired' ):
$return .= <<<IPSCONTENT
ipsFieldRow_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
						<label class='ipsFieldRow__label' for='elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-exp_month'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_expire', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span class='ipsFieldRow__required'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</label>
						<div class='ipsFieldRow__content'>
							
IPSCONTENT;

if ( $field->options['dummy'] ):
$return .= <<<IPSCONTENT

								<div class="ipsInput ipsInput--text ipsInput--dummy" id="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-exp" data-role="dummyExp"></div>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<select class="ipsInput ipsInput--select" 
IPSCONTENT;

if ( $field->options['names'] ):
$return .= <<<IPSCONTENT
name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[exp_month]"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-card="exp_month" id="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-exp_month" autocomplete="cc-exp-month">
									<option 
IPSCONTENT;

if ( $expMonth === NULL ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 disabled>MM</option>
									
IPSCONTENT;

foreach ( range( 1, 12 ) as $m ):
$return .= <<<IPSCONTENT

										<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $m, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $expMonth === $m ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_pad( $m, 2, '0', STR_PAD_LEFT ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</select>
								<select class="ipsInput ipsInput--select" 
IPSCONTENT;

if ( $field->options['names'] ):
$return .= <<<IPSCONTENT
name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[exp_year]"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-card="exp_year" id="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-exp_year" autocomplete="cc-exp-year">
									<option 
IPSCONTENT;

if ( $expYear === NULL ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 disabled>YYYY</option>
									
IPSCONTENT;

foreach ( range( date('Y'), date('Y') + 10 ) as $y ):
$return .= <<<IPSCONTENT

										<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $y, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $expYear === $y ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $y, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</select>
								<br>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<span class="i-color_warning" data-warning="exp">
IPSCONTENT;

if ( $field->error == 'card_expire_expired' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$field->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
					</div>
				</div>
				<div class='ipsSpanGrid__5'>
					<div class='ipsFieldRow 
IPSCONTENT;

if ( $field->error == 'ccv_invalid' or $field->error == 'ccv_invalid_3' or $field->error == 'ccv_invalid_4' ):
$return .= <<<IPSCONTENT
ipsFieldRow_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
						<label class='ipsFieldRow__label' for='elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-number'>
							<span title="
IPSCONTENT;

if ( array_key_exists( \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS, $types ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_ccv_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_ccv_desc_no_amex', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsTooltip>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_ccv', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>  <span class='ipsFieldRow__required'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</label>
						<div class='ipsFieldRow__content'>
							
IPSCONTENT;

if ( $field->options['dummy'] ):
$return .= <<<IPSCONTENT

								<div class="ipsInput ipsInput--text ipsInput--dummy" id="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-ccv" data-role="dummyCcv"></div>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<input type="text" class="ipsInput ipsInput--text" size='4' 
IPSCONTENT;

if ( $field->options['names'] ):
$return .= <<<IPSCONTENT
name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[ccv]"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-card="ccv" id="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-ccv" maxlength="4" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $ccv, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" autocomplete="cc-csc">
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<span class="i-color_warning" data-warning="ccv">
IPSCONTENT;

if ( $field->error == 'ccv_invalid' or $field->error == 'ccv_invalid_3' or $field->error == 'ccv_invalid_4' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$field->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
					</div>
				</div>
			</div>
			
IPSCONTENT;

if ( $field->options['save'] ):
$return .= <<<IPSCONTENT

				<div class='ipsFieldRow ipsFieldRow--checkbox'>
					<input type='checkbox' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[save]" value="1" id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Save' checked> 
					<div class='ipsFieldRow__content'>
						<label for='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Save'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $storedCards ) ):
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

if ( $field->options['attr'] ):
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function money( $name, $value, $options ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$decimals = \IPS\nexus\Money::numberOfDecimalsForCurrency( $currency );
$return .= <<<IPSCONTENT

	<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" size="4" value="
IPSCONTENT;

if ( isset($value[$currency]) AND $value !== '*' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( \is_object( $value[$currency] ) ? (string) $value[$currency]->amount : $value[$currency], $decimals, '.', '' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text ipsField_short" step="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format(1/pow(10,$decimals),$decimals,'.',''), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		<br>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $options['unlimitedLang'] !== NULL ):
$return .= <<<IPSCONTENT

	&nbsp;
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	&nbsp;
	<input
		type="checkbox"
		role='checkbox'
		data-control="unlimited 
IPSCONTENT;

if ( !empty($options['unlimitedTogglesOn']) OR !empty($options['unlimitedTogglesOff']) ):
$return .= <<<IPSCONTENT
toggle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
		name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[__unlimited]"
		value="1"
		
IPSCONTENT;

if ( $value === '*' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-unlimitedCheck"
		
IPSCONTENT;

if ( !empty($options['unlimitedTogglesOn']) ):
$return .= <<<IPSCONTENT
 data-togglesOn="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $options['unlimitedTogglesOn'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-controls="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $options['unlimitedTogglesOn'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !empty($options['unlimitedTogglesOff']) ):
$return .= <<<IPSCONTENT
 data-togglesOff="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $options['unlimitedTogglesOff'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-controls="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $options['unlimitedTogglesOff'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	> <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-unlimitedCheck'>
IPSCONTENT;

$val = "{$options['unlimitedLang']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
	<br>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function paymentRequestApi( $field, $gateway, $key, $country, $invoice, $currency, $amountAsCents, $amount ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="nexus.global.gateways.stripepaymentrequest" data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $gateway->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-country="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $country, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-currency="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-amount="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $amount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-amountAsCents="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $amountAsCents, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-invoice="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<noscript>
		<div class="ipsMessage ipsMessage--error">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_requires_js', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</noscript>
	<div id="paymentrequest-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
</div>
IPSCONTENT;

		return $return;
}

	function renewalTerm( $name, $value, $options ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$term = ( $value and $value->interval ) ? $value->getTerm() : NULL;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $options['nullLang'] ):
$return .= <<<IPSCONTENT

	<div class="i-margin-block_2">
		<input type="checkbox" role="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[null]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null" data-control="unlimited" 
IPSCONTENT;

if ( $value === NULL ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null_label" class="ipsInput ipsInput--toggle">
		<label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null_label" class="ipsField_unlimited">
IPSCONTENT;

$val = "{$options['nullLang']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
		&nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !isset( $options['lockPrice'] ) or !$options['lockPrice'] ):
$return .= <<<IPSCONTENT

<div class='cNexusStack cNexusStack_usergroup' id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_stack">
	<ol>
		<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

if ( $options['allCurrencies'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

					<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[amount_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" size="4" data-decimals="2" data-decpoint="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->locale['decimal_point'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-thousandsseparator="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->locale['thousands_sep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" step="0.01" value="
IPSCONTENT;

if ( $value AND isset( $value->cost[ $currency ]->amount ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->cost[ $currency ]->amount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text ipsField_short ipsField_stackItemNoMargin"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[amount]" size="4" data-decimals="2" data-decpoint="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->locale['decimal_point'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-thousandsseparator="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->locale['thousands_sep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" step="0.01" value="
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->cost->amountAsString(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text ipsField_short ipsField_stackItemNoMargin">
				
IPSCONTENT;

if ( \count( \IPS\nexus\Money::currencies() ) === 1 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->cost->currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\nexus\Money::currencies()[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[currency]">
						
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

							<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $value AND $currency === $value->cost->currency ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</select>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
		<li data-step="
IPSCONTENT;

if ( $options['initialTerm'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'for', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'every', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="unlimitedCatch">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[term]" size="4" value="
IPSCONTENT;

if ( $term ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $term['term'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text ipsField_tiny ipsField_stackItemNoMargin"  
IPSCONTENT;

if ( $options['lockTerm'] ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[unit]" 
IPSCONTENT;

if ( $options['lockTerm'] ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<option value="d" 
IPSCONTENT;

if ( $term and $term['unit'] === 'd' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'days', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
				<option value="m" 
IPSCONTENT;

if ( !$term or $term['unit'] === 'm' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'months', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
				<option value="y" 
IPSCONTENT;

if ( $term and $term['unit'] === 'y' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'years', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
			</select>
			
IPSCONTENT;

if ( $options['initialTerm'] ):
$return .= <<<IPSCONTENT

				&nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
&nbsp;
				<input type="checkbox"
					role="checkbox"
					data-control="unlimited 
IPSCONTENT;

if ( $options['unlimitedTogglesOn'] or $options['unlimitedTogglesOff'] ):
$return .= <<<IPSCONTENT
toggle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
					
IPSCONTENT;

if ( $options['unlimitedTogglesOn'] ):
$return .= <<<IPSCONTENT
data-togglesOn="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $options['unlimitedTogglesOn'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $options['unlimitedTogglesOff'] ):
$return .= <<<IPSCONTENT
data-togglesOff="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $options['unlimitedTogglesOff'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[unlimited]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited"
					
IPSCONTENT;

if ( ( $value or $options['lockPrice'] ) and !$term ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					aria-labelledby="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited_label"
					class="ipsInput ipsInput--toggle"
				>
				<label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited_label" class="ipsField_unlimited">
IPSCONTENT;

$val = "{$options['initialTermLang']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !isset( $options['lockPrice'] ) or !$options['lockPrice'] ):
$return .= <<<IPSCONTENT

		</li>
		
IPSCONTENT;

if ( $options['addToBase'] ):
$return .= <<<IPSCONTENT

			<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
				<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[add]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_add" 
IPSCONTENT;

if ( $value and $value->addToBase ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_to_purchase_price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ol>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function stateSelect( $name, $value, $unlimited=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- @todo [future] Is there a better UI for this? -->

IPSCONTENT;

if ( $unlimited !== NULL ):
$return .= <<<IPSCONTENT

	<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited" value="1" 
IPSCONTENT;

if ( $value === '*' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> 
IPSCONTENT;

$val = "{$unlimited}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[]" multiple size="20">
	
IPSCONTENT;

foreach ( \IPS\GeoLocation::$countries as $country ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( \IPS\GeoLocation::$states[ $country ] ) ):
$return .= <<<IPSCONTENT

			<optgroup label="
IPSCONTENT;

$val = "country-{$country}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

foreach ( \IPS\GeoLocation::$states[ $country ] as $state ):
$return .= <<<IPSCONTENT

					<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $country, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $state, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $value[ $country ] ) and ( ( $value[ $country ] == "*" ) or ( \in_array( $state, $value[ $country ] ) ) ) ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $state, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</optgroup>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $country, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $value[ $country ] ) ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "country-{$country}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</select>
IPSCONTENT;

		return $return;
}

	function usernamePassword( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsForm'>
	<li class='ipsFieldRow'>
		<div class='ipsFieldList__content'>
			<div class='ipsInputIcon'>
				<span class="ipsInputIcon__icon fa-solid fa-user"></span>
				<input type='text' class='ipsInput ipsInput--text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[un]" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $value['un'] ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['un'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			</div>
		</div>
	</li>
	<li class='ipsFieldRow'>
		<div class='ipsFieldList__content'>
			<div class='ipsInputIcon'>
				<span class="ipsInputIcon__icon fa-solid fa-lock"></span>
				<input type='password' class='ipsInput ipsInput--text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[pw]" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $value['pw'] ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['pw'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			</div>
		</div>
	</li>
</ul>
IPSCONTENT;

		return $return;
}

	function usernamePasswordDisplay( $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

	<ul class="ipsList ipsList--inline">
		<li><i class="fa-solid fa-user"></i> <span class="i-font-family_monospace">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['un'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></li>
		<li><i class="fa-solid fa-lock"></i> <span class="i-font-family_monospace">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['pw'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></li>
	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}