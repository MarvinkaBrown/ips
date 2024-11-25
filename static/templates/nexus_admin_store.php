<?php
namespace IPS\Theme;
class class_nexus_admin_store extends \IPS\Theme\Template
{	function adPackageExpire( $field ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'after', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]}" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"> <select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]"><option value="i" 
IPSCONTENT;

if ( $field->value[1] == 'i' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'impressions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option><option value="c" 
IPSCONTENT;

if ( $field->value[1] == 'c' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clicks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option></select> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]" 
IPSCONTENT;

if ( !$field->Value[0] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role="unlimited"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'never', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function fraudRuleDesc( $conditions, $results, $warning ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-margin-top_1">
	
IPSCONTENT;

for ( $i = 0; $i < \count( $conditions ); $i++ ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conditions[ $i ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $i != ( \count( $conditions ) - 1 ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'f_blurb_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endfor;;
$return .= <<<IPSCONTENT

	<br>
	
IPSCONTENT;

for ( $i = 0; $i < \count( $results ); $i++ ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $results[ $i ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $i != ( \count( $results ) - 1 ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'f_blurb_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endfor;;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

if ( $warning ):
$return .= <<<IPSCONTENT

	<div class="i-color_warning i-margin-top_1"><i class="fa-solid fa-circle-exclamation"></i> 
IPSCONTENT;

$sprintf = array($warning->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'fraud_rule_conflict', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function productDeleteWarning( $package, $active, $expiredRenewable, $expredNonRenewable, $canceledCanBeReactivated, $canceledCannotBeReactivated, $upgradeTo ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p class="ipsMessage ipsMessage--error i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
<div class="ipsBox" data-ips-template="productDeleteWarning">
	<h2 class="ipsBox__header">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</h2>
	<div class="i-padding_3">
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_review_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--delete-review i-margin-top_1">
				<li class="ipsData__item">
					
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT

						<div class="ipsData__icon i-color_warning i-font-size_2"><i class="fa-solid fa-xmark"></i></div>
						<div class="i-color_warning">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
						<div class="i-color_positive">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $expiredRenewable or $expredNonRenewable or $package->renew_options ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$pluralize = array( $active ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_count_active', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$pluralize = array( $active ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_count_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</li>
				
IPSCONTENT;

if ( $expiredRenewable or $expredNonRenewable ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						
IPSCONTENT;

if ( $expiredRenewable ):
$return .= <<<IPSCONTENT

							<div class="ipsData__icon i-color_warning i-font-size_2"><i class="fa-solid fa-xmark"></i></div>
							<div class="i-color_warning">
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
							<div class="i-color_positive">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( $expiredRenewable, $expredNonRenewable ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_count_expired_split', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $canceledCanBeReactivated or $canceledCannotBeReactivated ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						
IPSCONTENT;

if ( $canceledCanBeReactivated ):
$return .= <<<IPSCONTENT

							<div class="ipsData__icon i-color_warning i-font-size_2"><i class="fa-solid fa-xmark"></i></div>
							<div class="i-color_warning">
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
							<div class="i-color_positive">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( $canceledCanBeReactivated, $canceledCannotBeReactivated ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_count_canceled_split', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
		<div class="i-margin-top_1">
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=massManagePurchases&id={$package->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mass_change_all_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=viewPurchases&id={$package->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</div>
		<p class="i-margin-top_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_review_complete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>
</div>
<div class="ipsBox i-margin-top_1">
	<h2 class="ipsBox__header">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</h2>
	<div class="i-padding_3">
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_hide_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--hide-product i-margin-top_1">
				<li class="ipsData__item">
					
IPSCONTENT;

if ( $package->store ):
$return .= <<<IPSCONTENT

						<div class="ipsData__icon i-color_warning i-font-size_2"><i class="fa-solid fa-xmark"></i></div>
						<div class="i-color_warning">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
						<div class="i-color_positive">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_hide_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
				</li>
				<li class="ipsData__item">
					
IPSCONTENT;

if ( $package->reg ):
$return .= <<<IPSCONTENT

						<div class="ipsData__icon i-color_warning i-font-size_2"><i class="fa-solid fa-xmark"></i></div>
						<div class="i-color_warning">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
						<div class="i-color_positive">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_hide_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
				</li>
				<li class="ipsData__item">
					
IPSCONTENT;

if ( $upgradeTo ):
$return .= <<<IPSCONTENT

						<div class="ipsData__icon i-color_warning i-font-size_2"><i class="fa-solid fa-xmark"></i></div>
						<div class="i-color_warning">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
						<div class="i-color_positive">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_hide_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
				</li>
			</ul>
		</i-data>
		<div class="i-margin-top_1">
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&subnode=1&do=form&id={$package->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_delete_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function productOptions( $input, $fields, $package ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsFieldRow" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-controller="nexus.admin.store.productoptions" data-url="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=productoptions&package={$package->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
	<div class="ipsFieldRow__label">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'p_stock_dynamic_fields', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		<span class="ipsFieldRow__required">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
	</div>
	<div class="ipsFieldRow__content">
		<ul class="ipsFieldList">
			
IPSCONTENT;

foreach ( $fields as $field ):
$return .= <<<IPSCONTENT

				<li>
					<input type="checkbox" class="ipsInput ipsInput--toggle" id="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="field" data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \in_array( $field->id, $input->value ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 >
					<div class='ipsFieldList__content'>
						<label for='elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
		<div class="ipsFieldRow__desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'p_stock_dynamic_fields_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		<div class="ipsJS_show ipsBox i-margin-top_1" data-role='table'></div>
	</div>
	<noscript>
		<p class="ipsMessage ipsMessage--error">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'p_stock_dynamic_fields_noscript', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</noscript>
</li>
IPSCONTENT;

		return $return;
}

	function productOptionsChanged( $products ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p class="i-font-size_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_fields_modified', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
<div class="ipsTree_wrapper">
	<div class="ipsTree_rows">
		<ol class="ipsTree ipsTree_node">
			
IPSCONTENT;

foreach ( $products as $product ):
$return .= <<<IPSCONTENT

				<li>
					<div class="ipsTree_row ipsTree_noRoot">
						<div class="ipsTree_rowData">
							<h4 class="ipsTree_title"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&subnode=1&do=form&id={$product->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $product->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
						</div>
					</div>
				</li>	
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function productOptionsTable( $fields, $combinations, $existingValues, $renews ) {
		$return = '';
		$return .= <<<IPSCONTENT

<table class="ipsTable">
	<thead>
		<tr>
			
IPSCONTENT;

foreach ( $fields as $field ):
$return .= <<<IPSCONTENT

				<th>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</th>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'p_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
			<th>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'base_price_adjustment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
				<span class="i-color_soft i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'base_price_adjustment_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</th>
			
IPSCONTENT;

if ( $renews ):
$return .= <<<IPSCONTENT

				<th>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'renew_price_adjustment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
					<span class="i-color_soft i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'renew_price_adjustment_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</th>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</tr>
	</thead>
	<tbody>
		
IPSCONTENT;

foreach ( $combinations as $k => $options ):
$return .= <<<IPSCONTENT

			<tr>
				
IPSCONTENT;

foreach ( $options as $option ):
$return .= <<<IPSCONTENT

					<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $option, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<td data-role="unlimitedCatch">
					<input type="number" name="custom_fields[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][stock]" class="ipsInput ipsField_short" 
IPSCONTENT;

if ( isset( $existingValues[ $k ] ) and $existingValues[ $k ]['opt_stock'] != -1 ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $existingValues[ $k ]['opt_stock'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><br>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
					<input type="checkbox" class="ipsInput ipsInput--toggle" name="custom_fields[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][unlimitedStock]" id="custom_fields_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimitedStock" data-control="unlimited" 
IPSCONTENT;

if ( isset( $existingValues[ $k ] ) and $existingValues[ $k ]['opt_stock'] == -1 ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<label for="custom_fields_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimitedStock" class='ipsField_unlimited'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unlimited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				</td>
				<td>
					
IPSCONTENT;

$values = isset( $existingValues[ $k ] ) ? json_decode( $existingValues[ $k ]['opt_base_price'], TRUE ) : array();
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

						<input type="number" name="custom_fields[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][bpa][
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" class="ipsInput ipsField_short" step="any" value="
IPSCONTENT;

if ( isset( $values[$currency] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $values[$currency], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
					
				</td>
				
IPSCONTENT;

if ( $renews ):
$return .= <<<IPSCONTENT

					<td>
						
IPSCONTENT;

$values = isset( $existingValues[ $k ] ) ? json_decode( $existingValues[ $k ]['opt_renew_price'], TRUE ) : array();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

							<input type="number" name="custom_fields[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][rpa][
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" class="ipsInput ipsField_short" step="any" value="
IPSCONTENT;

if ( isset( $values[$currency] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $values[$currency], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</td>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</tr>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</tbody>
</table>
IPSCONTENT;

		return $return;
}

	function productRowHtml( $package, $active, $expired, $canceled ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsTree_row_cells i-margin-top_1">
	<span class="ipsTree_row_cell">
		
IPSCONTENT;

if ( $package->store or $package->reg ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$stockLevel = $package->stockLevel();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $stockLevel === NULL ):
$return .= <<<IPSCONTENT

				<span class="i-color_positive"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_purchasable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

elseif ( $stockLevel ):
$return .= <<<IPSCONTENT

				<span class="i-color_positive"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$pluralize = array( $stockLevel ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_in_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="i-color_issue"><i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$pluralize = array( $stockLevel ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_in_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<span class="i-color_negative"><i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_not_purchasable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</span>
	
IPSCONTENT;

if ( $package->show ):
$return .= <<<IPSCONTENT

		<span class="ipsTree_row_cell">
			
IPSCONTENT;

if ( $package->allow_upgrading or $package->allow_downgrading ):
$return .= <<<IPSCONTENT

				<span class="i-color_positive"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

if ( !$package->allow_upgrading and $package->allow_downgrading ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_downgradeable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_upgradeable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="i-color_negative"><i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_not_upgradeable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<span class="ipsTree_row_cell i-color_soft">
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=viewPurchases&id={$package->_id}&filter=purchase_tab_active", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

if ( $expired or $package->renew_options ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$pluralize = array( $active ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_count_active', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$pluralize = array( $active ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_count_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</a>
	</span>
	
IPSCONTENT;

if ( $expired or $package->renew_options ):
$return .= <<<IPSCONTENT

		<span class="ipsTree_row_cell i-color_soft">
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=viewPurchases&id={$package->_id}&filter=purchase_tab_expired", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

$pluralize = array( $expired ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_count_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

			</a>
		</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $canceled ):
$return .= <<<IPSCONTENT

		<span class="ipsTree_row_cell i-color_soft">
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=viewPurchases&id={$package->_id}&filter=purchase_tab_canceled", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

$pluralize = array( $canceled ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_count_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

			</a>
		</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function productTaxWarning(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elProductTaxWarningContainer">
	<div class="ipsMessage ipsMessage--warning i-margin-top_1" id="elProductTaxWarning">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_ba_warning_tax', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function storeIndexProductsSetting( $key, $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$val = "{$key}"; $htmlsprintf = array('<input type=\'number\' name=\'' . $name . '[0]\' value=\'' . $value[0] . '\' class=\'ipsInput ipsField_tiny\'>', '<input type=\'number\' name=\'' . $name . '[1]\' value=\'' . ( isset( $value[1] ) ? $value[1] : '' ). '\' class=\'ipsInput ipsField_tiny\'>'); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}