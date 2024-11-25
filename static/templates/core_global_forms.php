<?php
namespace IPS\Theme;
class class_core_global_forms extends \IPS\Theme\Template
{	function address( $name, $value, $googleApiKey, $minimize=FALSE, $requireFullAddress=TRUE ) {
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
>
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
</ul>
IPSCONTENT;

		return $return;
}

	function autocomplete( $name, $value, $required, $maxlength=NULL, $disabled=FALSE, $class='', $placeholder='', $nullLang=NULL, $autoComplete=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$valueAsArray = \is_array( $value ) ? $value : ( \is_string( $value ) ? explode( ',', $value ) : [] );
$return .= <<<IPSCONTENT


IPSCONTENT;

$valueToDisplay = \is_array( $value ) ? implode( "\n", $value ) : $value;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( ( !isset( $autoComplete['commaTrigger'] ) || $autoComplete['commaTrigger'] !== FALSE ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

// If the stored value has commas in it, we need to explode then implode to get the newlines
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $valueToDisplay and mb_stripos( $valueToDisplay, ',') !== FALSE ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$valueToDisplay = implode("\n", explode(",", $valueToDisplay));
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div>

IPSCONTENT;

if ( ( isset( $autoComplete['freeChoice'] ) && !$autoComplete['freeChoice'] ) || ( isset( $autoComplete['prefix'] ) and $autoComplete['prefix'] )  ):
$return .= <<<IPSCONTENT

<div 
IPSCONTENT;

if ( isset( $autoComplete['freeChoice'] ) && !$autoComplete['freeChoice'] ):
$return .= <<<IPSCONTENT
class="ipsJS_show"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $autoComplete['prefix'] ) and $autoComplete['prefix'] ):
$return .= <<<IPSCONTENT
data-controller='core.global.core.prefixedAutocomplete'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $autoComplete['prefix'] ) and $autoComplete['prefix'] ):
$return .= <<<IPSCONTENT

	<div data-role='prefixRow' class='ipsHide' id='
IPSCONTENT;

