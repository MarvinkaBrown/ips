<?php
namespace IPS\Theme;
class class_nexus_admin_customers extends \IPS\Theme\Template
{	function accountInformation( $customer, $tabs, $activeTabKey, $activeTabContents, $activeSubscription ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox'>
	<div id='acpPageHeader' class='cNexusSupportHeader i-text-align_center'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $customer, 'medium' );
$return .= <<<IPSCONTENT

		<div>
			<h1 class='i-font-size_5 i-color_hard i-font-weight_bold i-margin-top_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customer->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
			<p class="i-font-size_2 i-color_soft i-font-weight_500">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customer->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
			<p class='i-font-weight_500 i-margin-top_2 i-color_soft'>
IPSCONTENT;

$htmlsprintf = array($customer->joined->html()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'customer_since', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

if ( $activeSubscription ):
$return .= <<<IPSCONTENT

				<p class="i-text-align_center i-color_positive i-font-size_1 i-margin-top_1">
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) and $activeSubscription->purchase ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeSubscription->purchase->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-link-color_inherit">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<i class="fa-solid fa-certificate"></i> &nbsp; 
IPSCONTENT;

$sprintf = array($activeSubscription->package->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_subscriber', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) and $activeSubscription->purchase ):
$return .= <<<IPSCONTENT

					</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_add' ) ):
$return .= <<<IPSCONTENT

			<div class="i-margin-top_2"><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=invoices&do=generate&member={$customer->member_id}&_new=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--wide ipsButton--large'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_invoice', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>

<div class='ipsBox'>
	<i-tabs class='ipsTabs ipsTabs--stretch ipsTabs--customerTabs' id='ipsTabs_customer' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_customer_content'>
		<div role='tablist'>
			
IPSCONTENT;

foreach ( $tabs as $k => $tab ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customer->acpUrl()->setQueryString( array( 'do' => 'view', 'blockKey' => 'nexus_AccountInformation', 'block[nexus_AccountInformation]' => $k ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' id='ipsTabs_customer_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' role="tab" aria-controls="ipsTabs_customer_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( $activeTabKey == $k ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsTooltip title='
IPSCONTENT;

$val = "customer_tab_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
					<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> <span class='ipsResponsive_showPhone'>&nbsp;&nbsp;
IPSCONTENT;

$val = "customer_tab_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

if ( $tab['count'] ):
$return .= <<<IPSCONTENT
<span class='ipsNotification'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
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
	<div id='ipsTabs_customer_content' class='ipsTabs__panels acpFormTabContent'>
		<div id='ipsTabs_customer_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTabKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class='ipsTabs__panel' role="tabpanel" aria-labelledby='ipsTabs_customer_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTabKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			{$activeTabContents}
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function accountInformationOverview( $customer, $sparkline, $primaryBillingAddress, $addressCount ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
    
IPSCONTENT;

if ( $sparkline  ):
$return .= <<<IPSCONTENT

		<div id='elCustomerIncome'>{$sparkline}</div>
		<p class='i-color_soft i-font-size_-1 i-margin-top_1'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revenue_past_12_months', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view_credit' ) ):
$return .= <<<IPSCONTENT

		<div class="i-margin-top_4">
			
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

				<div class='i-background_3
IPSCONTENT;

if ( isset( $customer->cm_credits[ $currency ] ) && $customer->cm_credits[ $currency ]->amount->isGreaterThanZero() ):
$return .= <<<IPSCONTENT
_positive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-padding_2 i-text-align_center i-font-size_2 cCustomerCredit i-border-radius_box'>
					<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'client_credit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					
IPSCONTENT;

if ( isset( $customer->cm_credits[ $currency ] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customer->cm_credits[ $currency ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= new \IPS\nexus\Money( 0, $currency );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_edit_credit' ) ):
$return .= <<<IPSCONTENT

						<p><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=customers&controller=view&id={$customer->member_id}&do=credits", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_credit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-size='narrow' class='i-font-size_-1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></p>
					
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

	
	<div class="i-margin-top_4">
		<h2 class='ipsTitle ipsTitle--h4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'primary_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		
IPSCONTENT;

if ( $primaryBillingAddress ):
$return .= <<<IPSCONTENT

			<p class='i-font-size_1 i-margin-top_1'>
				{$primaryBillingAddress->toString('<br>')}
				
IPSCONTENT;

if ( isset( $primaryBillingAddress->vat ) and $primaryBillingAddress->vat ):
$return .= <<<IPSCONTENT

					<br>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_checkout_vat_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "nexus" )->vatNumber( $primaryBillingAddress->vat );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='i-font-size_1 i-margin-top_1 i-color_soft'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_primary_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<p>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=customers&controller=view&id={$customer->member_id}&do=addresses", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'customer_addresses', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='i-font-size_-1'>
IPSCONTENT;

$sprintf = array($addressCount); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_addresses_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
		</p>
	</div>
	
	
IPSCONTENT;

if ( \count( \IPS\nexus\Customer\CustomField::roots() ) or \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_edit_details' ) ):
$return .= <<<IPSCONTENT

		<div class="i-margin-top_4">
			<h2 class='ipsTitle ipsTitle--h4 i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'customer_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_edit_details' ) ):
$return .= <<<IPSCONTENT

				<p class='i-margin-bottom_1'>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=customers&controller=view&id={$customer->member_id}&do=edit", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='i-font-size_-1' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_customer_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
					
			
IPSCONTENT;

foreach ( \IPS\nexus\Customer\CustomField::roots() as $field ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$column = $field->column;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $displayValue = $field->displayValue( $customer->$column, TRUE ) ):
$return .= <<<IPSCONTENT

					<div class="i-margin-bottom_1">
						<h2 class='ipsMinorTitle'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
						<p class='i-font-size_1'>
							{$displayValue}
						</p>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_void' ) ):
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=customers&controller=view&do=void&id={$customer->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-margin-top_4 ipsButton ipsButton--negative ipsButton--wide" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'void_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'void_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function accountInformationTablePreview( $customer, $table, $title, $type ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	
IPSCONTENT;

$count = \count( $table->getRows(NULL) );
$return .= <<<IPSCONTENT

	<h3 class='ipsTitle ipsTitle--h3 ipsTruncate ipsTruncate_line 
IPSCONTENT;

if ( !$count ):
$return .= <<<IPSCONTENT
i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $count ):
$return .= <<<IPSCONTENT
<strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $count ):
$return .= <<<IPSCONTENT
</strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h3>
	{$table}
	<p class='i-margin-top_1'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\nexus\\extensions\\core\\MemberACPProfileBlocks\\AccountInformation&id={$customer->member_id}&type={$type}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$val = "customer_tab_{$type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit ipsButton--tiny ipsButton--wide'>
			
IPSCONTENT;

$val = "customer_manage_{$type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i>
		</a>
	</p>
</div>
IPSCONTENT;

		return $return;
}

	function addressItem( $address ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div>
	{$address['address']}

	
IPSCONTENT;

if ( $address['_buttons'] ):
$return .= <<<IPSCONTENT

		<br>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( $address['_buttons'], 'ipsControlStrip_showText' );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function addressTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( isset( $headers['_buttons'] ) ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->buttons( $table->rootButtons, '' );
endif;
$return .= <<<IPSCONTENT


<div class='cCustomerAddresses'>
	
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_addresses', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function addressTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$billing = null;
$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $row['primary_billing'] == true ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$billing = $row;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $billing ):
$return .= <<<IPSCONTENT

	<div class='ipsSpanGrid'>
		
IPSCONTENT;

if ( $billing ):
$return .= <<<IPSCONTENT

			<div class='ipsSpanGrid__6 i-background_2 i-padding_3'>
				<h2 class='ipsTitle ipsTitle--h3 i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_default_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "customers", "nexus" )->addressItem( $billing );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<hr class='ipsHr'>


IPSCONTENT;

if ( ( $billing && \count( $rows ) > 1 ) || ( !$billing && \count( $rows ) ) ):
$return .= <<<IPSCONTENT

	<ul class='ipsGrid i-basis_340'>
		
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !$row['primary_billing'] ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "customers", "nexus" )->addressItem( $row );
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function altContactsOverview( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<ul class='cCustomerOther_list i-grid i-gap_3'>
		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_alternate_contacts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function altContactsOverviewRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li>
		<p class='ipsTruncate ipsTruncate_line'>
			{$row['alt_id']}
		</p>
		<span class='i-font-size_1 i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['email'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function billingAgreementsOverview( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<ul class='cCustomerOther_list i-grid i-gap_3'>
		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_billing_agreements', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function billingAgreementsOverviewRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li>
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=billingagreements&id={$row['id']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-link-color_inherit">
			<span class='ipsTruncate ipsTruncate_line'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['gw_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</span>
			<span class='i-font-size_1 i-color_soft ipsTruncate ipsTruncate_line'>
IPSCONTENT;

if ( $row['next_cycle'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ba_next_cycle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['next_cycle'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		</a>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function cardsOverview( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<ul class='cCustomerOther_list i-grid i-gap_3'>
		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_credit_cards', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function cardsOverviewRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li>
		<p class='ipsTruncate ipsTruncate_line'>
			
IPSCONTENT;

if ( $row['card_type'] == 'paypal' or $row['card_type'] == 'venmo' ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['card_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $row['card_type'] == 'american_express' OR $row['card_type'] == 'diners_club' ):
$return .= <<<IPSCONTENT
XXXX-XXXXXX-X
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['card_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
XXXX-XXXX-XXXX-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['card_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
		<span class='i-font-size_1 i-color_soft ipsTruncate ipsTruncate_line'>
IPSCONTENT;

if ( $row['card_type'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "card_type_{$row['card_type']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_type_generic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( isset( $row['card_expires'] ) ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_expires_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['card_expire'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function cardsTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( isset( $headers['_buttons'] ) ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->buttons( $table->rootButtons, '' );
endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<ul class='ipsList ipsList--inline cNexusCards i-margin-top_1 i-text-align_center'>
		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_credit_cards', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function cardsTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li>
		
IPSCONTENT;

if ( $row['card_type'] == 'paypal' or $row['card_type'] === 'venmo' ):
$return .= <<<IPSCONTENT

			<div class='cNexusCards_name'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['card_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<span class='cNexusCards_number'>
				
IPSCONTENT;

if ( $row['card_type'] == 'american_express' OR $row['card_type'] == 'diners_club' ):
$return .= <<<IPSCONTENT

					XXXX XXXXXX X
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['card_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					XXXX XXXX XXXX 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['card_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $row['card_type'] ):
$return .= <<<IPSCONTENT

			<span class='cNexusCards_type cPayment cPayment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['card_type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "card_type_{$row['card_type']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $row['card_expire'] ):
$return .= <<<IPSCONTENT

			<span class='cNexusCards_expTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cards_exp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			<span class='cNexusCards_exp'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['card_expire'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=customers&controller=view&do=deleteCard&id={$row['card_member']}&card_id={$row['id']}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='cNexusCards_delete' data-confirm data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_this_card', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>&times;</a>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function customerPopup( $content ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<div class='i-padding_3'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	{$content}


IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function downloadsLink( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function invoices( $customer, $invoices, $invoiceCount ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox' data-ips-template="invoices">
	<h2 class='ipsBox__header'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'menu__nexus_payments_invoices', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoiceCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
		
IPSCONTENT;

if ( $invoiceCount > $invoices->limit ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=invoices&member={$customer->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary">
IPSCONTENT;

$pluralize = array( $invoiceCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_all_invoices', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h2>
	<div class='i-padding_2'>
		{$invoices}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function invoicesTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<i-data>
		<ul class='ipsData ipsData--table ipsData--invoices-table'>
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

		</ul>
	</i-data>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_invoices', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function invoicesTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li class='ipsData__item'>
		<div class='i-basis_120 i-text-align_center'>
			<span class='cNexusCustomerInvoice'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['i_total'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			{$row['i_status']}
		</div>
		<div class='ipsData__main'>
			<p class='i-link-color_inherit'>
				
IPSCONTENT;

if ( $row['_buttons']['view'] ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['_buttons']['view']['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				{$row['i_title']} <span class='i-color_soft i-font-size_1'>#
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['i_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

if ( $row['_buttons']['view'] ):
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
			<p class='i-font-size_1'>
				<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_created', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $row['i_date'] instanceof \IPS\DateTime ) ? $row['i_date'] : \IPS\DateTime::ts( $row['i_date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
			</p>
		</div>
		<div class='i-basis_120 i-text-align_end'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( $row['_buttons'] );
$return .= <<<IPSCONTENT

		</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function logType( $type ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $type === 'invoice' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-file-lines"></i>

IPSCONTENT;

elseif ( $type === 'purchase' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-box-archive"></i>

IPSCONTENT;

elseif ( $type === 'transaction' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-money-bill"></i>

IPSCONTENT;

elseif ( $type === 'comission' or $type === 'payout' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-folder-open"></i>

IPSCONTENT;

elseif ( $type === 'giftvoucher' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-gift"></i>

IPSCONTENT;

elseif ( $type === 'info' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-user"></i>

IPSCONTENT;

elseif ( $type === 'address' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-house"></i>

IPSCONTENT;

elseif ( $type === 'card' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-credit-card"></i>

IPSCONTENT;

elseif ( $type === 'alternative' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-users"></i>

IPSCONTENT;

elseif ( $type === 'lkey' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-key"></i>

IPSCONTENT;

elseif ( $type === 'download' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-download"></i>

IPSCONTENT;

elseif ( $type === 'billingagreement' ):
$return .= <<<IPSCONTENT

	<i class="fa-brands fa-paypal"></i>

IPSCONTENT;

elseif ( $type === 'note' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-file-lines"></i>

IPSCONTENT;

elseif ( $type === 'custom' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-star"></i>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function notes( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-flex">
	<div class="i-flex_11">
		
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( isset( $headers['_buttons'] ) ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->buttons( $table->rootButtons, '' );
endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

foreach ( $rows as $note ):
$return .= <<<IPSCONTENT

	<article class="ipsEntry ipsEntry--simple ipsEntry--customer-note">
		<header class="ipsEntry__header">
			<div class="ipsEntry__header-align">
				<div class="ipsPhotoPanel">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $note['note_author'] ), 'mini' );
$return .= <<<IPSCONTENT

					<div class="ipsPhotoPanel__text">
						<div class="ipsPhotoPanel__primary">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $note['note_author'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
						<div class="ipsPhotoPanel__secondary">
IPSCONTENT;

$val = ( $note['note_date'] instanceof \IPS\DateTime ) ? $note['note_date'] : \IPS\DateTime::ts( $note['note_date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
					</div>
				</div>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( $note['_buttons'], 'ipsControlStrip_showText' );
$return .= <<<IPSCONTENT

			</div>
		</header>
		<div class="ipsEntry__post ipsRichText">
			{$note['note_text']}
		</div>
	</article>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

	<div class="i-padding_2">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function notesBlock( $customer, $noteCount, $notes ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $noteCount ):
$return .= <<<IPSCONTENT

	<div class='ipsBox cNotesBox' data-ips-template="notesBlock">
		<h2 class='ipsBox__header'>
			<span>
IPSCONTENT;

$pluralize = array( $noteCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_customer_notes_uc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $noteCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=lazyBlock&block=IPS\\nexus\\extensions\\core\\MemberACPProfileBlocks\\Notes&id={$customer->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'customer_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='i-margin-start_auto'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'manage_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</a>
		</h2>
		<div class='i-padding_3'>
			{$notes}
		</div>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='i-padding_3 i-color_soft cCustomerNotes_none' data-ips-template="notesBlock">
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=lazyBlock&block=IPS\\nexus\\extensions\\core\\MemberACPProfileBlocks\\Notes&id={$customer->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'customer_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='i-float_end i-font-size_1'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'manage_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</a>
		
IPSCONTENT;

$pluralize = array( $noteCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_customer_notes_uc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $noteCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function notesOverview( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<ul class='cCustomerOther_list i-grid i-gap_3'>
		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_customer_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function notesOverviewRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li>
		<p class='ipsTruncate ipsTruncate_line'>
		    
IPSCONTENT;

$return .= \strip_tags( \IPS\Text\Parser::removeElements( $row['note_text'], array( 'blockquote' ) ), '<br>' );
$return .= <<<IPSCONTENT

		</p>
		<span class='i-font-size_1 i-color_soft ipsTruncate ipsTruncate_line'>
IPSCONTENT;

$val = ( $row['note_date'] instanceof \IPS\DateTime ) ? $row['note_date'] : \IPS\DateTime::ts( $row['note_date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $row['note_author'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function numberOfPurchasesField( $field ) {
		$return = '';
		$return .= <<<IPSCONTENT

<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]">
	<option value="n" data-control="toggle" data-toggles="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_nop" 
IPSCONTENT;

if ( $field->value[0] === 'n' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'number_of_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="v" data-control="toggle" data-toggles="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_tas" 
IPSCONTENT;

if ( $field->value[0] === 'v' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_amount_spent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'is', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]">
	<option value="g" 
IPSCONTENT;

if ( $field->value[1] === 'g' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'greater_than', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="e" 
IPSCONTENT;

if ( $field->value[1] === 'e' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'exactly', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="l" 
IPSCONTENT;

if ( $field->value[1] === 'l' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'less_than', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
&nbsp;
<input id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_nop" type="number" class="ipsInput ipsField_tiny" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->value[2], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_tas">
	<br>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "nexus", 'global' )->money( $field->name . '[3]', json_decode( $field->value[2], TRUE ), array( 'unlimitedLang' => NULL ) );
$return .= <<<IPSCONTENT

</div>
<br> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
<input type="checkbox" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[4]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_nr" data-control="unlimited" 
IPSCONTENT;

if ( !$field->value[1] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
<label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_nr" class='ipsField_unlimited'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_restriction', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
IPSCONTENT;

		return $return;
}

	function parentAccounts( $parents ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-margin-bottom_1'>
	
IPSCONTENT;

foreach ( $parents as $parent ):
$return .= <<<IPSCONTENT

		<div class='ipsPhotoPanel ipsPhotoPanel_mini i-padding_2' data-role='result'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $parent, 'mini' );
$return .= <<<IPSCONTENT

			<div class="ipsPhotoPanel__text">
				<h2 class='ipsPhotoPanel__primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alt_contact_for', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $parent->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $parent->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
				<p class='ipsPhotoPanel__secondary'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $parent->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
			</div>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function purchases( $customer, $tabs, $activeTabKey, $activeTabContents ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox' data-ips-template="purchases">
	<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<i-tabs class='ipsTabs ipsTabs--stretch' id='ipsTabs_purchases' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_purchases_content'>
		<div role='tablist'>
			
IPSCONTENT;

foreach ( $tabs as $k => $tab ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customer->acpUrl()->setQueryString( array( 'do' => 'view', 'blockKey' => 'nexus_Purchases', 'block[nexus_Purchases]' => $k ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" id='ipsTabs_purchases_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-controls="ipsTabs_purchases_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( $activeTabKey == $k ):
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

$val = "purchase_tab_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
				</a>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

	</i-tabs>
	<div id='ipsTabs_purchases_content' class='ipsTabs__panels acpFormTabContent'>
		<div id='ipsTabs_purchases_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTabKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class='ipsTabs__panel' role="tabpanel" aria-labelledby='ipsTabs_purchases_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTabKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			{$activeTabContents}
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function purchaseValueField( $field ) {
		$return = '';
		$return .= <<<IPSCONTENT

<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]">
	<option value="g" 
IPSCONTENT;

if ( $field->value[0] === 'g' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'greater_than', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="e" 
IPSCONTENT;

if ( $field->value[0] === 'e' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'exactly', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="l" 
IPSCONTENT;

if ( $field->value[0] === 'l' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'less_than', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
<br>

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "nexus", 'global' )->money( $field->name . '[1]', json_decode( $field->value[1], TRUE ), array( 'unlimitedLang' => NULL ) );
$return .= <<<IPSCONTENT

<br> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
<input type="checkbox" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_nr" data-control="unlimited" 
IPSCONTENT;

if ( !$field->value[0] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
<label for="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_nr" class='ipsField_unlimited'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_restriction', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
IPSCONTENT;

		return $return;
}

	function rowName( $row ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&tab=nexus_Main&id={$row['member_id']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $row['cm_first_name'] and $row['cm_last_name'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['cm_last_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['cm_first_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function rowPhoto( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&tab=nexus_Main&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsUserPhoto ipsUserPhoto--mini"><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></a>
IPSCONTENT;

		return $return;
}

	function standing( $standing ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elCustomerStanding' class="i-margin-block_2">
	
IPSCONTENT;

foreach ( $standing as $k => $data ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$positive = false;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( ( $data['thisval'] >= $data['avgval'] && $data['type'] != 'timetopay' ) || ( $data['thisval'] <= $data['avgval'] && $data['type'] != 'timetopay' ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$positive = true;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsSpanGrid__3">
			<div class="cCustomerStanding_value 
IPSCONTENT;

if ( $positive ):
$return .= <<<IPSCONTENT
cCustomerStanding_positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
cCustomerStanding_negative
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['value'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $positive ):
$return .= <<<IPSCONTENT

					<span class='cCustomerStanding_arrow ipsCursor_pointer' data-ipsHover data-ipsHover-width='250' data-ipsHover-content='#el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

if ( $data['type'] != 'timetopay' ):
$return .= <<<IPSCONTENT

							<i class='fa-solid fa-arrow-up'></i>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class='fa-solid fa-arrow-down'></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<span class='cCustomerStanding_arrow ipsCursor_pointer' data-ipsHover data-ipsHover-width='250' data-ipsHover-content='#el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

if ( $data['type'] != 'timetopay' ):
$return .= <<<IPSCONTENT

							<i class='fa-solid fa-arrow-down'></i>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class='fa-solid fa-arrow-up'></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsHide'>
					<div class='i-padding_3'>
						
IPSCONTENT;

if ( $positive ):
$return .= <<<IPSCONTENT

							<p class='ipsType_uppercase i-text-align_center i-font-size_1 i-color_positive'>
								<strong>
									
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( abs( \intval( $data['avgpct'] - $data['thispct'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
	
									
IPSCONTENT;

if ( $data['type'] != 'timetopay' ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'above_average', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'below_average', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</strong>
							</p>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<p class='ipsType_uppercase i-text-align_center i-font-size_1 i-color_negative'>
								<strong>
									
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( abs( \intval( $data['avgpct'] - $data['thispct'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
	
									
IPSCONTENT;

if ( $data['type'] != 'timetopay' ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'below_average', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'above_average', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</strong>
							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<hr class='ipsHr'>
	
						<div class='i-margin-top_1 cCustomerStanding_progressIndicator'>
							<p class='cCustomerStanding_values i-flex i-justify-content_space-between i-font-size_-1'>
								<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['lowest'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
								<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['highest'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
							</p>
							<div class='cCustomerStanding_progress'></div>
							<div class='cCustomerStanding_markers'>
								<span class='cCustomerStanding_markerAvg' style='left: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['avgpct'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%'>
									<i class='fa-solid fa-caret-up'></i>
									<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'avg_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</span>
								<span class='cCustomerStanding_markerThis' style='left: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['thispct'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%'><i class='fa-solid fa-caret-up'></i></span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<h3 class='ipsMinorTitle ipsTruncate ipsTruncate_line'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function voidFails( $fails ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'void_account_fails', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<ul>
	
IPSCONTENT;

foreach ( $fails as $id ):
$return .= <<<IPSCONTENT

		<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=transactions&do=view&id={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array($id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}}