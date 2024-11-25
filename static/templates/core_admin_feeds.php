<?php
namespace IPS\Theme;
class class_core_admin_feeds extends \IPS\Theme\Template
{	function importPreview( $feed ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form method="post">
	<input type="hidden" name="continue" value="1">
	<i-data>
		<ol class="ipsData ipsData--table ipsData--import-preview">
			
IPSCONTENT;

foreach ( $feed as $item ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<div class='ipsData__main'>
						<h3 class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
						<div class='ipsData__meta'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['date'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	</i-data>
	<div class="ipsSubmitRow">
		<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function importRow( $node ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$url = $node->_importedIntoUrl;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $url ):
$return .= <<<IPSCONTENT

    <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" data-ipsToolTip title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsBadge ipsBadge--style6">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $node->_application->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <span class="ipsBadge ipsBadge--style6">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $node->_application->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}