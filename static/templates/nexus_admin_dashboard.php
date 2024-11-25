<?php
namespace IPS\Theme;
class class_nexus_admin_dashboard extends \IPS\Theme\Template
{	function pendingActions( $pendingTransactions, $pendingWithdrawals, $pendingAdvertisements ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsGrid i-basis_400'>
	<i-data>
		<ul class="ipsData ipsData--table ipsData--pending-transactions" id='elNexusActions'>
			
IPSCONTENT;

if ( $pendingTransactions !== NULL ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<span class='cNexusActionBadge 
IPSCONTENT;

if ( $pendingTransactions < 1 ):
$return .= <<<IPSCONTENT
cNexusActionBadge_off
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $pendingTransactions > 99 ):
$return .= <<<IPSCONTENT
99+
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $pendingTransactions );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
					<div class='ipsData__main'>
						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=transactions&filter=tstatus_hold", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_transactions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $pendingWithdrawals !== NULL ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<span class='cNexusActionBadge 
IPSCONTENT;

if ( $pendingWithdrawals < 1 ):
$return .= <<<IPSCONTENT
cNexusActionBadge_off
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $pendingWithdrawals > 99 ):
$return .= <<<IPSCONTENT
99+
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $pendingWithdrawals );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
					<div class='ipsData__main'>
						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=payouts&filter=postatus_pend", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_widthdrawals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
	<i-data>
		<ul class="ipsData ipsData--table ipsData--pending-advertisements" id='elNexusActions'>
			
IPSCONTENT;

if ( $pendingAdvertisements !== NULL ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<span class='cNexusActionBadge 
IPSCONTENT;

if ( $pendingAdvertisements < 1 ):
$return .= <<<IPSCONTENT
cNexusActionBadge_off
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $pendingAdvertisements > 99 ):
$return .= <<<IPSCONTENT
99+
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $pendingAdvertisements );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
					<div class='ipsData__main'>
						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=promotion&controller=advertisements", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_advertisements', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
</div>
IPSCONTENT;

		return $return;
}}