if ( ! empty($htmlId) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
_prefixWrap'>
		<input type='checkbox' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_freechoice_prefix' 
IPSCONTENT;

if ( isset($valueAsArray['prefix']) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class='ipsJS_hide'> <button type='button' id='
IPSCONTENT;

if ( ! empty($htmlId) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
_prefix' data-role="prefixButton" data-ipsMenu data-ipsMenu-selectable="radio" data-ipsMenu-appendTo="
IPSCONTENT;

if ( ! empty($htmlId) ):
$return .= <<<IPSCONTENT
#
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
#
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
_prefixWrap" class='ipsButton ipsButton--soft ipsButton--small'><span>
IPSCONTENT;

if ( isset($valueAsArray['prefix']) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $valueAsArray['prefix'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span> <i class='fa-solid fa-caret-down'></i></button>
		<input type='hidden' data-role='prefixValue' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_prefix' value='
IPSCONTENT;

if ( isset($valueAsArray['prefix']) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $valueAsArray['prefix'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		<ul id='
IPSCONTENT;

if ( ! empty($htmlId) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
_prefix_menu' data-role="prefixMenu" class='ipsMenu ipsMenu_selectable ipsMenu_auto ipsHide'>
			<li data-ipsMenuValue='-' class='ipsMenu_item ipsMenu_itemChecked'>
				<a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
			<li class='ipsMenu_sep'>
				<hr>
			</li>
		</ul>
	</div>
	<noscript>
		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_first_as_prefix" value="0">
		
IPSCONTENT;

$valueKeys = \is_array( $value ) ? array_keys( $value ) : array_keys( explode( ',', $value ) );
$return .= <<<IPSCONTENT

		<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_first_as_prefix" value="1" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_first_as_prefix" 
IPSCONTENT;

if ( array_shift( $valueKeys ) === 'prefix' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> <label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_first_as_prefix">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'use_first_tag_as_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
	</noscript>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<textarea
	name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	id='elInput_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	class="ipsInput ipsInput--text 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

if ( $maxlength !== NULL ):
$return .= <<<IPSCONTENT
maxlength="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxlength, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $placeholder ):
$return .= <<<IPSCONTENT
placeholder="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $placeholder, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	data-ipsAutocomplete
	
IPSCONTENT;

if ( isset( $autoComplete['freeChoice'] ) && !$autoComplete['freeChoice'] ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-freeChoice='false'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( isset( $autoComplete['suggestionsOnly'] ) && $autoComplete['suggestionsOnly'] ):
$return .= <<<IPSCONTENT
data-ipsautocomplete-suggestionsOnly="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $autoComplete['forceLower'] ) && $autoComplete['forceLower'] ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-forceLower
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	data-ipsAutocomplete-lang='
IPSCONTENT;

if ( isset( $autoComplete['lang'] ) && $autoComplete['lang'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $autoComplete['lang'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ac_optional
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
	
IPSCONTENT;

if ( isset( $autoComplete['maxItems'] ) ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-maxItems='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $autoComplete['maxItems'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $autoComplete['addTokenText'] ) ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-addTokenText='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $autoComplete['addTokenText'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $autoComplete['addTokenTemplate'] ) ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-addTokenTemplate='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $autoComplete['addTokenTemplate'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !empty($autoComplete['unique']) ):
$return .= <<<IPSCONTENT

		data-ipsAutocomplete-unique
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset($autoComplete['source']) AND \is_array( $autoComplete['source'] ) ):
$return .= <<<IPSCONTENT

		list='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_datalist'
	
IPSCONTENT;

elseif ( !empty($autoComplete['source']) ):
$return .= <<<IPSCONTENT

		data-ipsAutocomplete-dataSource="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "{$autoComplete['source']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"
		data-ipsAutocomplete-queryParam='input'
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !empty($autoComplete['resultItemTemplate']) ):
$return .= <<<IPSCONTENT

		data-ipsAutocomplete-resultItemTemplate="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $autoComplete['resultItemTemplate'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $autoComplete['minLength'] ) ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-minLength='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $autoComplete['minLength'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $autoComplete['maxLength'] ) ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-maxLength='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $autoComplete['maxLength'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $autoComplete['minAjaxLength'] ) ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-minAjaxLength='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $autoComplete['minAjaxLength'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $autoComplete['disallowedCharacters'] ) ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-disallowedCharacters='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $autoComplete['disallowedCharacters'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $autoComplete['commaTrigger'] ) && $autoComplete['commaTrigger'] === FALSE ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-commaTrigger='false'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $autoComplete['minimized'] ) ):
$return .= <<<IPSCONTENT
data-ipsAutocomplete-minimized
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
IPSCONTENT;

if ( $valueToDisplay ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $valueToDisplay, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</textarea>


IPSCONTENT;

if ( ( isset( $autoComplete['freeChoice'] ) && !$autoComplete['freeChoice'] ) || ( isset( $autoComplete['prefix'] ) and $autoComplete['prefix'] )  ):
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>


IPSCONTENT;

if ( isset( $autoComplete['desc'] ) ):
$return .= <<<IPSCONTENT

	<div class='ipsFieldRow__desc'>
		{$autoComplete['desc']}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $nullLang ):
$return .= <<<IPSCONTENT

	<div class="ipsFieldRow__inlineCheckbox i-margin-top_1">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
		<input type="checkbox" data-control="unlimited" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null" id="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null" value="1" 
IPSCONTENT;

if ( $value === NULL ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-controls='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-labelledby='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null_label' class="ipsInput ipsInput--toggle">
		<label for='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null' id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null_label' class='ipsField_unlimited'>
IPSCONTENT;

$val = "{$nullLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( isset($autoComplete['source']) AND \is_array( $autoComplete['source'] ) ):
$return .= <<<IPSCONTENT

	<datalist id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_datalist">
	    
IPSCONTENT;

if ( isset( $autoComplete['formatSource'] ) and $autoComplete['formatSource'] ):
$return .= <<<IPSCONTENT

	        
IPSCONTENT;

foreach ( $autoComplete['source'] as $k => $v ):
$return .= <<<IPSCONTENT

	            <option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">{$v}</option>
	        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		    
IPSCONTENT;

foreach ( $autoComplete['source'] as $v ):
$return .= <<<IPSCONTENT

			    <option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
		    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</datalist>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function button( $lang, $type, $href=NULL, $class='', $attributes=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $type === 'link' ):
$return .= <<<IPSCONTENT

	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $href, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='
IPSCONTENT;

if ( ! $class or ! mb_stristr( $class, 'ipsButton' ) ):
$return .= <<<IPSCONTENT
ipsButton ipsButton--text
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $attributes ):
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $attributes as $key => $value ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 role="button">
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<button type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $class ):
$return .= <<<IPSCONTENT
class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $attributes ):
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $attributes as $key => $value ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function captchaInvisible( $publicKey, $lang, $rowHtml=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $rowHtml ):
$return .= <<<IPSCONTENT
<li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div data-ipsCaptcha data-ipsCaptcha-service='recaptcha_invisible' data-ipsCaptcha-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $publicKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsCaptcha-lang="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<noscript>
	  <div style="width: 302px; height: 352px;">
	    <div style="width: 302px; height: 352px; position: relative;">
	      <div style="width: 302px; height: 352px; position: absolute;">
	        <iframe src="https://www.google.com/recaptcha/api/fallback?k=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $publicKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" style="width: 302px; height:352px; border-style: none;">
	        </iframe>
	      </div>
	      <div style="width: 250px; height: 80px; position: absolute; border-style: none; bottom: 21px; left: 25px; margin: 0px; padding: 0px; right: 25px;">
	        <textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 80px; border: 1px solid #c1c1c1; margin: 0px; padding: 0px; resize: none;"></textarea>
	      </div>
	    </div>
	  </div>
	</noscript>
</div>

IPSCONTENT;

if ( $rowHtml ):
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function captchaKeycaptcha( $userId, $uniq, $sign, $sign2 ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input type='hidden' id='capcode' name='keycaptcha'>
<script>
	// required
	var s_s_c_user_id = '
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $userId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
';
	var s_s_c_session_id = '
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uniq, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
';
	var s_s_c_captcha_field_id = 'capcode';
	var s_s_c_submit_button_id ='sbutton-#-r';
	var s_s_c_web_server_sign = '
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $sign, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
';
	var s_s_c_web_server_sign2 = '
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $sign2, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
';
</script>
<div data-ipsCaptcha data-ipsCaptcha-service='keycaptcha' id='div_for_keycaptcha'></div>

IPSCONTENT;

		return $return;
}

	function captchaRecaptcha( $publicKey, $lang, $theme, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ipsCaptcha data-ipsCaptcha-service='recaptcha' data-ipsCaptcha-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $publicKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsCaptcha-lang="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsCaptcha-theme="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $theme, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<noscript>
		<iframe src="//www.google.com/recaptcha/api/noscript?k=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $publicKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&hl=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&error=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" height="300" width="500"></iframe>
		<br>
		<textarea class='ipsInput ipsInput--text' name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
		<input type="hidden" name="recaptcha_response_field" value="manual_challenge">
	</noscript>
</div>
IPSCONTENT;

		return $return;
}

	function captchaRecaptcha2( $publicKey, $lang, $theme='light' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ipsCaptcha data-ipsCaptcha-service='recaptcha2' data-ipsCaptcha-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $publicKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsCaptcha-lang="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsCaptcha-theme="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $theme, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></div>
IPSCONTENT;

		return $return;
}

	function checkbox( $name, $value=FALSE, $disabled=FALSE, $togglesOn=array(), $togglesOff=array(), $label='', $hiddenName='', $id=NULL, $fancyToggle=FALSE, $tooltip=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $id === NULL ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$id = md5(uniqid());
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $hiddenName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="0">
	<input
		type='checkbox'
		name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
		value='1'
		id="check_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		data-toggle-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !empty($togglesOn) OR !empty($togglesOff) ):
$return .= <<<IPSCONTENT

			data-control="toggle"
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !empty($togglesOn) ):
$return .= <<<IPSCONTENT
 data-togglesOn="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $togglesOn ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-controls="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $togglesOn ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !empty($togglesOff) ):
$return .= <<<IPSCONTENT
 data-togglesOff="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $togglesOff ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-controls="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $togglesOff ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $tooltip ):
$return .= <<<IPSCONTENT
title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tooltip, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $fancyToggle ):
$return .= <<<IPSCONTENT

			class='ipsSwitch'
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			class='ipsInput ipsInput--toggle'
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	>

IPSCONTENT;

if ( $label ):
$return .= <<<IPSCONTENT

<label for="check_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

$val = "{$label}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</label>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function checkboxset( $name, $value, $required, $options, $multiple=FALSE, $class='', $disabled=FALSE, $toggles=array(), $id=NULL, $unlimited=NULL, $unlimitedLang='all', $unlimitedToggles=array(), $toggleOn=TRUE, $descriptions=array(), $impliedUnlimited=FALSE, $showAllNone=TRUE, $condense=TRUE, $userSuppliedInput='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT

	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[__EMPTY]" value="__EMPTY">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div data-control="granularCheckboxset"
IPSCONTENT;

if ( $condense ):
$return .= <<<IPSCONTENT
 data-count="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $options ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( $unlimited !== NULL AND $impliedUnlimited === FALSE ):
$return .= <<<IPSCONTENT

			<div data-role="checkboxsetUnlimited" class="
IPSCONTENT;

if ( !\is_array( $value ) ):
$return .= <<<IPSCONTENT
ipsJS_show
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
				<input
					type='checkbox'
					name="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( trim( $name, '[]' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited"
					value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimited, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
					id="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( trim( $id ?: $name, '[]' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited"
					
IPSCONTENT;

if ( $unlimited === $value ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					class="ipsSwitch"
					data-role="checkboxsetUnlimitedToggle"
				>
				&nbsp;
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				&nbsp;
				<a class="i-cursor_pointer" data-action="checkboxsetCustomize">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'customize', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div data-role="checkboxsetGranular" class="ipsField__checkboxOverflow 
IPSCONTENT;

if ( $unlimited !== NULL AND $impliedUnlimited === FALSE and !\is_array( $value ) ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

$userInput = \is_array( $value ) ? array_diff( $value, array_keys( $options ) ) : array();
$return .= <<<IPSCONTENT

		<input type="search" data-role="search" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsInput ipsField__checkboxOverflow__search" />
		<ul class="ipsFieldList">
			
IPSCONTENT;

foreach ( $options as $k => $v ):
$return .= <<<IPSCONTENT

				<li data-role="result">
					<input type="checkbox" 
IPSCONTENT;

if ( $class ):
$return .= <<<IPSCONTENT
class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" 
IPSCONTENT;

if ( ( $unlimited !== NULL AND $unlimited === $value ) or ( \is_array( $value ) AND \in_array( $k, $value ) ) or ( $userSuppliedInput AND count( $userInput ) AND $k == $userSuppliedInput ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled === TRUE or ( \is_array( $disabled ) and \in_array( $k, $disabled ) ) ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $toggles[ $k ] ) and !empty( $toggles[ $k ] ) ):
$return .= <<<IPSCONTENT
data-control="toggle" 
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

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles[ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
					<div class='ipsFieldList__content'>
						<label for='elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label' data-role="label">{$v}</label>
						
IPSCONTENT;

if ( isset( $descriptions[ $k ] ) ):
$return .= <<<IPSCONTENT

							<div class='ipsFieldRow__desc'>
								{$descriptions[ $k ]}
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
        
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
' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $userInput ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
        </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( $showAllNone ):
$return .= <<<IPSCONTENT

		<div class="ipsJS_show ipsField__checkboxOverflow__toggles" data-role="massToggles">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			&nbsp;
			<button data-action="checkboxsetAll" type="button">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button> / <button data-action="checkboxsetNone" type="button">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function codemirror( $name, $value, $required, $maxlength=NULL, $disabled=FALSE, $class='', $placeholder='', $tags=array(), $mode='htmlmixed', $id=NULL, $height='300px', $preview=NULL, $tagLinks=array(), $tagSource=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $tags ) OR $tagSource !== NULL ):
$return .= <<<IPSCONTENT

<div class='ipsColumns' data-controller='core.global.editor.customtags' data-tagFieldType='codemirror' data-tagFieldID='elCodemirror_
IPSCONTENT;

if ( $id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $tagSource ):
$return .= <<<IPSCONTENT
data-tagSource='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagSource, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<div class='ipsColumns__primary'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='i-background_3 i-padding_2' data-role="editor" 
IPSCONTENT;

if ( $preview ):
$return .= <<<IPSCONTENT
data-controller="core.global.editor.codePreview" data-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-preview-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $preview, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<textarea
	name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	id='elCodemirror_
IPSCONTENT;

if ( $id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
	value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( htmlentities( $value === NULL ? '' : $value, ENT_DISALLOWED, 'UTF-8', TRUE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	class="ipsInput ipsInput--text ipsInput--wide 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

if ( $maxlength !== NULL ):
$return .= <<<IPSCONTENT
maxlength="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxlength, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $placeholder ):
$return .= <<<IPSCONTENT
placeholder="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $placeholder, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	data-control="codemirror"
	data-mode="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mode, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

if ( $height ):
$return .= <<<IPSCONTENT
data-height="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</textarea>
	
IPSCONTENT;

if ( $preview ):
$return .= <<<IPSCONTENT

		<button type="button" data-action="preview" data-ipsDialog data-ipsDialog-content="#
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_preview" class="ipsButton ipsButton--soft i-margin-top_3 ipsButton--small ipsJs_hide"><i class="fa-solid fa-magnifying-glass"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'preview', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_preview" class="ipsHide ipsDialog_loading"></div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>


IPSCONTENT;

if ( !empty( $tags ) OR $tagSource ):
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsColumns__secondary ipsComposeArea_sidebar' data-codemirrorid='elCodemirror_
IPSCONTENT;

if ( $id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		<h3 class='i-background_3 i-padding_2 ipsTitle ipsTitle--h4' data-role='tagsHeader'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<ul class='ipsScrollbar' data-role='tagsList'>
		
IPSCONTENT;

if ( !empty( $tags ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->editorTags( $tags, $tagLinks );
$return .= <<<IPSCONTENT

		
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

		return $return;
}

	function color( $name, $value, $required, $disabled=FALSE, $swatches=NULL, $rgba=FALSE, $allowNone=FALSE, $allowNoneLang='colorpicker_use_none' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsInput__color-wrap" >
    <div class="ipsInput__color-wrap-inner">
        <input
                type="color"
                name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
                
IPSCONTENT;

if ( $allowNone and ! $value ):
$return .= <<<IPSCONTENT

                value=""
                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( $swatches ):
$return .= <<<IPSCONTENT
data-swatches="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( $rgba ):
$return .= <<<IPSCONTENT
data-rgba="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                data-control="color" class="ipsInput ipsInput--text">
        <span spellcheck="false" contenteditable="plaintext-only" class="ipsInput__color-label">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
        <div data-role="iro-container" class="ipsInput__color-iro-container ipsMenu"></div>
    </div>
    
IPSCONTENT;

if ( $allowNone ):
$return .= <<<IPSCONTENT

        &nbsp;
        <div class="ipsFieldRow__inlineCheckbox">
            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

            &nbsp;
            <input type="checkbox" data-control="unlimited" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_none" id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_none' value="1" 
IPSCONTENT;

if ( ! $value ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby='label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_none' class="ipsInput ipsInput--toggle">
            <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_none' id='label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_none' class='ipsField_unlimited'>
IPSCONTENT;

$val = "{$allowNoneLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
        </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function colorDisplay( $color ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-flex i-align-items_center i-gap_2">
	<div style="background-color: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
; height: 15px; width: 15px; border: 1px solid black;" class="i-flex_00"></div><div>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_strtoupper( $color ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
</div>
IPSCONTENT;

		return $return;
}

	function date( $name, $value, $required, $min=NULL, $max=NULL, $disabled=FALSE, $time=FALSE, $unlimited=NULL, $unlimitedLang=NULL, $unlimitedName=NULL, $toggles=array(), $toggleOn=TRUE, $class='ipsField_short', $placeholder=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input
	type="date"
	name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

if ( $value and ($unlimited === NULL or $value and $value !== $unlimited ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;

if ( $value instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->format('Y-m-d'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-preferredFormat="
IPSCONTENT;

if ( $value instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $min !== NULL ):
$return .= <<<IPSCONTENT
min="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $min, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $max !== NULL ):
$return .= <<<IPSCONTENT
max="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $max, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	class="ipsInput ipsInput--text 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	data-control="date"
>

IPSCONTENT;

if ( $time ):
$return .= <<<IPSCONTENT

<input name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $time, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" type="time" size="12" class="ipsInput ipsInput--text ipsField_short" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( '_time_format_hhmm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" step='60' min='00:00' value="
IPSCONTENT;

if ( $value instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->format('H:i'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $unlimited !== NULL and ( !$disabled or $unlimited === $value ) ):
$return .= <<<IPSCONTENT

	&nbsp;
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	&nbsp;
	<input type="checkbox" data-control="unlimited
IPSCONTENT;

if ( \count( $toggles ) ):
$return .= <<<IPSCONTENT
 toggle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value="
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

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \count( $toggles ) ):
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

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby='label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsInput ipsInput--toggle">
	<label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsField_unlimited'>
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

	function dateRange( $start, $end, $unlimited=NULL, $unlimitedLang=NULL, $unlimitedName=NULL, $unlimitedChecked=FALSE, $toggles=array(), $toggleOn=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'between', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 {$start} 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 {$end}

IPSCONTENT;

if ( $unlimited !== NULL ):
$return .= <<<IPSCONTENT

	&nbsp;
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	&nbsp;
	<input type="checkbox" data-control="unlimited
IPSCONTENT;

if ( \count( $toggles ) ):
$return .= <<<IPSCONTENT
 toggle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimited, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $unlimitedChecked ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \count( $toggles ) ):
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

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby='label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsInput ipsInput--toggle">
	<label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimitedName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsField_unlimited'>
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

	function editor( $name, $value, $options, $postKey, $uploadControl, $emoticons, $tags=array(), $contentClass=NULL, $contentId=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $tags ) ):
$return .= <<<IPSCONTENT

<div class='ipsColumns' data-controller='core.global.editor.customtags' data-tagFieldType='editor' data-tagFieldID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<div class='ipsColumns__primary'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsComposeArea_editor' data-role="editor">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->editorRawV5( $name, $value, $options, $postKey, $uploadControl, $emoticons, $tags, $contentClass, $contentId );
$return .= <<<IPSCONTENT

		</div>

IPSCONTENT;

if ( !empty( $tags ) ):
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsColumns__secondary ipsComposeArea_sidebar'>
		<h3 class='i-background_3 i-padding_2 ipsTitle ipsTitle--h4' data-role='tagsHeader'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<ul class='ipsScrollbar' data-role='tagsList'>
		
IPSCONTENT;

foreach ( $tags as $tagKey => $tagValue  ):
$return .= <<<IPSCONTENT

			<li class='i-padding_2'>
				<label class="ipsJS_show i-color_hard i-font-weight_500" data-tagKey="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
				<div class='ipsJS_hide i-color_soft'><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></div>
				<div class='i-color_soft '>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function editorAttachments( $name, $value, $minimize, $maxFileSize, $maxFiles, $maxChunkSize, $totalMaxSize, $allowedFileTypes, $pluploadKey, $multiple=FALSE, $editor=FALSE, $forceNoscript=FALSE, $template='core.attachments.fileItem', $existing=array(), $default=NULL, $supportsDelete = TRUE, $allowStockPhotos=FALSE ) {
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
]" value="">
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div data-ipsEditor-toolList>
		
IPSCONTENT;

$editorName = preg_replace( "/(.+?)_(\d+?)_$/", "$1[$2]", mb_substr( $name, 0, -7 ) );
$return .= <<<IPSCONTENT

		<div data-role='attachmentArea' data-controller='core.global.editor.uploader, core.global.editor.insertable' data-editorID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editorName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsComposeArea_attachments ipsUploader ipsUploader--withBorder ipsUploader--insertable' id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_drop_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsUploader data-ipsUploader-dropTarget='#elEditorDrop_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsUploader-button='[data-action="browse"]' 
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
" data-ipsUploader-key="
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

$return .= \IPS\Theme\Template::htmlspecialchars( $totalMaxSize, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
 data-ipsUploader-insertable data-ipsUploader-postkey="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editor, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsUploader-template='core.editor.attachments' 
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT
data-ipsUploader-existingFiles='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $existing ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
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
>
			<div class="ipsComposeArea_dropZone" id='elEditorDrop_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				<i class='fa-solid fa-paperclip ipsUploader__icon'></i>
				<div>
					<ul class='i-flex i-align-items_center i-flex-wrap_wrap'>
						<li class="i-flex_00 ipsAttachment_loading ipsLoading--small ipsAttachment_loading_editor ipsHide"><i class='fa-solid fa-circle-notch fa-spin fa-fw'></i></li>
						<li class='i-flex_91' data-action='browse' data-supports-drag="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( isset( (\IPS\Request::i()->cookie['editor_supports_drag'])) and  (\IPS\Request::i()->cookie['editor_supports_drag']) ?: "true", ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							<div class='ipsAttachment_supportDrag'>
								<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_attach_drag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								<a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_attach_choose_drag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</div>
                            <div class='ipsAttachment_nonDrag'>
                                <button type='button' class='ipsButton ipsButton--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_attach_choose_nodrag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
                            </div>
						</li>
                        <li class="ipsComposeArea__media-other">
                            <a
                                href='#'
                                data-ipsDialog
                                data-ipsDialog-fixed
                                data-ipsDialog-forceReload
                                data-ipsDialog-destructOnClose
                                data-ipsDialog-remoteSubmit='false'
                                data-ipsDialog-remoteVerify='false'
                                data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_insert_existing_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
                                data-ipsDialog-url='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=editor&do=myMedia&postKey={$editor}&editorId={$editorName}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'
                                data-ipsTooltip=""
                                title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_insert_existing_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
                            >
								<i class="fa-regular fa-folder-open"></i>
								<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_insert_existing_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                            </a>
                        </li>
                        
IPSCONTENT;

if ( $allowStockPhotos ):
$return .= <<<IPSCONTENT

                            <li class="ipsComposeArea__media-other">
                                <a
                                    href='#'
                                    data-action='stockPhoto'
                                    title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_stockart_choose_editor', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
                                    data-ipstooltip=''
                                >
                                    <i class="fa-regular fa-images"></i>
									<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_stockart_choose_editor', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                                </a>
                            </li>
                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
					
IPSCONTENT;

if ( $allowedFileTypes !== NULL || $maxFileSize || $totalMaxSize ):
$return .= <<<IPSCONTENT

						<ul class='i-flex i-flex-wrap_wrap i-gap_3 i-row-gap_1 i-color_soft'>
							
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

								<li>
									<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_accepted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</strong>
									
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ', ', $allowedFileTypes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $multiple and $totalMaxSize ):
$return .= <<<IPSCONTENT

								<li>
									<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_total_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</strong>
									
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $totalMaxSize * 1048576 );
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $maxFileSize and ( !$multiple or !$totalMaxSize or $maxFileSize < $totalMaxSize ) ):
$return .= <<<IPSCONTENT

								<li>
									<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</strong>
									
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round($maxFileSize,2), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
MB
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $maxFiles ):
$return .= <<<IPSCONTENT

								<li>
IPSCONTENT;

$pluralize = array( $maxFiles ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max_files', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div data-role='fileList' class='ipsComposeArea_attachmentsInner 
IPSCONTENT;

if ( \count($value) == 0 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
						<div data-role='files' class='ipsHide ipsComposeArea_attachmentsContainer'>
							<h4 class='ipsTitle ipsTitle--hr i-margin-top_3 i-font-weight_600 i-text-transform_uppercase i-font-size_-1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_uploaded_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
							<div class='ipsUploader__container ipsUploader__container--files' data-role='fileContainer'>
								
IPSCONTENT;

foreach ( $value as $attachID => $file ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $file->mediaType() === 'file' ):
$return .= <<<IPSCONTENT

										<div class='ipsUploader__row ipsUploader__row--insertable ipsAttach ipsAttach_done' id='elAttach_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='file' data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-filesize='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->filesize(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

if ( $file->securityKey ):
$return .= <<<IPSCONTENT
 data-filekey='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->securityKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
											<div class='ipsUploader__rowPreview' data-role='preview' data-action='insertFile'>
												<div class='ipsUploader__rowPreview__generic'>
													<i class='fa-solid fa-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getIconFromName( $file->originalFilename ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
												</div>
											</div>
											<div class='ipsUploader_rowMeta' data-action='insertFile'>
												<h2 class='ipsUploader_rowTitle' data-role='title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
												<p class='ipsUploader_rowDesc'>
													
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $file->filesize() );
$return .= <<<IPSCONTENT

												</p>
												<div data-role='insert' class='ipsUploader__rowInsert'>
													<a href='#' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'insert_into_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
														
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_insert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

													</a>
												</div>
											</div>
											<div data-role='deleteFileWrapper'>
												<a href='#' data-role='deleteFile' class='ipsUploader__rowDelete' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_media_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
													&times;
												</a>
											</div>
										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</div>
						</div>
						<div data-role='images' class='ipsHide ipsComposeArea_attachmentsContainer'>
							<h4 class='ipsTitle ipsTitle--hr i-margin-top_3 i-font-weight_600 i-text-transform_uppercase i-font-size_-1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_uploaded_images', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
							<div class='ipsUploader__container ipsUploader__container--images' data-role='fileContainer'>
								
IPSCONTENT;

foreach ( $value as $attachID => $file ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $file->mediaType() === 'image' ):
$return .= <<<IPSCONTENT

										<div class='ipsUploader__row ipsUploader__row--insertable ipsAttach ipsAttach_done' id='elAttach_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='file' data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-fullsizeurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-thumbnailurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->attachmentThumbnailUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-fileType="image">
											<div class='ipsUploader__rowPreview' data-role='preview' data-action='insertFile'>
												<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt=''>
											</div>
											<div class='ipsUploader_rowMeta' data-action='insertFile'>
												<h2 class='ipsUploader_rowTitle' data-role='title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
												<p class='ipsUploader_rowDesc'>
													
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $file->filesize() );
$return .= <<<IPSCONTENT

												</p>
												<div data-role='insert' class='ipsUploader__rowInsert'>
													<a href='#' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'insert_into_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
														
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_insert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

													</a>
												</div>
											</div>
											<div data-role='deleteFileWrapper'>
												<a href='#' data-role='deleteFile' class='ipsUploader__rowDelete' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_media_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
													&times;
												</a>
											</div>
										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</div>
						</div>
						<div data-role='videos' class='ipsHide ipsComposeArea_attachmentsContainer'>
							<h4 class='ipsTitle ipsTitle--hr i-margin-top_3 i-font-weight_600 i-text-transform_uppercase i-font-size_-1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_uploaded_videos', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
							<div class='ipsUploader__container ipsUploader__container--images' data-role='fileContainer'>
								
IPSCONTENT;

foreach ( $value as $attachID => $file ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $file->mediaType() === 'video' ):
$return .= <<<IPSCONTENT

										<div class='ipsUploader__row ipsUploader__row--image ipsUploader__row--insertable ipsAttach ipsAttach_done' id='elAttach_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='file' data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-fullsizeurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-thumbnailurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->attachmentThumbnailUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-fileType="video" data-mimeType="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getMimeType( $file->originalFilename ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
											<div class='ipsUploader__rowPreview__generic' data-role='preview' data-action='insertFile'>
												<video>
													 <source src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" type="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getMimeType( $file->originalFilename ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
												</video>
											</div>
											<div class='ipsUploader_rowMeta' data-action='insertFile'>
												<h2 class='ipsUploader_rowTitle' data-role='title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
												<p class='ipsUploader_rowDesc'>
													
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $file->filesize() );
$return .= <<<IPSCONTENT

												</p>
												<div data-role='insert' class='ipsUploader__rowInsert'>
													<a href='#' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'insert_into_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
														
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_insert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

													</a>
												</div>
											</div>
											<div data-role='deleteFileWrapper'>
												<a href='#' data-role='deleteFile' class='ipsUploader__rowDelete' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_media_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
													&times;
												</a>
											</div>
										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</div>
						</div>
						<div data-role='audio' class='ipsHide ipsComposeArea_attachmentsContainer'>
							<h4 class='ipsTitle ipsTitle--hr i-margin-top_3 i-font-weight_600 i-text-transform_uppercase i-font-size_-1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_uploaded_audio', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
							<div class='ipsUploader__container ipsUploader__container--files' data-role='fileContainer'>
								
IPSCONTENT;

foreach ( $value as $attachID => $file ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $file->mediaType() === 'audio' ):
$return .= <<<IPSCONTENT

										<div class='ipsUploader__row ipsUploader__row--insertable ipsAttach ipsAttach_done' id='elAttach_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='file' data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-fullsizeurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-thumbnailurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->attachmentThumbnailUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-fileType="audio" data-mimeType="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getMimeType( $file->originalFilename ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
											<div class='ipsUploader__rowPreview' data-role='preview' data-action='insertFile'>
												<div class='ipsUploader__rowPreview__generic'>
													<i class='fa-solid fa-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getIconFromName( $file->originalFilename ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
												</div>
											</div>
											<div class='ipsUploader_rowMeta' data-action='insertFile'>
												<h2 class='ipsUploader_rowTitle' data-role='title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
												<p class='ipsUploader_rowDesc'>
													
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $file->filesize() );
$return .= <<<IPSCONTENT

												</p>
												<div data-role='insert' class='ipsUploader__rowInsert'>
													<a href='#' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'insert_into_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
														
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_insert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

													</a>
												</div>
											</div>
											<div data-role='deleteFileWrapper'>
												<a href='#' data-role='deleteFile' class='ipsUploader__rowDelete' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_media_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
													&times;
												</a>
											</div>
										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</div>
						</div>
					</div>
				</div>
			</div>		
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function editorAttachmentsMinimized( $name ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ipsEditor-toolList data-ipsEditor-toolListMinimized data-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-background_2" hidden>
	<div data-role='attachmentArea'>
		<div class="ipsComposeArea_dropZone">
			<i class='fa-solid fa-paperclip ipsUploader__icon'></i>
			<div class='i-color_soft'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</div>
		</div>		
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function editorAttachmentsPlaceholder( $name, $editor, $noUploaderError=NULL, $allowMedia=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$noUploaderError && !$allowMedia ):
$return .= <<<IPSCONTENT

<div data-ipsEditor-toolList data-ipseditor-no-attachments-placeholder></div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div data-ipsEditor-toolList class="i-background_2" data-ipseditor-no-attachments-placeholder>
		<div data-role='attachmentArea'>
			<div class="ipsComposeArea_dropZone" id='elEditorDrop_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				<div>
					<ul class='ipsList ipsList--inline'>
						
IPSCONTENT;

if ( $noUploaderError ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

$val = "{$noUploaderError}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=attachments", null, "attachments", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'manage_attachments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $allowMedia ):
$return .= <<<IPSCONTENT

							<li>
								<a href='#' class='ipsButton ipsButton--soft ipsButton--small' data-ipsMenu id='elEditorAttach_media
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_attach_other', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								<ul class='ipsMenu ipsMenu_auto ipsHide' id='elEditorAttach_media
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
									<li class='ipsMenu_item'><a href='#' data-ipsDialog data-ipsDialog-fixed data-ipsDialog-forceReload data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_insert_existing_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-url="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=editor&do=myMedia&postKey={$editor}&editorId={$name}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_insert_existing_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								</ul>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			</div>		
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function editorRawV5( $name, $value, $options, $postKey, $uploadControl, $emoticons, $tags=array(), $contentClass=NULL, $contentId=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ! empty( $options['profanityBlock'] ) and \is_array( $options['profanityBlock'] ) ):
$return .= <<<IPSCONTENT

	<div data-role='editorCensorBlock' data-controller='core.global.editor.censorBlock' data-censorBlockWords='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode($options['profanityBlock']), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-editorID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsHide i-margin-bottom_3 i-background_2 i-padding_2 ipsLoading--small'>
		<div class="ipsMessage ipsMessage--warning">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profanity_block_public_explanation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>
		<div data-role="editorCensorBlockMessage" class="ipsRichText"><div data-role="editorCensorBlockMessageInternal"></div></div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='ipsEditor' data-ipsEditorv5
    
IPSCONTENT;

if ( $options['minimize'] !== NULL ):
$return .= <<<IPSCONTENT
data-ipsEditorv5-minimized
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


    
IPSCONTENT;

if ( $postKey ):
$return .= <<<IPSCONTENT

        data-ipsEditorv5-postKey="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $postKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


    
IPSCONTENT;

if ( $options['autoSaveKey'] ):
$return .= <<<IPSCONTENT

        data-ipsEditorv5-autoSaveKey="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $options['autoSaveKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


    data-ipsEditorv5-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"

    
IPSCONTENT;

if ( $contentClass AND $contentId ):
$return .= <<<IPSCONTENT

        data-ipsEditorv5-contentClass='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contentClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
        data-ipsEditorv5-contentId='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contentId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


    
IPSCONTENT;

if ( @$options['comments'] ):
$return .= <<<IPSCONTENT
data-ipseditorv5-commenteditor
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


    
IPSCONTENT;

if ( \IPS\Settings::i()->giphy_enabled ):
$return .= <<<IPSCONTENT
data-ipseditorv5-giphyenabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( \IPS\core\StoredReplies::enabledRepliesExist() ):
$return .= <<<IPSCONTENT
data-ipseditorv5-stockrepliesenabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( $options['loadPlugins'] ):
$return .= <<<IPSCONTENT
data-ipseditorv5-loadplugins
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    data-ipseditorv5-restrictions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode(",",\IPS\Helpers\Form\Editor::getMemberRestrictions( comments: (bool) $options["comments"] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
>

	<div data-role='editorComposer'>
		<div 
IPSCONTENT;

if ( $options['minimize'] ):
$return .= <<<IPSCONTENT
class="ipsHide norewrite"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class="norewrite ipsLoading"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role="mainEditorArea">
			<textarea name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role='contentEditor' class="ipsInput ipsInput--text ipsHide" tabindex='1'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
</textarea>
		</div>
		
IPSCONTENT;

if ( $options['minimize'] ):
$return .= <<<IPSCONTENT

			<div class='ipsComposeArea_dummy ipsJS_show' tabindex='1'><i class='fa-regular fa-comment'></i> 
IPSCONTENT;

$val = "{$options['minimize']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


        <div data-role="editor-messages-container"></div>
		{$uploadControl}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function editorTags( $tags=array(), $tagLinks=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $tags as $tagKey => $tagValue ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \is_array( $tagValue ) ):
$return .= <<<IPSCONTENT

		<li>
			<ul>
				<li>
					<h4 class='i-background_3 i-padding_2 i-flex i-align-items_center i-gap_2'>
						<span>
IPSCONTENT;

$val = "{$tagKey}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

if ( isset( $tagLinks[$tagKey] ) ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagLinks[$tagKey]['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-customtags-dialog class='ipsButton ipsButton--inherit' target='_blank' 
IPSCONTENT;

if ( isset( $tagLinks[$tagKey]['data'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $tagLinks[$tagKey]['data'] AS $dataKey => $dataValue ):
$return .= <<<IPSCONTENT
data-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $dataKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $dataValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $tagLinks[$tagKey]['title'] ) ):
$return .= <<<IPSCONTENT
title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagLinks[$tagKey]['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagLinks[$tagKey]['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
							</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</h4>
				</li>
				<li>
					<ul data-role='tagList_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

foreach ( $tagValue as $key => $value ):
$return .= <<<IPSCONTENT

						<li class='i-padding_2'>
							<label data-tagKey="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
                                <span class="ipsRichText">
                                    <code class="language-ipsphtml">
                                        
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                    </code>
                                </span>
                            </label>
							<div class='i-color_soft i-font-size_-2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</li>
			</ul>
		</li>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<li class='i-padding_2'>
			<label data-tagKey="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
                <span class="ipsRichText">

                    <code class="language-ipsphtml">
                    
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                    </code>
                </span>&nbsp;
                
IPSCONTENT;

if ( isset( $tagLinks[$tagKey] ) ):
$return .= <<<IPSCONTENT

                    <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagLinks[$tagKey]['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' data-customtags-dialog 
IPSCONTENT;

if ( isset( $tagLinks[$tagKey]['data'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $tagLinks[$tagKey]['data'] AS $dataKey => $dataValue ):
$return .= <<<IPSCONTENT
data-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $dataKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $dataValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $tagLinks[$tagKey]['title'] ) ):
$return .= <<<IPSCONTENT
title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagLinks[$tagKey]['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
                        <i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagLinks[$tagKey]['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
                    </a>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </label>
            <div class='ipsJS_hide i-color_soft'><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></div>
			
IPSCONTENT;

if ( $tagValue ):
$return .= <<<IPSCONTENT
<div class='i-color_soft i-font-size_-2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

		return $return;
}

	function ftp( $name, $value, $showBypassValidationCheckbox=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="core.global.core.ftp">
	<ul class='ipsFieldRow__content ipsFieldRow__content--checkboxes'>
		<li>
			<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[protocol]" value="ftp" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_ftp" data-role="portToggle" data-port="21" 
IPSCONTENT;

if ( !isset( $value['protocol'] ) or $value['protocol'] == 'ftp' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_ftp'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'FTP', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
		</li>
		<li>
			<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[protocol]" value="ssl_ftp" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_ssl_ftp" data-role="portToggle" data-port="21" 
IPSCONTENT;

if ( isset( $value['protocol'] ) and $value['protocol'] == 'ssl_ftp' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_ssl_ftp'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ftp_with_ssl', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
		</li>
		<li>
			<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[protocol]" value="sftp" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_sftp" data-role="portToggle" data-port="22" 
IPSCONTENT;

if ( isset( $value['protocol'] ) and $value['protocol'] == 'sftp' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_sftp'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'SFTP', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
		</li>
	</ul>
	<br>
	<ul class='ipsList ipsList--inline'>
		<li class='ipsInputIcon'>
			<span class="ipsInputIcon__icon ipsFlag fa-solid fa-earth-americas"></span>
			<input type='text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[server]" placeholder="ftp.example.com" data-role="serverInput" 
IPSCONTENT;

if ( isset( $value['server'] ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['server'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--text">
		</li>
		<li class='ipsInputIcon'>
			<span class="ipsInputIcon__icon ipsFlag fa-solid fa-bolt"></span>
			<input type='text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[port]" data-role="portInput" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'port', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;

if ( isset( $value['port'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['port'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
21
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text ipsField_tiny">
		</li>
	</ul>
	<ul class='ipsField_translatable'>
		<li class='ipsInputIcon'>
			<span class="ipsInputIcon__icon ipsFlag fa-solid fa-user"></span>
			<input type='text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[un]" data-role="usernameInput" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ftp_username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
 class="ipsInput ipsInput--text">
		</li>
		<li class='ipsInputIcon'>
			<span class="ipsInputIcon__icon ipsFlag fa-solid fa-lock"></span>
			<input type='password' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[pw]" data-role="passwordInput" placeholder="
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
 class="ipsInput ipsInput--text">
		</li>
		<li class='ipsInputIcon'>
			<span class="ipsInputIcon__icon ipsFlag fa-solid fa-folder"></span>
			<input type='text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[path]" data-role="pathInput" placeholder="/path/" 
IPSCONTENT;

if ( isset( $value['path'] ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['path'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--text">
		</li>
	</ul>
	
IPSCONTENT;

if ( $showBypassValidationCheckbox ):
$return .= <<<IPSCONTENT

		<ul>
			<li>
				<label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_bypassValidation'>
					<input type='checkbox' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[bypassValidation]" value="1" id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_bypassValidation'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ftp_bypass_validation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</label>
			</li>
		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function ftpDisplay( $value, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

	<div class="">
		<ul class="ipsList ipsList--inline">
			<li>
				
IPSCONTENT;

if ( $value['protocol'] === 'sftp' ):
$return .= <<<IPSCONTENT

					<span class="ipsBadge ipsBadge--style1">SFTP</span>
				
IPSCONTENT;

elseif ( $value['protocol'] === 'ssl_ftp' ):
$return .= <<<IPSCONTENT

					<span class="ipsBadge ipsBadge--style6">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ftp_with_ssl', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<span class="i-font-family_monospace">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['server'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $value['port'] ):
$return .= <<<IPSCONTENT
:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['port'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
			</li>
			<li>
				<i class="fa-solid fa-user"></i> <span class="i-font-family_monospace">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['un'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</li>
			<li>
				<i class="fa-solid fa-lock"></i> <span class="i-font-family_monospace">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['pw'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</li>
			
IPSCONTENT;

if ( $value['path'] ):
$return .= <<<IPSCONTENT

				<li>
					<i class="fa-solid fa-folder"></i> <span class="i-font-family_monospace">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['path'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $url ):
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--soft ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'connect', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function hCaptcha( $siteKey ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ipsCaptcha data-ipsCaptcha-service='hcaptcha' data-ipsCaptcha-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $siteKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class="h-captcha" data-sitekey="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $siteKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></div>
</div>

IPSCONTENT;

		return $return;
}

	function icon( $name, $value, $required, $maxIcons=null, $disabled=FALSE, $htmlId=NULL, $allowedTypes=['fa', 'emoji'], $svgIcons=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div
    id="elInput_
IPSCONTENT;

if ( ! empty($htmlId) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
    class="ipsInputIcon"
    data-controller="core.global.editor.icon"
    data-allowed-icon-types="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode(",", $allowedTypes), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
    
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
data-input-disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( is_int( $maxIcons ) ):
$return .= <<<IPSCONTENT
data-max-icons="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxIcons, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( $svgIcons ):
$return .= <<<IPSCONTENT
data-svg-icons
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
    <input
            class="ipsHide"
            type="text"
            name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
            value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode($value ?: []), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
            data-ips-icon-picker-input
            
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    />

    <button data-role="launcher" class="ipsButton ipsButton--primary" type="button">
IPSCONTENT;

if ( $maxIcons === null or $maxIcons > 1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_icon_choose_icons', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_icon_choose_icon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</button>
    <button data-role="clear" class="ipsButton ipsButton--negative 
IPSCONTENT;

if ( !empty( $value ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type="button">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_icon_clear_icon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
    <ul data-role="selectedicons" class="ipsIconPicker__selectedIcons"></ul>
</div>

IPSCONTENT;

		return $return;
}

	function interval( $name, $valueNumber, $selectedUnit, $required, $unlimited, $unlimitedLang, $unlimitedToggles, $unlimitedToggleOn, $valueToggles, $minSeconds, $maxSeconds, $disabled=FALSE, $suffix = NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input
	type="number"
	name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[val]"
	size="5"
	
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $unlimited !== NULL and $valueNumber == $unlimited ):
$return .= <<<IPSCONTENT

		value=""
		data-jsdisable="true"
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $valueNumber, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	class="ipsInput ipsInput--text ipsField_short"
	
IPSCONTENT;

if ( $minSeconds !== NULL ):
$return .= <<<IPSCONTENT

		min="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( floor($minSeconds/86400), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $maxSeconds !== NULL ):
$return .= <<<IPSCONTENT

		max="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxSeconds, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	step="any"
	
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT

		disabled aria-disabled='true'
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $valueToggles ) ):
$return .= <<<IPSCONTENT

		data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $valueToggles ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
<select class="ipsInput ipsInput--select ipsInput--auto" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[unit]" 
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( $maxSeconds === NULL or $maxSeconds >= 604800 ):
$return .= <<<IPSCONTENT

		<option value="w" 
IPSCONTENT;

if ( $selectedUnit === 'w' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'weeks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ( $maxSeconds === NULL or $maxSeconds >= 86400 ) and ( $minSeconds === NULL or $minSeconds < 604800 ) ):
$return .= <<<IPSCONTENT

		<option value="d" 
IPSCONTENT;

if ( $selectedUnit === 'd' ):
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
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ( $maxSeconds === NULL or $maxSeconds >= 3600 ) and ( $minSeconds === NULL or $minSeconds < 86400 ) ):
$return .= <<<IPSCONTENT

		<option value="h" 
IPSCONTENT;

if ( $selectedUnit === 'h' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hours', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ( $maxSeconds === NULL or $maxSeconds >= 60 ) and ( $minSeconds === NULL or $minSeconds < 3600 ) ):
$return .= <<<IPSCONTENT

		<option value="i" 
IPSCONTENT;

if ( $selectedUnit === 'i' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'minutes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $minSeconds === NULL or $minSeconds < 60 ):
$return .= <<<IPSCONTENT

		<option value="s" 
IPSCONTENT;

if ( $selectedUnit === 's' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'seconds', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</select>

IPSCONTENT;

if ( \is_string( $suffix ) ):
$return .= <<<IPSCONTENT

	{$suffix}

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $unlimited !== NULL ):
$return .= <<<IPSCONTENT

	&nbsp;
	<div class="ipsFieldRow__inlineCheckbox">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		&nbsp;
		<input
			type="checkbox"
			data-control="unlimited
IPSCONTENT;

if ( \count($unlimitedToggles) ):
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
[unlimited]"
			id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-unlimitedCheck'
			value="1"
			
IPSCONTENT;

if ( $unlimited == $valueNumber ):
$return .= <<<IPSCONTENT

				checked
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT

				disabled aria-disabled='true'
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $unlimitedToggles ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $unlimitedToggleOn === FALSE ):
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
"
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			aria-labelledby='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'
			class="ipsInput ipsInput--toggle"
		>
		<label for='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-unlimitedCheck' id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label' class='ipsField_unlimited'>
			
IPSCONTENT;

$val = "{$unlimitedLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</label>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function item( $name, $value, $maxItems, $minAjaxLength, $datasource, $template ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_values" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', array_keys($value) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
<input
	type="text"
	class="ipsInput ipsInput--text"
	name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	value=""
	id="elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	data-ipsContentItem
	
IPSCONTENT;

if ( $maxItems ):
$return .= <<<IPSCONTENT
data-ipsContentItem-maxItems="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxItems, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	data-ipsContentItem-dataSource="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $datasource, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	data-ipsContentItem-minAjaxLength="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $minAjaxLength, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
>
<ul data-contentItem-results="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsContentItemSelector">

IPSCONTENT;

if ( \is_array($value) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $value as $item ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$html = $template( $item );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$idColumn = $item::$databaseColumnId;
$return .= <<<IPSCONTENT

		<li data-id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->$idColumn, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<span class='cContentItem_delete' data-action='delete'>&times;</span>
			{$html}
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</ul>

IPSCONTENT;

		return $return;
}

	function itemResult( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $item::$databaseColumnId;
$return .= <<<IPSCONTENT

<div data-itemid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role='contentItemRow'>
	<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
	
IPSCONTENT;

if ( $item->container() ):
$return .= <<<IPSCONTENT

		<em>
IPSCONTENT;

$sprintf = array($item->container()->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'item_selector_added_to_container', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</em>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $item::$databaseColumnMap['date'] ) ):
$return .= <<<IPSCONTENT

		<span class='i-color_soft'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts($item->mapped('date'))->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'item_selector_added_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
</div>


IPSCONTENT;

		return $return;
}

	function keyValue( $key, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsField_stackItem_keyValue">
	<span class="i-flex_00 i-align-self_center i-color_soft i-font-weight_500 i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_key_value_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
	<span data-ipsStack-keyvalue-name="key">{$key}</span>
	<span class="i-flex_00 i-align-self_center i-color_soft i-font-weight_500 i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_key_value_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
	<span data-ipsStack-keyvalue-name="key">{$value}</span>
</div>
IPSCONTENT;

		return $return;
}

	function matrix( $id, $headers, $rows, $action, $hiddenValues, $actionButtons, $langPrefix, $widths=array(), $manageable=TRUE, $checkAlls=array(), $checkAllRows=FALSE, $classes=array(), $showTooltips=FALSE, $squashFields=TRUE, $sortable=FALSE, $styledRowTitle=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsMatrix 
IPSCONTENT;

if ( $sortable ):
$return .= <<<IPSCONTENT
data-ipsMatrix-sortable='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsMatrix-manageable='
IPSCONTENT;

if ( $manageable ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $squashFields ):
$return .= <<<IPSCONTENT
data-ipsMatrix-squashFields
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

foreach ( $rows as $rowId => $row ):
$return .= <<<IPSCONTENT

		<input type='hidden' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_matrixRows[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rowId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]' data-matrixrowid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rowId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='1'>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $manageable ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->buttons( array( 'add' => array( 'link' => \IPS\Http\Url::internal( '#' ), 'icon' => 'plus', 'title' => 'add_button', 'class' => 'matrixAdd', 'data' => array( 'matrixID' => $id ) ) ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsTableScroll">
		<table class='ipsTable ipsMatrix 
IPSCONTENT;

if ( \count( $classes ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $classes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' role='grid' data-matrixID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->matrixRows( $headers, $rows, $langPrefix, $manageable, $widths, $checkAlls, $checkAllRows, $showTooltips, $sortable ? $id : FALSE, $styledRowTitle );
$return .= <<<IPSCONTENT

		</table>
	</div>
	<div class="ipsSubmitRow">
		
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT
{$button} 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
</form>
IPSCONTENT;

		return $return;
}

	function matrixNested( $id, $headers, $rows, $action, $hiddenValues, $actionButtons, $langPrefix, $widths=array(), $manageable=TRUE, $checkAlls=array(), $checkAllRows=FALSE, $classes=array(), $showTooltips=FALSE, $squashFields=TRUE, $sortable=FALSE, $styledRowTitle=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ipsMatrix data-ipsMatrix-manageable='
IPSCONTENT;

if ( $manageable ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $sortable ):
$return .= <<<IPSCONTENT
data-ipsMatrix-sortable='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $squashFields ):
$return .= <<<IPSCONTENT
data-ipsMatrix-squashFields
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

foreach ( $rows as $rowId => $row ):
$return .= <<<IPSCONTENT

		<input type='hidden' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_matrixRows[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rowId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]' data-matrixrowid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rowId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='1'>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $manageable ):
$return .= <<<IPSCONTENT

		<div class="i-padding_2">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'admin' )->buttons( array( 'add' => array( 'link' => \IPS\Http\Url::internal( '#' ), 'icon' => 'plus', 'title' => 'add_button', 'class' => 'matrixAdd', 'data' => array( 'matrixID' => $id ) ) ) );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsTableScroll">
		<table class='ipsTable ipsMatrix 
IPSCONTENT;

if ( \count( $classes ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $classes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' role='grid' data-matrixID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->matrixRows( $headers, $rows, $langPrefix, $manageable, $widths, $checkAlls, $checkAllRows, $showTooltips, $sortable ? $id : FALSE, $styledRowTitle );
$return .= <<<IPSCONTENT

		</table>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function matrixRows( $headers, $rows, $langPrefix, $manageable=TRUE, $widths=array(), $checkAlls=array(), $checkAllRows=FALSE, $showTooltips=FALSE, $sortable=FALSE, $styledRowTitle=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<thead>
	<tr>
		
IPSCONTENT;

if ( $sortable ):
$return .= <<<IPSCONTENT

			<th></th>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $headers as $header ):
$return .= <<<IPSCONTENT

			<th class="ipsMatrixHeader i-text-align_center" style="width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $widths[ $header ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%">
				
IPSCONTENT;

$val = "{$langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( array_key_exists( $header, $checkAlls ) ):
$return .= <<<IPSCONTENT

					<br>
					<input type="checkbox" data-action="checkAll" name="__all[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $header, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-checkallheader="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $header, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $checkAlls[ $header ] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</th>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $manageable ):
$return .= <<<IPSCONTENT

		    <th></th>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</tr>
</thead>
<tbody>
	<tr role='row' class='ipsMatrix_empty 
IPSCONTENT;

if ( \count( $rows ) > 0 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		<td colspan="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $headers ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='i-padding_3 i-color_soft'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'matrix_no_rows', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</td>
	</tr>
	
IPSCONTENT;

foreach ( $rows as $rowId => $row ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \is_string( $row ) ):
$return .= <<<IPSCONTENT

			<tr>
				<th class="ipsMatrix_subHeader" colspan="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $headers ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">{$row}</th>
			</tr>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<tr data-matrixrowid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rowId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" role='row' 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->type && \IPS\Widget\Request::i()->type == $rowId ):
$return .= <<<IPSCONTENT
class='ipsMatrix_highlighted'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

if ( $sortable ):
$return .= <<<IPSCONTENT

					<td>
						<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $sortable, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_matrixOrder[]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rowId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="noMatrixSquash">
						<div class='ipsTree_drag ipsDrag'>
							<i class='ipsTree_dragHandle ipsDrag_dragHandle fa-solid fa-bars ipsJS_show' data-ipsTooltip data-ipsTooltip-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reorder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></i>
						</div>
					</td>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $headers as $header ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \is_object( $row[ $header ] ) ):
$return .= <<<IPSCONTENT

						<td role='gridcell' 
IPSCONTENT;

if ( $showTooltips ):
$return .= <<<IPSCONTENT
data-ipsTooltip title="
IPSCONTENT;

$val = "{$langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-col='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $header, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-title="
IPSCONTENT;

$val = "{$langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="
IPSCONTENT;

if ( $row[$header]->error ):
$return .= <<<IPSCONTENT
 ipsMatrix_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="unlimitedCatch">
							{$row[ $header ]->html()}
							
IPSCONTENT;

if ( $row[$header]->error ):
$return .= <<<IPSCONTENT

								<p class="i-color_warning">
IPSCONTENT;

$val = "{$row[$header]->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</td>
					
IPSCONTENT;

elseif ( \is_string( $row[ $header ] ) ):
$return .= <<<IPSCONTENT

						<td role='gridcell' class="ipsMatrix_rowTitle">
							<div 
IPSCONTENT;

if ( isset( $row['_level'] ) ):
$return .= <<<IPSCONTENT
style="margin-left: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $row['_level']*15, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                
IPSCONTENT;

if ( $styledRowTitle ):
$return .= <<<IPSCONTENT

                                    {$row[ $header ]}
                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								    <strong>
IPSCONTENT;

if ( $langPrefix === FALSE ):
$return .= <<<IPSCONTENT
{$row[ $header ]}
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$row[ $header ]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</strong>
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $checkAllRows ):
$return .= <<<IPSCONTENT

									<br>
                                    <div class="ipsButtons ipsButtons--start i-margin-top_1">
                                        <a href='#' data-action="checkRow" class='ipsButton ipsButton--soft ipsButton--tiny'><i class='fa-solid fa-plus'></i></a> <a href='#' data-action="unCheckRow" class='ipsButton ipsButton--soft ipsButton--tiny'><i class='fa-solid fa-minus'></i></a>
                                    </div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</td>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $manageable ):
$return .= <<<IPSCONTENT

					<td role='gridcell' class="ipsTable_controls">
							<span class="ipsJS_show">
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( array( 'add' => array( 'icon' => 'times-circle', 'title' => 'delete', 'class' => 'matrixDelete' ) ) );
$return .= <<<IPSCONTENT

							</span>
							<noscript>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->checkbox( $rowId . '_delete' );
$return .= <<<IPSCONTENT

							</noscript>
					</td>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</tr>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</tbody>
IPSCONTENT;

		return $return;
}

	function message( $lang, $id=NULL, $css='', $parse=FALSE ) {
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
	<div class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsMessage ipsMessage--form">
		<div class="ipsRichText">
			
IPSCONTENT;

if ( $parse ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				{$lang}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function node( $name, $value, $multiple, $url, $title, $roots, $zeroVal, $permCheck, $subnodes, $togglePerm=NULL, $toggleIds=array(), $disabledCallback=NULL, $zeroValTogglesOn=array(), $zeroValTogglesOff=array(), $autoPopulate=FALSE, $children=NULL, $nodeClass=NULL, $where=NULL, $disabledArray=array(), $noParentNodesTitle=NULL, $noParentNodes=array(), $clubs=FALSE, $togglePermPBR=TRUE, $toggleIdsOff=array(), $loadMoreLink=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsSelectTree ipsJS_show' data-name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsSelectTree data-ipsSelectTree-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT
data-ipsSelectTree-multiple
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsSelectTree-selected='{$value}'>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="" data-role="nodeValue">
	<div class='ipsSelectTree_value ipsSelectTree_placeholder'></div>
	<span class='ipsSelectTree_expand'><i class='fa-solid fa-chevron-down'></i></span>
	<div class='ipsSelectTree_nodes ipsHide'>
		
IPSCONTENT;

if ( $clubs ):
$return .= <<<IPSCONTENT

			<i-tabs class='ipsTabs ipsTabs--small ipsTabs--stretch' id='ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content'>
				<div role='tablist'>
					<button type="button" id='ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_global' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_global_panel" aria-selected="true">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node_selector_global', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</button>
					<button type="button" id='ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_clubs' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_clubs_panel" aria-selected="false">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node_selector_clubs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</button>
				</div>
			</i-tabs>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div data-role='nodeList' class='ipsScrollbar'>
			
IPSCONTENT;

if ( $clubs ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels'>
					<div id='ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_global_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_global" data-role="globalNodeList">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->nodeCascade( $roots, FALSE, $permCheck, $subnodes, $togglePerm, $toggleIds, $disabledCallback, $autoPopulate, $children, $nodeClass, $where, $disabledArray, $noParentNodesTitle, $noParentNodes, FALSE, $togglePermPBR, $toggleIdsOff );
$return .= <<<IPSCONTENT

					</div>
					<div id='ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_clubs_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_nodeSelect_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_clubs" data-role="clubNodeList" hidden>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->nodeCascade( $roots, FALSE, $permCheck, $subnodes, $togglePerm, $toggleIds, $disabledCallback, $autoPopulate, $children, $nodeClass, $where, $disabledArray, $noParentNodesTitle, $noParentNodes, TRUE, $togglePermPBR, $toggleIdsOff );
$return .= <<<IPSCONTENT

					</div>
				</div>		
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->nodeCascade( $roots, FALSE, $permCheck, $subnodes, $togglePerm, $toggleIds, $disabledCallback, $autoPopulate, $children, $nodeClass, $where, $disabledArray, $noParentNodesTitle, $noParentNodes, NULL, $togglePermPBR, $toggleIdsOff );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $loadMoreLink ):
$return .= <<<IPSCONTENT

			<div class='ipsSelectTree_loadMore' data-action='nodeLoadMore' data-offset='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $loadMoreLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				<a class='ipsButton ipsButton--soft ipsButton--wide'><span class='ipsLoading ipsLoading--tiny ipsHide'>&nbsp;</span>&nbsp;&nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_load_more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

if ( $zeroVal !== NULL ):
$return .= <<<IPSCONTENT

	<div class="i-margin-top_1">
		&nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
&nbsp;
		<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-zeroVal" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-zeroVal" data-role="zeroVal" 
IPSCONTENT;

if ( $value === 0 ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( !empty($zeroValTogglesOn) OR !empty($zeroValTogglesOff) ):
$return .= <<<IPSCONTENT
data-control="toggle"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( !empty($zeroValTogglesOn) ):
$return .= <<<IPSCONTENT
 data-togglesOn="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $zeroValTogglesOn ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-controls="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $zeroValTogglesOn ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( !empty($zeroValTogglesOff) ):
$return .= <<<IPSCONTENT
 data-togglesOff="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $zeroValTogglesOff ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-controls="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $zeroValTogglesOff ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle"> 
		<label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-zeroVal" class='ipsField_unlimited'>
IPSCONTENT;

$val = "{$zeroVal}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function nodeAutocomplete( $v ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ol class="ipsNodeSelect_breadcrumb">
	
IPSCONTENT;

foreach ( $v->parents() as $parent ):
$return .= <<<IPSCONTENT

		<li><span class="i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $parent->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-angle-right"></i></span></li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
</ol>
IPSCONTENT;

		return $return;
}

	function nodeCascade( $nodes, $results=FALSE, $permCheck=NULL, $subnodes=TRUE, $togglePerm=NULL, $toggleIds=array(), $disabledCallback=NULL, $autoPopulate=FALSE, $children=NULL, $nodeClass=NULL, $where=NULL, $disabledArray=array(), $noParentNodesTitle=NULL, $noParentNodes=array(), $clubs=NULL, $togglePermPBR=TRUE, $toggleIdsOff=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty( $nodes ) ):
$return .= <<<IPSCONTENT

	<p class='i-padding_2 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<ul>
		
IPSCONTENT;

foreach ( $nodes as $node ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $clubs === NULL or ( $clubs === TRUE and $node->club() ) or ( $clubs === FALSE and !$node->club() ) ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

if ( ( $permCheck === NULL or $node->can( $permCheck ) ) and ( $disabledCallback === NULL or $disabledCallback( $node ) ) and !\in_array( $node->_id, $disabledArray ) ):
$return .= <<<IPSCONTENT

						<div data-action="nodeSelect" class='ipsSelectTree_item 
IPSCONTENT;

if ( $node->hasChildren( 'view', NULL, $subnodes, $where ) ):
$return .= <<<IPSCONTENT
ipsSelectTree_withChildren
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $autoPopulate AND isset( $children[ $node->_id ] ) ):
$return .= <<<IPSCONTENT
ipsSelectTree_itemOpen
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $node->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $nodeClass and !( $node instanceof $nodeClass ) ):
$return .= <<<IPSCONTENT
.s
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-breadcrumb='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( array_values( array_map( function( $val ){ return isset( $val::$titleLangPrefix ) ? \IPS\Member::loggedIn()->language()->addToStack( $val::$titleLangPrefix . $val->_id, FALSE, array( 'json' => TRUE, 'escape' => TRUE, 'striptags' => TRUE ) ) : ( $val->_title ? $val->_title : $val->_title ); }, iterator_to_array( $node->parents() ) ) ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $togglePerm and $node->can( $togglePerm, NULL, $togglePermPBR ) ):
$return .= <<<IPSCONTENT
data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggleIds ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

elseif ( !$togglePerm and isset( $toggleIds[ $node->_id ] ) ):
$return .= <<<IPSCONTENT
data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggleIds[ $node->_id ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

elseif ( \count( $toggleIdsOff ) ):
$return .= <<<IPSCONTENT
data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggleIdsOff ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $autoPopulate AND isset( $children[ $node->_id ] ) ):
$return .= <<<IPSCONTENT
data-childrenloaded="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
							
IPSCONTENT;

if ( $node->hasChildren( 'view', NULL, $subnodes, $where ) ):
$return .= <<<IPSCONTENT

								<a href='#' data-action="getChildren" class='ipsSelectTree_toggle'></a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<span data-role="nodeTitle">
								
IPSCONTENT;

if ( $clubs === TRUE ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$sprintf = array($node->club()->name, $node->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_container_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $node->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</span>
						</div>
						
IPSCONTENT;

if ( $autoPopulate AND isset( $children[ $node->_id ] ) and \get_class( $node ) == ltrim( $nodeClass, '\\' ) ):
$return .= <<<IPSCONTENT

							<div data-role="childWrapper">
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->nodeCascade( $children[ $node->_id ], FALSE, $permCheck, $subnodes, $togglePerm, $toggleIds, $disabledCallback, FALSE, $children, $nodeClass, $where, $disabledArray, NULL, array(), NULL, $togglePermPBR, $toggleIdsOff );
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $disabledCallback === NULL or $disabledCallback( $node ) !== NULL ):
$return .= <<<IPSCONTENT

							<div class='ipsSelectTree_item ipsSelectTree_itemDisabled 
IPSCONTENT;

if ( $node->hasChildren( 'view', NULL, $subnodes, $where ) ):
$return .= <<<IPSCONTENT
ipsSelectTree_withChildren
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $autoPopulate AND isset( $children[ $node->_id ] ) ):
$return .= <<<IPSCONTENT
ipsSelectTree_itemOpen
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $node->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-breadcrumb='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( array_values( array_map( function( $val ){ return isset( $val::$titleLangPrefix ) ? \IPS\Member::loggedIn()->language()->addToStack( $val::$titleLangPrefix . $val->_id, FALSE, array( 'json' => TRUE, 'escape' => TRUE, 'striptags' => TRUE ) ) : ( $val->_title ? $val->_title : $val->_title ); }, iterator_to_array( $node->parents() ) ) ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $autoPopulate AND isset( $children[ $node->_id ] ) ):
$return .= <<<IPSCONTENT
data-childrenloaded="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								
IPSCONTENT;

if ( $node->hasChildren( 'view', NULL, $subnodes, $where ) ):
$return .= <<<IPSCONTENT

									<a href='#' data-action="getChildren" class='ipsSelectTree_toggle'></a>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<span data-role="nodeTitle">
									
IPSCONTENT;

if ( $clubs === TRUE ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$sprintf = array($node->club()->name, $node->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_container_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $node->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</span>
							</div>
							
IPSCONTENT;

if ( $autoPopulate AND isset( $children[ $node->_id ] ) ):
$return .= <<<IPSCONTENT

								<div data-role="childWrapper">
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->nodeCascade( $children[ $node->_id ], FALSE, $permCheck, $subnodes, $togglePerm, $toggleIds, $disabledCallback, FALSE, $children, $nodeClass, $where, $disabledArray, NULL, array(), NULL, $togglePermPBR, $toggleIdsOff );
$return .= <<<IPSCONTENT

								</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
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

if ( $noParentNodesTitle and \count( $noParentNodes ) ):
$return .= <<<IPSCONTENT

			<li>
				<div class='ipsSelectTree_item ipsSelectTree_itemDisabled ipsSelectTree_withChildren 
IPSCONTENT;

if ( $autoPopulate ):
$return .= <<<IPSCONTENT
ipsSelectTree_itemOpen
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-id="0" data-breadcrumb='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( array() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $autoPopulate ):
$return .= <<<IPSCONTENT
data-childrenloaded="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<a href='#' data-action="getChildren" class='ipsSelectTree_toggle'></a>
					<span data-role="nodeTitle">
IPSCONTENT;

$val = "{$noParentNodesTitle}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</div>
				
IPSCONTENT;

if ( $autoPopulate ):
$return .= <<<IPSCONTENT

					<div data-role="childWrapper">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->nodeCascade( $noParentNodes, FALSE, $permCheck, $subnodes, $togglePerm, $toggleIds, $disabledCallback, $autoPopulate, $children, $nodeClass, $where, $disabledArray, NULL, array(), NULL, $togglePermPBR, $toggleIdsOff );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

	function number( $name, $value, $required, $unlimited=NULL, $range=FALSE, $min=NULL, $max=NULL, $step=NULL, $decimals=0, $unlimitedLang='unlimited', $disabled=FALSE, $suffix=NULL, $toggles=array(), $toggleOn=TRUE, $valueToggles=array(), $id=NULL, $prefix=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $range ):
$return .= <<<IPSCONTENT

	<div class="i-flex i-align-items_center i-gap_2">
		
IPSCONTENT;

if ( $min !== NULL ):
$return .= <<<IPSCONTENT

			<strong class='i-flex_00 i-font-size_-2' data-role='rangeBoundary' hidden>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $min, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<input
	type="
IPSCONTENT;

if ( $range ):
$return .= <<<IPSCONTENT
range
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
number
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
	name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

if ( $id !== NULL ):
$return .= <<<IPSCONTENT

		id="elNumber_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	size="5"
	
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $unlimited !== NULL and $value === $unlimited ):
$return .= <<<IPSCONTENT

		value=""
		data-jsdisable="true"
	
IPSCONTENT;

elseif ( \is_numeric( $value ) ):
$return .= <<<IPSCONTENT

		value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( $value, $decimals === true ? mb_strlen( mb_substr( $value, mb_strpos( $value, '.' ) + 1 ) ) : $decimals, '.', '' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		value="0"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	class="ipsInput 
IPSCONTENT;

if ( $range ):
$return .= <<<IPSCONTENT
ipsInput--range
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsInput--text
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

if ( $min !== NULL ):
$return .= <<<IPSCONTENT

		min="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $min, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $max !== NULL ):
$return .= <<<IPSCONTENT

		max="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $max, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $step !== NULL and $step != 'any' ):
$return .= <<<IPSCONTENT

		step="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( $step, $decimals, '.', '' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		step="any"
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count($valueToggles) ):
$return .= <<<IPSCONTENT
data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $valueToggles ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>


IPSCONTENT;

if ( $range ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $max !== NULL ):
$return .= <<<IPSCONTENT

		<strong class='i-flex_00 i-font-size_-2' data-role='rangeBoundary'><span id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_rangeValue' data-role='rangeValue'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> / 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $max, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \is_string( $suffix ) ):
$return .= <<<IPSCONTENT

	{$suffix}

IPSCONTENT;

elseif ( isset( $suffix['preUnlimited'] ) ):
$return .= <<<IPSCONTENT

	{$suffix['preUnlimited']}

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $unlimited !== NULL ):
$return .= <<<IPSCONTENT

	&nbsp;
	<div class="ipsFieldRow__inlineCheckbox">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		&nbsp;
		<input type="checkbox" data-control="unlimited
IPSCONTENT;

if ( \count($toggles) ):
$return .= <<<IPSCONTENT
 toggle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" name="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-unlimitedCheck' value="
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

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \count($toggles) ):
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

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label' class="ipsInput ipsInput--toggle">
		<label for='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-unlimitedCheck' id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label' class='ipsField_unlimited'>
IPSCONTENT;

$val = "{$unlimitedLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \is_array( $suffix ) and isset( $suffix['postUnlimited'] ) ):
$return .= <<<IPSCONTENT

	&nbsp;&nbsp;&nbsp;{$suffix['postUnlimited']}

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function numberRange( $start, $end ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'between', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 {$start} 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 {$end}
IPSCONTENT;

		return $return;
}

	function poll( $name, $value, $pollData, $allowPollOnly ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.front.core.pollEditor' data-pollName='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-maxQuestions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->max_poll_questions, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-maxChoices="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->max_poll_choices, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='cPoll'>	
	<noscript>
		
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[fallback]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->pid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_no_js', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</noscript>
	<div class='ipsForm ipsForm--vertical ipsForm--poll ipsJS_show'>
		<ul class=''>
			<li class='ipsFieldRow'>
				<input type='text' class='ipsInput ipsInput--text ipsInput--primary ipsInput--wide' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[title]" maxlength="255" 
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->poll_question, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			</li>
			<li class='ipsFieldRow'>
				<ul class='ipsFieldRow__content ipsFieldRow__content--checkboxes'>
					
IPSCONTENT;

if ( \IPS\Settings::i()->poll_allow_public ):
$return .= <<<IPSCONTENT

						<li>
							<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[public]" id='elPoll_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_public' 
IPSCONTENT;

if ( $value and $value->poll_view_voters ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
							<label for='elPoll_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_public'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'make_votes_public', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Settings::i()->ipb_poll_only and $allowPollOnly ):
$return .= <<<IPSCONTENT

						<li>
							<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[poll_only]" id='elPoll_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_pollOnly' 
IPSCONTENT;

if ( $value and $value->poll_only ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
							<label for='elPoll_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_pollOnly'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_only_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li>
						<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[has_close_date]" id='elPoll_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_hasCloseDate' value='1' data-control="toggle" data-toggles='elPoll_closeDate' 
IPSCONTENT;

if ( ($value and $value->poll_close_date instanceof \IPS\DateTime) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
						<label for='elPoll_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_hasCloseDate'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_specify_close_time', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					</li>
				</ul>
			</li>
			<li class='ipsFieldRow' id='elPoll_closeDate'>
				<label class='ipsFieldRow__label' for='poll_close_date'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_close_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<div class='ipsFieldRow__content'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->date( $name . '[poll_close_date]', ( $value and $value->poll_close_date instanceof \IPS\DateTime ) ? $value->poll_close_date : ( isset( \IPS\Request::i()->topic_poll['poll_close_date'] ) ? \IPS\Request::i()->topic_poll['poll_close_date'] : \IPS\DateTime::create()->add( new \DateInterval( 'P1D' ) ) ), NULL, NULL, FALSE, FALSE, $name . '[poll_close_time]' );
$return .= <<<IPSCONTENT

				</div>
			</li>
		</ul>
	</div>

	<section data-role='pollContainer'>

	</section>

	<ul class='ipsButtons i-padding_2'>
		<li><a href='#' data-action='addQuestion' class='ipsButton ipsButton--inherit ipsJS_show' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_poll_question_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-plus'></i> <span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_poll_question', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>		
	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function radio( $name, $value, $required, $options, $disabled=FALSE, $toggles=array(), $descriptions=array(), $warnings=array(), $userSuppliedInput='', $unlimited=NULL, $unlimitedLang=NULL, $htmlId=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $unlimited !== NULL ):
$return .= <<<IPSCONTENT

	<ul class="ipsFieldList ipsFieldList--radio" role="radiogroup">
		<li>
			<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimited, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_{unlimited}_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-control="toggle" data-togglesOff="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $value === $unlimited ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
			<div class='ipsFieldList__content'>
				<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_{unlimited}_
IPSCONTENT;

if ( ! empty($htmlId) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

$val = "{$unlimitedLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</label>
			</div>
		</li>
	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<input type="hidden" name="radio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__empty" value='1'>
<ul class="ipsFieldList ipsFieldList--radio" role="radiogroup" id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;

if ( ! empty($htmlId) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">

IPSCONTENT;

foreach ( $options as $k => $v ):
$return .= <<<IPSCONTENT

	<li>
		<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $value == (string) $k or ( isset( $userSuppliedInput ) and !\in_array( $value, array_keys( $options ) ) and $k == $userSuppliedInput ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled === TRUE or ( \is_array( $disabled ) and \in_array( $k, $disabled ) ) ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $toggles[ $k ] ) and !empty( $toggles[ $k ] ) ):
$return .= <<<IPSCONTENT
data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles[ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
		<div class='ipsFieldList__content'>
			<label for="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>{$v}</label>
			
IPSCONTENT;

if ( !empty( $userSuppliedInput ) AND $userSuppliedInput == $k ):
$return .= <<<IPSCONTENT

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

if ( !\in_array( $value, array_keys( $options ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $userSuppliedInput, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;

if ( ! empty($htmlId) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $descriptions[ $k ] ) ):
$return .= <<<IPSCONTENT

				{$descriptions[ $k ]}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $warnings[ $k ] ) ):
$return .= <<<IPSCONTENT

				{$warnings[ $k ]}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function radioImages( $name, $value, $required, $options, $disabled=FALSE, $toggles=array(), $descriptions=array(), $warnings=array(), $userSuppliedInput='', $unlimited=NULL, $unlimitedLang=NULL, $htmlId=NULL, $gridspan=3 ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!-- Used for screenshot selection: Eg. Downloads -->
<div class="ipsGrid ipsGrid--forms-radio-images ipsAttachment_fileList ipsAttachment_fileList--radios">
	
IPSCONTENT;

foreach ( $options as $k => $v ):
$return .= <<<IPSCONTENT

		<div class='ipsAttach ipsImageAttach ipsAttach_done'>
			<label>
				<div class="ipsImageAttach_thumb" data-role='preview'>
					<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
				</div>
				<div>
					<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $value == (string) $k or ( isset( $userSuppliedInput ) and !\in_array( $value, array_keys( $options ) ) and $k == $userSuppliedInput ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled === TRUE or ( \is_array( $disabled ) and \in_array( $k, $disabled ) ) ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $toggles[ $k ] ) and !empty( $toggles[ $k ] ) ):
$return .= <<<IPSCONTENT
data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles[ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
				</div>
				
IPSCONTENT;

if ( isset( $descriptions[ $k ] ) ):
$return .= <<<IPSCONTENT

					<div class='i-color_soft'>{$descriptions[ $k ]}</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</label>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function rating( $name, $value, $required, $max=5, $display=NULL, $userRated=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input type='hidden' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value="0">
<div data-ipsRating data-ipsRating-changeRate='true' 
IPSCONTENT;

if ( $display ):
$return .= <<<IPSCONTENT
data-ipsRating-value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( $display, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $userRated ):
$return .= <<<IPSCONTENT
data-ipsRating-userRated="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $userRated, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

foreach ( range( 1, $max ) as $i ):
$return .= <<<IPSCONTENT

		<input type='radio' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $i == floor( (float) $value ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function rowDesc( $label, $element, $required=FALSE, $error=NULL, $prefix=NULL, $suffix=NULL, $id=NULL, $object=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Dispatcher::i()->controllerLocation == 'admin' AND !( $object instanceof \IPS\Helpers\Form\Address ) AND !( $object instanceof \IPS\Helpers\Form\Upload ) AND !( $object instanceof \IPS\Helpers\Form\Node ) AND !( $object instanceof \IPS\Helpers\Form\Codemirror ) ):
$return .= <<<IPSCONTENT
<!-- <br> -->
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='ipsFieldRow__desc'>%s</div>
IPSCONTENT;

		return $return;
}

	function rowWarning( $label, $element, $required=FALSE, $error=NULL, $prefix=NULL, $suffix=NULL, $id=NULL, $object=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div 
IPSCONTENT;

if ( $id !== NULL ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_warning"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<p class='ipsMessage ipsMessage--warning i-margin-top_2'>%s</p>
</div>
IPSCONTENT;

		return $return;
}

	function sort( $name, $value, $options ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsField_stack" data-ipsStack data-ipsStack-sortable data-ipsStack-fieldName="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<ul data-role="stack">
	
IPSCONTENT;

$i = 0;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $value as $id => $val ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$i++;
$return .= <<<IPSCONTENT

		<li class='ipsField_stackItem' data-role="stackItem">
			<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $val, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<div class="i-cursor_move" data-action='stackDrag'>
				<span class="ipsField_stackDrag ipsDrag ipsJS_show">
					<i class='fa-solid fa-bars ipsDrag_dragHandle'></i>
				</span>
				<div data-ipsStack-wrapper class="i-padding_2">
					
IPSCONTENT;

if ( $options['checkboxes'] ):
$return .= <<<IPSCONTENT

						<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_checkboxes[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" 
IPSCONTENT;

if ( $val ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
						<label for="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

$val = "{$options['checkboxes']}{$id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</label>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $val, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function stack( $name, $fields, $options=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsField_stack" data-ipsStack data-ipsStack-sortable data-ipsStack-fieldName="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $options['maxItems'] ) ):
$return .= <<<IPSCONTENT
data-ipsStack-maxItems="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $options['maxItems'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<ul data-role="stack">
	
IPSCONTENT;

foreach ( $fields as $field ):
$return .= <<<IPSCONTENT

		<li class='ipsField_stackItem' data-role="stackItem">
			<span class="ipsField_stackDrag ipsDrag ipsJS_show" data-action='stackDrag'>
				<i class='fa-solid fa-bars ipsDrag_dragHandle'></i>
			</span>
			<div data-ipsStack-wrapper>{$field}</div>
			<input type="submit" class="ipsField_stackDelete ipsJS_hide" name="form_remove_stack[
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($field), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="&cross;">
			<a href='#' class="ipsField_stackDelete ipsJS_show" data-action="stackDelete">
				&times;
			</a>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
	<div class='i-margin-top_2'>
		<a class="ipsField_stackAdd ipsButton ipsButton--soft ipsButton--small ipsJS_show" href='#' data-action="stackAdd" role="button"><i class='fa-solid fa-plus-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stack_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
	<input type="submit" class="ipsJS_hide" name="form_add_stack" value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stack_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
</div>
IPSCONTENT;

		return $return;
}

	function text( $name, $type, $value, $required, $maxlength=NULL, $size=NULL, $disabled=FALSE, $autoComplete=NULL, $placeholder=NULL, $regex=NULL, $nullLang=NULL, $htmlId=NULL, $showMeter=FALSE, $htmlAutocomplete=NULL, $pwCheckAgainstMember=NULL, $pwCheckAgainstRequest=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $showMeter ):
$return .= <<<IPSCONTENT

	<div data-ipsPasswordStrength
IPSCONTENT;

if ( \IPS\Settings::i()->password_strength_meter_enforce ):
$return .= <<<IPSCONTENT
 data-ipsPasswordStrength-enforced data-ipsPasswordStrength-enforcedStrength='
IPSCONTENT;

$return .= \IPS\Settings::i()->password_strength_option;
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $pwCheckAgainstMember !== NULL ):
$return .= <<<IPSCONTENT
 data-ipsPasswordStrength-checkAgainstMember='
IPSCONTENT;

$return .= json_encode( array( $pwCheckAgainstMember->name, $pwCheckAgainstMember->email ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( \count( $pwCheckAgainstRequest ) ):
$return .= <<<IPSCONTENT
 data-ipsPasswordStrength-checkAgainstRequest='
IPSCONTENT;

$return .= json_encode($pwCheckAgainstRequest);
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $autoComplete ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->autocomplete( $name, $value, $required, $maxlength, $disabled, '', $placeholder, $nullLang, $autoComplete );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<input
		class="ipsInput ipsInput--text"
		type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		
IPSCONTENT;

if ( !is_null( $value ) ):
$return .= <<<IPSCONTENT

		value="
IPSCONTENT;

if ( \is_array( $value ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $value ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		id="elInput_
IPSCONTENT;

if ( ! empty($htmlId) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
		
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $maxlength !== NULL ):
$return .= <<<IPSCONTENT
maxlength="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxlength, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $size !== NULL ):
$return .= <<<IPSCONTENT
size="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $placeholder !== NULL ):
$return .= <<<IPSCONTENT
placeholder='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $placeholder, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $regex !== NULL and $regex ):
$return .= <<<IPSCONTENT
pattern="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $regex, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $htmlAutocomplete !== NULL and $htmlAutocomplete ):
$return .= <<<IPSCONTENT
autocomplete="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $htmlAutocomplete, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	>
	
IPSCONTENT;

if ( $showMeter ):
$return .= <<<IPSCONTENT

		<div data-role='strengthInfo' class='ipsHide'>
			<meter max="100" value="0" class='ipsForm_meter' data-role='strengthMeter'></meter>
			<span data-role='strengthText' class='ipsForm_meterAdvice'></span>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $nullLang ):
$return .= <<<IPSCONTENT

		<div class="ipsFieldRow__inlineCheckbox">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
			<input type="checkbox" data-control="unlimited" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null" id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null' value="1" 
IPSCONTENT;

if ( $value === NULL ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null_label' class="ipsInput ipsInput--toggle">
			<label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null_label' class='ipsField_unlimited'>
IPSCONTENT;

$val = "{$nullLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $showMeter ):
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function textarea( $name, $value, $required, $maxlength=NULL, $disabled=FALSE, $class='', $placeholder='', $nullLang=NULL, $tags=array(), $rows=NULL, $allOptions=[] ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $tags ) or !empty( $allOptions['tagSource'] ) ):
$return .= <<<IPSCONTENT

<div
    class='ipsColumns'
    data-controller='core.global.editor.customtags'
    data-tagFieldType='
IPSCONTENT;

if ( @$allOptions["codeMode"] ):
$return .= <<<IPSCONTENT
codebox
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
text
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
    data-tagFieldID='elTextarea_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
    
IPSCONTENT;

if ( !empty($allOptions['tagSource']) ):
$return .= <<<IPSCONTENT
data-tagsource="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $allOptions['tagSource'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
	<div class='ipsColumns__primary'>
		<div data-role="editor">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<textarea
	name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	id='elTextarea_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	value="
IPSCONTENT;

if ( $value !== null  ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
	class="ipsInput ipsInput--text ipsInput--wide 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

if ( $rows !== NULL ):
$return .= <<<IPSCONTENT
rows="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rows, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $maxlength !== NULL ):
$return .= <<<IPSCONTENT
maxlength="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxlength, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
aria-required='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $placeholder ):
$return .= <<<IPSCONTENT
placeholder="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $placeholder, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( @$allOptions['codeMode'] ):
$return .= <<<IPSCONTENT
data-ipscodebox
IPSCONTENT;

if ( !empty( $allOptions['codeModeAllowedLanguages'] ) ):
$return .= <<<IPSCONTENT
 data-ipscodebox-allowed-languages=
IPSCONTENT;

$return .= json_encode($allOptions['codeModeAllowedLanguages']);
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
IPSCONTENT;

if ( $value !== null  ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</textarea>

IPSCONTENT;

if ( !empty( $tags ) or !empty( $allOptions['tagSource'] ) ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $nullLang ):
$return .= <<<IPSCONTENT

	<div class="ipsFieldRow__inlineCheckbox">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
		<input type="checkbox" data-control="unlimited" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null" id="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null" value="1" 
IPSCONTENT;

if ( $value === NULL ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-controls='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-labelledby='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null_label' class="ipsInput ipsInput--toggle">
		<label for='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null' id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_null_label' class='ipsField_unlimited'>
IPSCONTENT;

$val = "{$nullLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $tags ) or !empty( $allOptions['tagSource'] ) ):
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsColumns__secondary ipsComposeArea_sidebar'>
		<h3 class='i-background_3 i-padding_2 ipsTitle ipsTitle--h4' data-role='tagsHeader'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<ul class='ipsScrollbar' data-role='tagsList'>
            
IPSCONTENT;

$tagLinks = $allOptions['tagLinks'] ?? null;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->editorTags( $tags, $tagLinks );
$return .= <<<IPSCONTENT

        </ul>
	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function translatable( $name, $languages, $values, $editors, $placeholder, $textarea=FALSE, $required=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $languages ) === 1 ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $languages as $lang ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !isset( $editors[ $lang->id ] )  ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $textarea ):
$return .= <<<IPSCONTENT

				<textarea class='ipsInput ipsInput--text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]">
IPSCONTENT;

if ( isset($values[ $lang->id ]) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $values[ $lang->id ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</textarea>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<input type='text' class='ipsInput ipsInput--text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
IPSCONTENT;

if ( isset($values[ $lang->id ]) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $values[ $lang->id ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $placeholder !== NULL ):
$return .= <<<IPSCONTENT
placeholder='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $placeholder, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			{$editors[ $lang->id ]->html()}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $textarea ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $languages as $lang ):
$return .= <<<IPSCONTENT

			<span class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></span> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $required and $lang->default ):
$return .= <<<IPSCONTENT
<span class="ipsFieldRow__required">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<br>
			<br>
			<textarea name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" aria-label='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsInput ipsInput--text 
IPSCONTENT;

if ( !$required || !$lang->default ):
$return .= <<<IPSCONTENT
 ipsFieldRow_errorExclude
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( isset( $values[ $lang->id ]) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $values[ $lang->id ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</textarea><br>
			<br>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<ul class='ipsForm ipsForm--translatable'>
			
IPSCONTENT;

foreach ( $languages as $lang ):
$return .= <<<IPSCONTENT

				<li class='ipsFieldRow'>
					<div class='ipsFieldList__content'>
						
IPSCONTENT;

if ( !isset( $editors[ $lang->id ] )  ):
$return .= <<<IPSCONTENT

							<div class="ipsInputIcon">
								<span class="ipsInputIcon__icon 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></span>
								<input type='text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" aria-label='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' placeholder="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $required and $lang->default ):
$return .= <<<IPSCONTENT
 - 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $values[ $lang->id ]) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $values[ $lang->id ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--text 
IPSCONTENT;

if ( !$required || !$lang->default ):
$return .= <<<IPSCONTENT
ipsFieldRow_errorExclude
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							</div>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<p class='ipsFlagEditor'>
								<span class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></span> <span class='ipsFlagLabel'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								
IPSCONTENT;

if ( $required and $lang->default ):
$return .= <<<IPSCONTENT
<span class="ipsFieldRow__required">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</p>
							{$editors[ $lang->id ]->html()}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function trbl( $name, $top, $right, $bottom, $left ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsWidthHeight_container">
	<div class="ipsWidthHeight_controls">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_input_top', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]" class="ipsInput ipsInput--text ipsField_tiny" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $top, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_input_top', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_input_right', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]" class="ipsInput ipsInput--text ipsField_tiny" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $right, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_input_right', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_input_bottom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]" class="ipsInput ipsInput--text ipsField_tiny" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $bottom, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_input_bottom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_input_left', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[3]" class="ipsInput ipsInput--text ipsField_tiny" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $left, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_input_left', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'px', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function upload( $name, $value, $minimize, $maxFileSize, $maxFiles, $maxChunkSize, $totalMaxSize, $allowedFileTypes, $pluploadKey, $multiple=FALSE, $editor=FALSE, $forceNoscript=FALSE, $template='core.attachments.fileItem', $existing=array(), $default=NULL, $supportsDelete = TRUE, $allowStockPhotos=FALSE ) {
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

if ( $file->tempId ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->tempId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
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

					</span>
					<span class="i-color_soft i-font-size_-2">
						
IPSCONTENT;

if ( $allowedFileTypes !== NULL ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_accepted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:
							
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

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_total_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:
							
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

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_max', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:
							
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
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function uploadDisplay( $file, $downloadUrl ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $file->isImage() ):
$return .= <<<IPSCONTENT

	<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsImage" data-ipsLightbox>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $downloadUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function uploadFile( $id, $file, $name=NULL, $editor=FALSE, $showAsImages=FALSE, $link=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- v5 todo: This is used as the "Other Media" attachment modal when posting new content -->

IPSCONTENT;

if ( $showAsImages ):
$return .= <<<IPSCONTENT

	<div data-action='selectFile' class='ipsAttach ipsImageAttach 
IPSCONTENT;

if ( $editor ):
$return .= <<<IPSCONTENT
ipsAttach_done
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='file' data-fileid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $link ):
$return .= <<<IPSCONTENT
data-filelink="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-fileType="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->mediaType(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $file->attachmentThumbnailUrl ):
$return .= <<<IPSCONTENT
data-thumbnailurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->attachmentThumbnailUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $file->isImage() or $file->isVideo() ):
$return .= <<<IPSCONTENT
data-fullsizeurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $file->isVideo() ):
$return .= <<<IPSCONTENT
data-mimeType="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getMimeType( $file->originalFilename ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<ul class='ipsImageAttach_controls'>
			
IPSCONTENT;

if ( $editor ):
$return .= <<<IPSCONTENT

				<li><button type="button" class='ipsAttach_selection' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_insert_one', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-check'></i></button></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $name ):
$return .= <<<IPSCONTENT

				<li>
					<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_keep[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1">
					<a href='#' data-role='deleteFile' class='ipsButton ipsButton--small ipsButton--soft' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_media_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-regular fa-trash-can'></i></a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
		
IPSCONTENT;

$screenshot = isset( $file->screenshot ) ? $file->screenshot : $file;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$extension =  mb_strtolower( mb_substr( $screenshot->originalFilename, mb_strrpos( $screenshot->originalFilename, '.' ) + 1 ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \in_array( $extension, \IPS\Image::supportedExtensions() ) ):
$return .= <<<IPSCONTENT

			<div class='ipsImageAttach_thumb' data-role='preview'>
				<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $screenshot->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt=''>
			</div>
		
IPSCONTENT;

elseif ( \in_array( $extension, \IPS\File::$videoExtensions ) ):
$return .= <<<IPSCONTENT

			<div class='ipsImageAttach_thumb' data-role='preview'>
				<video>
					<source src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $screenshot->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" type="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getMimeType( $screenshot->originalFilename ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				</video>
			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='ipsImageAttach_thumb' data-role='preview'>
				<div class='ipsThumb'><i></i></div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
		
		<h2 class='ipsAttach_title ipsTruncate_1' data-role='title'>
IPSCONTENT;

if ( isset( $file->contextInfo ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->contextInfo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
		<p class='i-color_soft i-font-size_-2 ipsTruncate_1'><span data-role='status'>
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $file->filesize() );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

if ( isset( $file->contextInfo ) ):
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div data-action='selectFile' class='ipsData__item ipsAttach 
IPSCONTENT;

if ( $editor ):
$return .= <<<IPSCONTENT
ipsAttach_done
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $file->attachmentThumbnailUrl ):
$return .= <<<IPSCONTENT
data-thumbnailurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->attachmentThumbnailUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role='file' data-fileid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $link ):
$return .= <<<IPSCONTENT
data-filelink="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-fileType="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->mediaType(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<div class='i-basis_100 ipsResponsive_hidePhone i-text-align_center'>
			
IPSCONTENT;

if ( \in_array( mb_strtolower( mb_substr( $file->filename, mb_strrpos( $file->filename, '.' ) + 1 ) ), \IPS\Image::supportedExtensions() ) ):
$return .= <<<IPSCONTENT

				<img src="
IPSCONTENT;

if ( $file->attachmentThumbnailUrl ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->attachmentThumbnailUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-file i-font-size_2'></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsData__main'>
			<h2 class='ipsData__title ipsAttach_title ipsTruncate_1' data-role='title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
			<p class='ipsData__meta'>
				
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $file->filesize() );
$return .= <<<IPSCONTENT

			</p>
		</div>
		<div class='i-basis_260 i-color_soft' data-role='status'>
			
		</div>
		<div class='i-basis_100 i-text-align_end'>
			<ul class='ipsList ipsList--inline'>
				
IPSCONTENT;

if ( $editor ):
$return .= <<<IPSCONTENT

					<li><button type="button" class='ipsAttach_selection' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'form_upload_insert_one', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-check'></i></button></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $name ):
$return .= <<<IPSCONTENT

					<li>
						<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_keep[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1">
						<a href='#' data-role='deleteFile' class='ipsButton ipsButton--small ipsButton--soft' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editor_media_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-regular fa-trash-can'></i></a>
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

		return $return;
}

	function widthheight( $name, $width, $height, $unlimited, $unlimitedLang, $image=NULL, $resizableDiv=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsWidthHeight_container">
	
IPSCONTENT;

if ( $image !== NULL ):
$return .= <<<IPSCONTENT

		<img class="ipsJS_show ipsWidthHeight" data-control="dimensions" src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" style="width:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px; height:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px">
	
IPSCONTENT;

elseif ( $image === NULL AND $resizableDiv === TRUE ):
$return .= <<<IPSCONTENT

		<div class="ipsJS_show ipsWidthHeight" data-control="dimensions" style="width:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px; height:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px"></div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsWidthHeight_controls">
		<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]" class="ipsInput ipsInput--text ipsField_short ipsWidthHeight_width" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'width', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'> &times; <input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]" class="ipsInput ipsInput--text ipsField_short ipsWidthHeight_height" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'height', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'px', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $unlimited !== NULL ):
$return .= <<<IPSCONTENT

			<div class="ipsFieldRow__inlineCheckbox">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				<input type="checkbox" role='checkbox' class="ipsInput ipsInput--toggle" data-control="dimensionsUnlimited" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[unlimited]" id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' 
IPSCONTENT;

if ( $unlimited == array( $width, $height ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited_label' class="ipsInput ipsInput--toggle">
				<label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited_label' class='ipsField_unlimited'>
IPSCONTENT;

$val = "{$unlimitedLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

		return $return;
}}