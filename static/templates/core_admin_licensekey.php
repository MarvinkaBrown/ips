<?php
namespace IPS\Theme;
class class_core_admin_licensekey extends \IPS\Theme\Template
{	function overview( $licenseData ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox i-flex i-align-items_center i-gap_2 i-padding_3 i-margin-bottom_block">
	<div class="i-flex_11 i-basis_600">
		<h3 class='ipsTitle ipsTitle--h4'>
			
IPSCONTENT;

if ( $licenseData['cloud'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_name_cloud', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_name_license', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<br>
			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( substr_replace( $licenseData['key'], '**********', -10 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</h3>
		
		<p class='i-color_soft i-margin-top_2'>
			
IPSCONTENT;

$sprintf = array($licenseData['url']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_url', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $licenseData['test_url'] ):
$return .= <<<IPSCONTENT

				<br>
				
IPSCONTENT;

$sprintf = array($licenseData['test_url']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_test_url', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
	</div>
	
IPSCONTENT;

if ( $licenseData['expires'] ):
$return .= <<<IPSCONTENT

		<span class="i-font-size_-2 ipsBadge ipsBadge--
IPSCONTENT;

if ( strtotime( $licenseData['expires'] ) > time() ):
$return .= <<<IPSCONTENT
positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
negative
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_expires', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( strtotime( $licenseData['expires'] ) instanceof \IPS\DateTime ) ? strtotime( $licenseData['expires'] ) : \IPS\DateTime::ts( strtotime( $licenseData['expires'] ) );$return .= $val->html();
$return .= <<<IPSCONTENT

		</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

<div class="ipsBox">
	<h3 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'licensekey_benefits_head', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	<i-data>
		<ul class="ipsData ipsData--table ipsData--compact ipsData--license-key">
			
IPSCONTENT;

foreach ( array( 'forums', 'calendar', 'blog', 'gallery', 'downloads', 'cms', 'nexus', 'spam', 'copyright' ) as $k ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $k !== 'copyright' or ( isset( $licenseData['products'][ $k ] ) and $licenseData['products'][ $k ] ) ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<div class="ipsData__icon">
							
IPSCONTENT;

if ( isset( $licenseData['products'][ $k ] ) and $licenseData['products'][ $k ] ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $k == 'spam' and !$licenseData['cloud'] and strtotime( $licenseData['expires'] ) < time() ):
$return .= <<<IPSCONTENT

									<i class="i-font-size_2 fa-solid fa-triangle-exclamation"></i>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<i class="i-font-size_2 fa-solid fa-check"></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="i-font-size_2 fa-solid fa-xmark"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<div class="ipsData__main">
							<h4 class="ipsData__title">
IPSCONTENT;

$val = "license_benefit_$k"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
						</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<div class="ipsData__icon">
					
IPSCONTENT;

if ( $licenseData['cloud'] OR ( $licenseData['expires'] and strtotime( $licenseData['expires'] ) > time() ) ):
$return .= <<<IPSCONTENT

						<i class="i-font-size_2 fa-solid fa-check"></i>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i class="i-font-size_2 fa-solid fa-triangle-exclamation"></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsData__main">
					<h4 class="ipsData__title">
IPSCONTENT;

$sprintf = array($licenseData['support']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_benefit_support', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h4>
				</div>
			</li>
		</ul>
	</i-data>
</div>
IPSCONTENT;

		return $return;
}}