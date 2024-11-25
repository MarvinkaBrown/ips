<?php
namespace IPS\Theme;
class class_blog_admin_dashboard extends \IPS\Theme\Template
{	function overview( $data ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
	<ul class="ipsData ipsData--table ipsData--dashboard-overview">
		
IPSCONTENT;

foreach ( $data as $k => $v ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<span class="i-basis_180">
					<strong>
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				</span>
				<span class="">
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</span>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</i-data>
IPSCONTENT;

		return $return;
}}