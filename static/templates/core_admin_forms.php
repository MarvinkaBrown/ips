<?php
namespace IPS\Theme;
class class_core_admin_forms extends \IPS\Theme\Template
{	function blurb( $lang, $parse=TRUE, $background=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText 
IPSCONTENT;

if ( $background ):
$return .= <<<IPSCONTENT
i-padding_3 i-background_2
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	
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
IPSCONTENT;

		return $return;
}

	function checkbox( $name, $value=FALSE, $disabled=FALSE, $togglesOn=array(), $togglesOff=array(), $label='', $hiddenName='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $hiddenName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="0" />
<input
	type='checkbox'
	role='checkbox'
	name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	value='1'
	id='check_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	class="ipsInput ipsInput--toggle"
	
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

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $togglesOn ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $togglesOff ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
<label for='check_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$label}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
IPSCONTENT;

		return $return;
}

	function dateinterval( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'every', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<br>
<ul class="ipsFieldList">
	<li><input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[y]" class="ipsInput ipsField_short" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->y, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="0" /> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'years', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	<li><input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[m]" class="ipsInput ipsField_short" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->m, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="0" /> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'months', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	<li><input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[d]" class="ipsInput ipsField_short" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->d, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="0" /> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'days', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	<li><input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[h]" class="ipsInput ipsField_short" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->h, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="0" /> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hours', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	<li><input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[i]" class="ipsInput ipsField_short" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="0" /> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'minutes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	<li><input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[s]" class="ipsInput ipsField_short" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value->s, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="0" /> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'seconds', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
</ul>
IPSCONTENT;

		return $return;
}

	function emptyRow( $contents, $id=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsFieldRow ipsFieldRow--matrix' 
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
	<h2 class='ipsFieldRow__section'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
</li>
IPSCONTENT;

		return $return;
}

	function radio( $name, $value, $required, $options, $disabled=FALSE, $toggles=array(), $descriptions=array(), $warnings=array(), $userSuppliedInput='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class="ipsFieldList ipsFieldList--radio" role="radiogroup">

IPSCONTENT;

foreach ( $options as $k => $v ):
$return .= <<<IPSCONTENT

	<li>
		<input type="radio" role="radio" id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle" value="
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

if ( $required === TRUE ):
$return .= <<<IPSCONTENT
required aria-required='true'
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

if ( isset( $toggles[ $k ] ) and !empty( $toggles[ $k ] ) ):
$return .= <<<IPSCONTENT
data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles[ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-controls="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles[ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>
		<div class='ipsFieldList__content'>
			<label for='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>{$v}</label>
			
IPSCONTENT;

if ( isset( $descriptions[ $k ] ) ):
$return .= <<<IPSCONTENT

				<div class='ipsFieldRow__desc'>
					{$descriptions[ $k ]}
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $warnings[ $k ] ) ):
$return .= <<<IPSCONTENT

				<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_warning">
					<p class='ipsMessage ipsMessage--warning'>{$warnings[ $k ]}</p>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !empty( $userSuppliedInput ) ):
$return .= <<<IPSCONTENT

				<input type='text' name='
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
'>
			
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

	function row( $langKey, $element, $desc, $warning, $required=FALSE, $error=NULL, $prefix=NULL, $suffix=NULL, $id=NULL, $object=NULL, $form=NULL, $rowClasses=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsFieldRow 
IPSCONTENT;

if ( $object instanceof \IPS\Helpers\Form\YesNo ):
$return .= <<<IPSCONTENT
ipsFieldRow_yesNo
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $object and isset( \IPS\Widget\Request::i()->searchResult ) and ( \IPS\Widget\Request::i()->searchResult === $object->name or \IPS\Widget\Request::i()->searchResult === $id ) ):
$return .= <<<IPSCONTENT
ipsFieldRow_searchResult
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
	<div class='ipsFieldRow__label 
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT
ipsFieldRow_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		{$langKey}
		
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

		
IPSCONTENT;

