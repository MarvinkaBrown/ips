<?php
namespace IPS\Theme;
class class_nexus_global_invoices extends \IPS\Theme\Template
{	function packageFields( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $item->details ) ):
$return .= <<<IPSCONTENT

    <br><span class="i-color_soft">
        
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

IPSCONTENT;

		return $return;
}

	function printInvoice( $invoice, $summary, $address ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsPrint">
	
IPSCONTENT;

$return .= \IPS\Settings::i()->nexus_invoice_header;
$return .= <<<IPSCONTENT

	<h1>
IPSCONTENT;

$sprintf = array($invoice->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
	<div class="i-flex i-flex-wrap_wrap i-gap_3 i-margin-bottom_3 i-margin-top_3">
		<div class="i-flex_11">
			
IPSCONTENT;

if ( $invoice->po ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_po_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->po, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
				<br>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $invoice->member->member_id ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->guest_data['member']['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<br>
			
IPSCONTENT;

if ( $address ):
$return .= <<<IPSCONTENT

				{$address->toString('<br>')}
				
IPSCONTENT;

if ( isset( $address->vat ) and $address->vat ):
$return .= <<<IPSCONTENT

					<br><br>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_checkout_vat_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_strtoupper( preg_replace( '/[^A-Z0-9]/', '', $address->vat ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div>
			<strong>
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
</strong><br>
			
IPSCONTENT;

$return .= \IPS\GeoLocation::buildFromJson( \IPS\Settings::i()->site_address )->toString('<br>');
$return .= <<<IPSCONTENT
<br>
			<br>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->date->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

if ( $invoice->notes ):
$return .= <<<IPSCONTENT

		<div class="i-margin-bottom_3">
			
IPSCONTENT;

$return .= nl2br( htmlspecialchars( $invoice->notes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<table>
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
					<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 {$item->detailsForDisplay( 'print' )}</td>
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
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
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
				</tr>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<tr class="i-font-size_2">
				<td colspan="3"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></td>
				<td><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['total'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></td>
			</tr>
		</tfoot>
	</table>
	
IPSCONTENT;

$return .= \IPS\Settings::i()->nexus_invoice_footer;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}}