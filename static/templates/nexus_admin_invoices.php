<?php
namespace IPS\Theme;
class class_nexus_admin_invoices extends \IPS\Theme\Template
{	function generate( $summary, $itemTypes, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<ul class="ipsButtons ipsButtons--end ipsButtons--generate-invoice i-margin-bottom_2">
		<li>
			<a href="#el_addItem_menu" class="ipsButton ipsButton--secondary ipsButton--small" data-ipsMenu id='el_addItem'><i class="fa-solid fa-plus"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_add_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i></a>
			<div id='el_addItem_menu' class='ipsMenu ipsHide'>
				<ul>
					
IPSCONTENT;

foreach ( $itemTypes as $k => $class ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( method_exists( $class, 'form' ) ):
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('add', $k ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog  data-ipsDialog-title="
IPSCONTENT;

$val = "{$class::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i> 
IPSCONTENT;

$val = "{$class::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('addRenewal', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'renewal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-rotate-right"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'renewal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				</ul>
			</div>
		</li>
	</ul>
	<table class="ipsTable ipsTable_zebra">
		<thead>
			<tr>
				<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_unit_price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_quantity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_line_price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			
IPSCONTENT;

foreach ( $summary['items'] as $itemId => $item ):
$return .= <<<IPSCONTENT

				<tr>
					<td>
						<span title="
IPSCONTENT;

$val = "{$item::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip><i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i></span> &nbsp; 
						
IPSCONTENT;

if ( $itemUrl = $item->acpUrl() ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \count( $item->details ) ):
$return .= <<<IPSCONTENT

							<br>
							<span class="i-color_soft">
								
IPSCONTENT;

foreach ( $item->details as $k => $v ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$val = "nexus_pfield_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\nexus\Package\CustomField::load( $k )->displayValue( $v, TRUE );
$return .= <<<IPSCONTENT
<br>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</td>
					<td>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->price, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( isset( $item->renewalTerm ) and $item->renewalTerm ):
$return .= <<<IPSCONTENT

							<br>
							<span class="i-color_soft">
								
IPSCONTENT;

$sprintf = array($item->renewalTerm); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_renewal', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</td>
					<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
					<td>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( isset( $item->renewalTerm ) and $item->renewalTerm ):
$return .= <<<IPSCONTENT

							<br>
							<span class="i-color_soft">
								
IPSCONTENT;

$sprintf = array($item->renewalTerm->times( $item->quantity )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_renewal', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</td>
					<td>
						<ul class="ipsControlStrip" data-ipscontrolstrip>
							<li class="ipsControlStrip_button ">
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('remove', $itemId), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_remove_item_warn', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"> <!-- @todo [Future] It would be nice if it could recalculate prices automatically rather than show that warning -->
									<i class="ipsControlStrip_icon fa-solid fa-xmark-circle"></i>
									<span class="ipsControlStrip_item">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</a>
							</li>
						</ul>
					</td>
				</tr>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</tbody>
		<tfoot>
			<tr>
				<td colspan="3"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subtotal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></td>
				<td><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['subtotal'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></td>
				<td></td>
			</tr>
			
IPSCONTENT;

foreach ( $summary['tax'] as $taxId => $tax ):
$return .= <<<IPSCONTENT

				<tr>
					<td colspan="3">
IPSCONTENT;

$val = "nexus_tax_{$taxId}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)</td>
					<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tax['amount'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
					<td></td>
				</tr>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<tr>
				<td colspan="3"><strong class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></td>
				<td><strong class="i-font-size_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['total'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></td>
				<td></td>
			</tr>
		</tfoot>
	</table>
</div>
<div class="ipsSubmitRow">			
	<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post">
		<input type="hidden" name="continue" value="1">
		<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function invoiceTimeline(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<ul class="cNexusInvoiceExplain">
		
IPSCONTENT;

if ( \IPS\Settings::i()->cm_invoice_warning ):
$return .= <<<IPSCONTENT

			<li>
				<p class="time">
					
IPSCONTENT;

if ( \IPS\Settings::i()->cm_invoice_warning % 24 == 0 ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->cm_invoice_warning / 24 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_warning_time', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->cm_invoice_warning ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_warning_time_h', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
				<p class="event">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_credit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<li>
			<p class="time">
				
IPSCONTENT;

if ( \IPS\Settings::i()->cm_invoice_generate % 24 == 0 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->cm_invoice_generate / 24 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_generate_time', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->cm_invoice_generate ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_generate_time_h', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
			<p class="event">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_generate_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</li>
		
IPSCONTENT;

if ( \IPS\Settings::i()->cm_invoice_warning and \count( \IPS\nexus\Gateway::billingAgreementGateways() ) ):
$return .= <<<IPSCONTENT

			<li>
				<p class="time">
					
IPSCONTENT;

if ( \IPS\Settings::i()->cm_invoice_warning % 24 == 0 ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->cm_invoice_warning / 24 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_generate_time', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->cm_invoice_warning ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_generate_time_h', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
				<p class="event">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_ba_warn_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<li>
			<p class="time">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_grace_time', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<p class="event">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_grace_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</li>
		<li>
			<p class="time">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_expire_time', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<p class="event">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_expire_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</li>
		
IPSCONTENT;

if ( \IPS\Settings::i()->cm_invoice_expireafter ):
$return .= <<<IPSCONTENT

			<li>
				<p class="time">
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->cm_invoice_expireafter ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_invoice_exp_time', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</p>
				<p class="event">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_timeline_invoice_exp_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function link( $invoice, $number=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $number ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($invoice->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function packageSelector(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<noscript>
		<p class="ipsMessage ipsMessage--error">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_enable_js', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</noscript>
	<div data-controller="nexus.admin.store.productselector" data-url="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=invoices&do=productTree", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsJS_show">
		<ul class='ipsTree ipsTree_node'>
			
IPSCONTENT;

foreach ( \IPS\nexus\Package\Group::roots() as $group ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "invoices", \IPS\Request::i()->app )->packageSelectorGroup( $group );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function packageSelectorGroup( $group ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li>
	<div class='ipsTree_row 
IPSCONTENT;

if ( $group->hasChildren() ):
$return .= <<<IPSCONTENT
ipsTree_parent
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="group" data-groupId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<div class='ipsTree_rowData'>
			<h4 class="ipsTree_title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
		</div>
	</div>
	<ul class='ipsTree ipsTree_node'></ul>
</li>
IPSCONTENT;

		return $return;
}

	function packageSelectorProduct( $product ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li>
	<div class='ipsTree_row' data-role="product">
		<div class='ipsTree_rowData'>
			<h4 class="ipsTree_title"><input name="invoice_products[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $product->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" type="number" class="ipsInput ipsField_tiny" value="0"> &nbsp; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $product->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
		</div>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function status( $status ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="ipsBadge ipsBadge--style
IPSCONTENT;

if ( $status == \IPS\nexus\Invoice::STATUS_PAID ):
$return .= <<<IPSCONTENT
4
IPSCONTENT;

elseif ( $status == \IPS\nexus\Invoice::STATUS_PENDING ):
$return .= <<<IPSCONTENT
1
IPSCONTENT;

elseif ( $status == \IPS\nexus\Invoice::STATUS_CANCELED ):
$return .= <<<IPSCONTENT
5
IPSCONTENT;

elseif ( $status == \IPS\nexus\Invoice::STATUS_EXPIRED ):
$return .= <<<IPSCONTENT
6
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

$val = "istatus_{$status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</span>
IPSCONTENT;

		return $return;
}

	function transactionsTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<i-data>
		<ul class="ipsData ipsData--table ipsData--transactions-table">
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

		</ul>
	</i-data>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='i-color_soft i-padding_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_related_transactions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function transactionsTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li class='ipsData__item'>
		<div class='ipsData__main'>
			<h3 class='ipsTitle ipsTitle--h3'><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['t_method'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>&nbsp;&nbsp;{$row['t_status']}</h3><br>
			<span class='cNexusPrice'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['t_amount'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
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

	function unpaidConsequences( $invoice ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ( $consequences = $invoice->unpaidConsequences() and \count( $consequences ) ) ):
$return .= <<<IPSCONTENT

	<div class="i-margin-top_1 ipsMessage ipsMessage--general">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_unpaid_consequences', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		<ul class="cInvoiceUnpaidConsequences">
			
IPSCONTENT;

foreach ( $consequences as $k => $v ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $k !== 'unassociate' ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

if ( \is_array( $v ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $v['message'] ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v['message'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								<span class="cInvoiceUnpaidConsequences_warning">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v['warning'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$val = "invoice_unpaid_$k"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								<ul>
									
IPSCONTENT;

foreach ( $v as $k2 => $v2 ):
$return .= <<<IPSCONTENT

										<li>
											
IPSCONTENT;

if ( \is_array( $v2 ) ):
$return .= <<<IPSCONTENT

												{$v2['message']}
												<span class="cInvoiceUnpaidConsequences_warning">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v2['warning'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												{$v2}
												
IPSCONTENT;

if ( $k === 'purchases' and isset( $consequences['unassociate'] ) and isset( $consequences['unassociate'][ $k2 ] ) and $childPurchases = array_filter( $consequences['unassociate'][ $k2 ], function( $c ) use( $consequences ) { return !array_key_exists( $c, $consequences['purchases'] ); }, ARRAY_FILTER_USE_KEY ) ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_unpaid_unassociate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

													<ul>
														
IPSCONTENT;

foreach ( $childPurchases as $child ):
$return .= <<<IPSCONTENT

															<li>{$child}</li>
														
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

										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

		</ul>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function view( $invoice, $summary, $transactions ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsPhotoPanel ipsPhotoPanel_mini i-margin-block_3 ipsResponsive_showPhone'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->userPhoto( $invoice->member, 'mini' );
$return .= <<<IPSCONTENT

	<div>
		<h3 class='ipsTitle ipsTitle--h3'>
			<strong>
				
IPSCONTENT;

if ( $invoice->member->member_id ):
$return .= <<<IPSCONTENT

					<a class="i-link-color_inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</strong>
		</h3>
		
IPSCONTENT;

if ( $invoice->member->email ):
$return .= <<<IPSCONTENT

			<p class='i-font-weight_normal i-font-size_1 i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
				
	</div>
</div>

<div class='ipsSpanGrid'>
	<div class='ipsSpanGrid__4'>
		<div class='ipsBox i-padding_3 i-text-align_center i-margin-bottom_1 cInvoiceStatus'>
			
IPSCONTENT;

if ( $invoice->status == 'paid' ):
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-check-circle i-font-size_6'></i>
			
IPSCONTENT;

elseif ( $invoice->status == 'pend' ):
$return .= <<<IPSCONTENT

				<i class='fa-regular fa-clock i-font-size_6'></i>
			
IPSCONTENT;

elseif ( $invoice->status == 'expd' ):
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-circle-exclamation i-font-size_6'></i>
			
IPSCONTENT;

elseif ( $invoice->status == 'canc' ):
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-xmark-circle i-font-size_6'></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<br>
			<p class='ipsTitle ipsTitle--h3'><strong>
IPSCONTENT;

$val = "istatus_{$invoice->status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></p>
			<p class='i-font-size_1'>
IPSCONTENT;

$val = "istatus_{$invoice->status}_desc"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

			
IPSCONTENT;

if ( $invoice->status_extra and isset( $invoice->status_extra['type'] ) ):
$return .= <<<IPSCONTENT

				<p class='i-font-size_1 i-margin-top_1'>
					
IPSCONTENT;

if ( $invoice->status_extra['type'] === 'manual' ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $invoice->status_extra['setByID'] )->name, \IPS\DateTime::ts( $invoice->status_extra['date'] )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_status_extra', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( $invoice->status_extra['type'] === 'zero' ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_status_zero', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>

		<div class='ipsBox i-margin-bottom_1'>
			<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<div class='i-padding_3'>
				<p>
					
IPSCONTENT;

if ( $invoice->notes ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->notes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span class='i-color_soft'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_invoice_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>

				<p class='i-font-size_1 i-margin-top_1'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->acpUrl()->setQueryString( array( 'do' => 'notes' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--tiny'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</p>
			</div>
		</div>

		
IPSCONTENT;

if ( $transactions ):
$return .= <<<IPSCONTENT

			<div class='i-margin-bottom_1 ipsBox'>
				<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_transactions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				{$transactions}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	</div>
	<div class='ipsSpanGrid__8'>

		<div class='ipsPhotoPanel ipsPhotoPanel_mini i-margin-bottom_1 ipsResponsive_hidePhone'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->userPhoto( $invoice->member, 'mini' );
$return .= <<<IPSCONTENT

			<div>
				<h3 class='ipsTitle ipsTitle--h3'>
					<strong>
						
IPSCONTENT;

if ( $invoice->member->member_id ):
$return .= <<<IPSCONTENT

							<a class="i-link-color_inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</strong>
				</h3>
				
IPSCONTENT;

if ( $invoice->member->email ):
$return .= <<<IPSCONTENT

					<p class='i-font-weight_normal i-font-size_1 i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
				
			</div>
		</div>
		
		<div class='ipsBox'>
			<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<div class='i-padding_3'>
				<div class='ipsSpanGrid i-margin-bottom_1'>
					<div class='ipsSpanGrid__7'>
						
IPSCONTENT;

if ( $invoice->billaddress ):
$return .= <<<IPSCONTENT

							<h3 class="i-font-weight_600 i-color_hard i-font-size_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_billedto', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<p>
								{$invoice->billaddress->toString('<br>')}
							</p>
							
IPSCONTENT;

if ( isset( $invoice->billaddress->vat ) and $invoice->billaddress->vat ):
$return .= <<<IPSCONTENT

								<p class='i-margin-top_1'>
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_checkout_vat_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->vatNumber( $invoice->billaddress->vat );
$return .= <<<IPSCONTENT

								</p>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsSpanGrid__5'>
						<h3 class="i-font-weight_600 i-color_hard i-font-size_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class='i-margin-bottom_1'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->date, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</p>

						<h3 class="i-font-weight_600 i-color_hard i-font-size_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_paiddate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class='i-margin-bottom_1'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->paid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</p>

						<h3 class="i-font-weight_600 i-color_hard i-font-size_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_po_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class='i-margin-bottom_1'>
							
IPSCONTENT;

if ( $invoice->po ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->po, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							&nbsp;&nbsp;
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->acpUrl()->setQueryString( array( 'do' => 'poNumber' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog class='i-font-size_-1'>(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</a>
						</p>
					</div>
				</div>

				<div class='i-background_1'>
					<table class="ipsTable ipsTable_zebra">
						<thead>
							<tr>
								<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
								<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_unit_price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
								<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_quantity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
								<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_line_price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
							</tr>
						</thead>
						<tbody>
							
IPSCONTENT;

foreach ( $summary['items'] as $k => $item ):
$return .= <<<IPSCONTENT

								<tr>
									<td class="ipsTable_wrap">
										<span title="
IPSCONTENT;

$val = "{$item->_title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip><i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i></span> &nbsp; 
										
IPSCONTENT;

if ( $url = $item->acpUrl() ):
$return .= <<<IPSCONTENT

											<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( \count( $item->details ) ):
$return .= <<<IPSCONTENT

											<span class="i-color_soft">
												
IPSCONTENT;

foreach ( $item->details as $k => $v ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

if ( $displayValue = trim( \IPS\nexus\Package\CustomField::load( $k )->displayValue( $v, TRUE ) ) ):
$return .= <<<IPSCONTENT

														
IPSCONTENT;

$val = "nexus_pfield_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: {$displayValue}<br>
													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

											</span>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</td>
									<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->price, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
									<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
									<td>
										
IPSCONTENT;

if ( $item->payTo ):
$return .= <<<IPSCONTENT

											<a href="#" data-ipsDialog data-ipsDialog-content="#el_item
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_commission" data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $item->quantity > 1 ):
$return .= <<<IPSCONTENT
 x
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
												
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

											</a>
											<div id="el_item
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_commission" class="ipsHide">
												
IPSCONTENT;

$recipientAmounts = $item->recipientAmounts();
$return .= <<<IPSCONTENT

												<table class="ipsTable ipsTable_zebra">
													<thead>
														<tr>
															<th></th>
															<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_unit_price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
															<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_line_price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
														</tr>
													</thead>
													<tbody>
														<tr>
															<td></td>
															<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->price, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
															<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
														</tr>
														
IPSCONTENT;

if ( $item->commission and $item->fee ):
$return .= <<<IPSCONTENT

															<tr>
																<td>
IPSCONTENT;

$sprintf = array($item->commission); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_site_commission', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</td>
																<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recipientAmounts['site_commission_unit'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
																<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recipientAmounts['site_commission_line'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
															</tr>
															<tr>
																<td colspan="2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_site_fee', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</td>
																<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->fee, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
															</tr>
															<tr>
																<td colspan="2"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_site_total', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></td>
																<td><strong class="">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recipientAmounts['site_total'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></td>
															</tr>
															<tr>
																<td colspan="2"><strong>
IPSCONTENT;

$sprintf = array($item->payTo->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_amount_recipient', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong></td>
																<td><strong class="">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recipientAmounts['recipient_final'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></td>
															</tr>
														
IPSCONTENT;

elseif ( $item->commission ):
$return .= <<<IPSCONTENT

															<tr>
																<td>
IPSCONTENT;

$sprintf = array($item->commission); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_site_commission', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</td>
																<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recipientAmounts['site_commission_unit'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
																<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recipientAmounts['site_commission_line'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
															</tr>
															<tr>
																<td>
IPSCONTENT;

$sprintf = array($item->payTo->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_amount_recipient', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</td>
																<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recipientAmounts['recipient_unit'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
																<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recipientAmounts['recipient_line'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
															</tr>
														
IPSCONTENT;

elseif ( $item->fee ):
$return .= <<<IPSCONTENT

															<tr>
																<td colspan="2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_site_fee', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</td>
																<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->fee, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
															</tr>
															<tr>
																<td colspan="2">
IPSCONTENT;

$sprintf = array($item->payTo->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_amount_recipient', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</td>
																<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recipientAmounts['recipient_final'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
															</tr>
														
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													</tbody>
												</table>
											</div>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</td>
								</tr>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</tbody>
						<tfoot>
							<tr class='cInvoice_subtotal'>
								<td colspan="3"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subtotal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></td>
								<td><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['subtotal'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></td>
							</tr>
							
IPSCONTENT;

foreach ( $summary['tax'] as $taxId => $tax ):
$return .= <<<IPSCONTENT

								<tr>
									<td colspan="3">
										
IPSCONTENT;

$val = "nexus_tax_{$taxId}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $tax['type'] !== 'single' ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $tax['type'] === 'eu' and $invoice->billaddress->business and $invoice->billaddress->vat ):
$return .= <<<IPSCONTENT

												(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tax_rate_eu', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)
											
IPSCONTENT;

elseif ( $invoice->billaddress->business ):
$return .= <<<IPSCONTENT

												(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tax_rate_business', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tax_rate_consumer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											(
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</td>
									<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tax['amount'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
								</tr>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							<tr class='cInvoice_subtotal'>
								<td colspan="3"><strong class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></td>
								<td><strong class="i-font-size_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['total'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></td>
							</tr>
                            
IPSCONTENT;

$recipients = $invoice->payToRecipients();
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

$commission = $invoice->commission( $invoice->total->amount->subtract( \IPS\Math\Number::sum( $recipients ) ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $recipients or ( $commission AND \IPS\Settings::i()->ref_on ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$totalForSite = $summary['total']->amount->subtract( \IPS\Math\Number::sum( $recipients ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

foreach ( $recipients as $recipient => $amount ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$customer = \IPS\nexus\Customer::load( $recipient );
$return .= <<<IPSCONTENT

									<tr>
										<td colspan="3">
											<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
												<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customer->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customer->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsUserPhoto ipsUserPhoto--tiny' loading="lazy" alt=""></a>
												<div>
													<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customer->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-link-color_inherit"><strong>
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $recipient )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_amount_recipient', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong></a>
												</div>
											</div>
										</td>
										<td><strong>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $amount, $invoice->currency );
$return .= <<<IPSCONTENT
</strong></td>
									</tr>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


								
IPSCONTENT;

if ( isset( $commission ) and $commission['rule'] ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$totalForSite = $totalForSite->subtract( $commission['amount']->amount );
$return .= <<<IPSCONTENT

									<tr>
										<td colspan="3">
											<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
												<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $commission['referrer']->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $commission['referrer']->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsUserPhoto ipsUserPhoto--tiny' loading='lazy' alt=''></a>
												<div>
													<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $commission['referrer']->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><strong>
IPSCONTENT;

$sprintf = array($commission['referrer']->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_amount_commission', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong></a>
													<br>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $commission['rule']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

												</div>
											</div>
										</td>
										<td><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $commission['amount'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></td>
									</tr>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<tr class='cInvoice_subtotal'>
									<td colspan="3"><strong class="">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_site_total', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></td>
									<td><strong class="">
IPSCONTENT;

$return .= new \IPS\nexus\Money( $totalForSite, $invoice->currency );
$return .= <<<IPSCONTENT
</strong></td>
								</tr>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</tfoot>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}}