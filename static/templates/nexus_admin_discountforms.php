<?php
namespace IPS\Theme;
class class_nexus_admin_discountforms extends \IPS\Theme\Template
{	function bulk( $field, $nodeSelect ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='cNexusStack cNexusStack_usergroup'>
	<ol>
		<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_if', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_purchasing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
			<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[buying]" class="ipsInput ipsField_short ipsField_stackItemNoMargin" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[buying]" 
IPSCONTENT;

if ( \is_array( $field->value ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->value['buying'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		</li>
		<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_of', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[this]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_this_this" value="1" 
IPSCONTENT;

if ( !\is_array( $field->value ) or !$field->value['package'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 > <label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_this_this">&nbsp;<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_this_product', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></label><br>
			<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[this]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_this_other" value="0" data-control='toggle' data-toggles='p_bulk_discounts_nodeSelect
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \is_array( $field->value ) and $field->value['package'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 > <label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_this_other">&nbsp;<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_different_product', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></label><br>
			<div id="p_bulk_discounts_nodeSelect
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				{$nodeSelect}
			</div>
		</li>
		<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_then', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_price_becomes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
			<ul>
				
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

					<li>
						<input type="number" class="ipsInput ipsField_short ipsField_stackItemNoMargin" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[price][
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" 
IPSCONTENT;

if ( \is_array( $field->value ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->value['price'][$currency], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-decimals="2" data-decpoint="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->locale['decimal_point'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-thousandsseparator="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->locale['thousands_sep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" step="0.01"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
			<br>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_price_becomes_once', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</li>
	</ol>
</div>

IPSCONTENT;

		return $return;
}

	function loyalty( $field, $nodeSelect ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='cNexusStack cNexusStack_usergroup'>
	<ol>
		<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_if', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_after_purchasing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
			<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[owns]" class="ipsInput ipsField_short ipsField_stackItemNoMargin" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[owns]" 
IPSCONTENT;

if ( \is_array( $field->value ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->value['owns'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_or_more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
			<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[active]" 
IPSCONTENT;

if ( \is_array( $field->value ) and isset( $field->value['active'] ) and $field->value['active'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_active_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
		</li>
		<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_of', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[this]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_this_this" value="1" 
IPSCONTENT;

if ( !\is_array( $field->value ) or !$field->value['package'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 > <label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_this_this">&nbsp;<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_this_product', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></label><br>
			<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[this]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_this_other" value="0" data-control='toggle' data-toggles='p_loyalty_discounts_nodeSelect
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \is_array( $field->value ) and $field->value['package'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 > <label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_this_other">&nbsp;<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_different_product', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></label><br>
			<div id="p_loyalty_discounts_nodeSelect
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				{$nodeSelect}
			</div>
		</li>
		<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_then', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_price_becomes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
			<ul>
				
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

					<li>
						<input type="number" class="ipsInput ipsField_short ipsField_stackItemNoMargin" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[price][
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" 
IPSCONTENT;

if ( \is_array( $field->value ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->value['price'][$currency], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 step="0.01"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
			<br>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_price_becomes_always', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</li>
	</ol>
</div>
IPSCONTENT;

		return $return;
}

	function usergroup( $field ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='cNexusStack cNexusStack_usergroup'>
	<ol>
		<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_if', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_customer_in_group', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
			<select name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[group]" class='ipsInput ipsInput--select ipsInput--fullWidth'><option value="0"></option>
IPSCONTENT;

foreach ( \IPS\Member\Group::groups() as $group ):
$return .= <<<IPSCONTENT
<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->g_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \is_array( $field->value ) and $field->value['group'] == $group->g_id ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
</select><br>
			<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[secondary]" 
IPSCONTENT;

if ( \is_array( $field->value ) and isset( $field->value['secondary'] ) and $field->value['secondary'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_check_secondary', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</li>
		<li data-step='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_then', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			<strong class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'discount_price_becomes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
			<ul>
				
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

					<li>
						<input type="number" class="ipsInput ipsField_short ipsField_stackItemNoMargin" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[price][
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" 
IPSCONTENT;

if ( \is_array( $field->value ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->value['price'][$currency], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 step="0.01"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</li>
	</ol>
</div>
IPSCONTENT;

		return $return;
}}