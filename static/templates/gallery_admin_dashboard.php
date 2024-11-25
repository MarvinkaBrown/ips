<?php
namespace IPS\Theme;
class class_gallery_admin_dashboard extends \IPS\Theme\Template
{	function overview( $data ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
	<ul class="ipsData ipsData--table ipsData--compact ipsData--dashboard-overview">
		
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

if ( $k === 'total_disk_spaceg' ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $v );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( $k === 'largest_image' ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> <span class="i-color_soft">(
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $v->file_size );
$return .= <<<IPSCONTENT
)</span>
					
IPSCONTENT;

elseif ( $k === 'most_viewed_image' ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> <span class="i-color_soft">(
IPSCONTENT;

$pluralize = array( $v->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
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