if ( \IPS\IN_DEV and $form and $object ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->searchKeywords( $form->action->setQueryString( array( 'do' => ( isset( $form->action->queryString['do'] ) and ( $form->action->queryString['do'] != 'form' or $form->action->queryString['do'] != 'edit' ) ) ? $form->action->queryString['do'] : NULL, 'id' => NULL, 'searchResult' => $id ) )->acpQueryString(), $object->name );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsFieldRow__content 
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT
ipsFieldRow_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( $object and $form and !( $object instanceof \IPS\Helpers\Form\Translatable ) and !( $object instanceof \IPS\Helpers\Form\Editor ) and !( $object instanceof \IPS\Helpers\Form\Upload ) and !( $object instanceof \IPS\Helpers\Form\Codemirror ) and $form->copyButton and ( !isset( $object->options['disableCopy'] ) or !$object->options['disableCopy'] ) ):
$return .= <<<IPSCONTENT

		<a href="#" data-baseLink="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->copyButton->setQueryString( array( 'key' => $object->name ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-controller="core.admin.core.nodeCopySetting" data-field="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $object->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsJS_show ipsButton ipsButton--tiny ipsButton--inherit ipsButton--icon i-opacity_4 i-hover-opacity_10 i-float_end cCopyNode' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip tabindex="999"><i class="fa-solid fa-gear"></i> <i class='fa-solid fa-caret-right'></i></a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		{$prefix}
		{$element}
		{$suffix}
		{$desc}
		{$warning}
		
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

			<div class='ipsFieldRow__warning'><i class='fa-solid fa-circle-exclamation'></i> 
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='ipsFieldRow__warning'></div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function select( $name, $value, $required, $options, $multiple=FALSE, $class='', $disabled=FALSE, $toggles=array(), $id=NULL, $unlimited=NULL, $unlimitedLang='all', $unlimitedToggles=array(), $toggleOn=TRUE, $userSuppliedInput='', $sort=FALSE, $parse=NULL ) {
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

if ( $parse === 'raw' ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
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
 
IPSCONTENT;

if ( \is_array($disabled) and \in_array( $_k, $disabled ) ):
$return .= <<<IPSCONTENT
disabled
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

if ( ( ( $value === 0 and $k === 0 ) or ( $value !== 0 and $value === $k ) or ( $value !== 0 and \is_numeric( $value ) and \is_numeric( $k ) and $value == $k ) ) or ( \is_array( $value ) and \in_array( $k, $value ) ) or ( !empty( $userSuppliedInput ) and !\in_array( $value, array_keys( $options ) ) and $k == $userSuppliedInput ) ):
$return .= <<<IPSCONTENT
selected
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
 
IPSCONTENT;

if ( \is_array($disabled) and \in_array( $k, $disabled ) ):
$return .= <<<IPSCONTENT
disabled
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
	<input type="checkbox" role='checkbox' class="ipsInput ipsInput--toggle" data-control="unlimited
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
" 
IPSCONTENT;

if ( \is_array( $toggleOn ) ):
$return .= <<<IPSCONTENT
data-togglesOff="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggleOn ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-controls="
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
_unlimited_label'>
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

	function socialProfiles( $key, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsField_stackItem_keyValue">
	<span data-ipsStack-keyvalue-name="key">{$key}</span> 
	<span data-ipsStack-keyvalue-name="key">{$value}</span>
</div>
IPSCONTENT;

		return $return;
}

	function tags( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input type='text' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $value ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsAutocomplete>
IPSCONTENT;

		return $return;
}

	function template( $id, $action, $tabs, $activeTab, $error, $errorTabs, $hiddenValues, $actionButtons, $uploadField, $sidebar, $tabClasses=array(), $formClass='', $attributes=array(), $tabArray=array(), $usingIcons=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox ipsPull">
	<form accept-charset='utf-8' data-formId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
 data-ipsForm class="ipsFormWrap ipsFormWrap--admin-template" 
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
>
		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_activeTab" value="
IPSCONTENT;

if ( $activeTab ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
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

		
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT

			<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

if ( \count( $tabs ) === 1 ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

				<div class='ipsColumns'>
					<div class='ipsColumns__primary'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--admin-template'>
							
IPSCONTENT;

$return .= array_pop( $tabs );
$return .= <<<IPSCONTENT

						</ul>
			
IPSCONTENT;

if ( !empty( $sidebar ) ):
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsColumns__secondary i-basis_280'>
						
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

			<i-tabs class='ipsTabs acpFormTabBar
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
_content' class='acpFormTabContent'>
				
IPSCONTENT;

foreach ( $tabs as $name => $contents ):
$return .= <<<IPSCONTENT

					<div class="ipsTabs__panel" role="tabpanel" id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !$activeTab != $name ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

if ( isset( $sidebar[ $name ] ) ):
$return .= <<<IPSCONTENT

							<div class='ipsColumns'>
								<div class='ipsColumns__primary'>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--admin-template 
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
										{$contents}
									</ul>
						
IPSCONTENT;

if ( isset( $sidebar[ $name ] ) ):
$return .= <<<IPSCONTENT

								</div>
								<div class='ipsColumns__secondary i-basis_280'>
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
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsSubmitRow">
			
IPSCONTENT;

$return .= implode( '', $actionButtons);
$return .= <<<IPSCONTENT

		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function webhookselector( $name, $value, $required, $options, $multiple=FALSE, $class='', $disabled=FALSE, $toggles=array(), $id=NULL, $unlimited=NULL, $unlimitedLang='all', $unlimitedToggles=array(), $toggleOn=TRUE, $descriptions=array(), $impliedUnlimited=FALSE ) {
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


<div data-control="granularCheckboxset" data-count="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $options ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">

<div data-role="checkboxsetGranular" class=" ">

    <div class="ipsSpanGrid">
        
IPSCONTENT;

foreach ( $options as $k => $v ):
$return .= <<<IPSCONTENT

       <div class="ipsSpanGrid__4">
					<span class=''>
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

if ( ( $unlimited !== NULL AND $unlimited === $value ) or ( \is_array( $value ) AND \in_array( $k, $value ) ) ):
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
">
                        <span class="ipsFieldRow_label">     <label for='elCheckbox_
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
_label' data-role="label">{$k}</label>
                        </span>
                         
IPSCONTENT;

if ( isset( $descriptions[ $k ] ) ):
$return .= <<<IPSCONTENT

                        <div class="ipsFieldList__content">
                            {$descriptions[ $k ]}
                        </div>
                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>

       </div>
        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

    </div>
</div>
</div>
IPSCONTENT;

		return $return;
}}