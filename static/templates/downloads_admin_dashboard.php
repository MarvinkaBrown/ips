<?php
namespace IPS\Theme;
class class_downloads_admin_dashboard extends \IPS\Theme\Template
{	function overview( $data ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
	<ul class="ipsData ipsData--table ipsData--compact ipsData--dashboard-overview">
		
IPSCONTENT;

foreach ( $data as $k => $v ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<span class="i-basis_220">
					<strong>
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				</span>
				<span class="">
					
IPSCONTENT;

if ( \in_array( $k, array( 'total_disk_spaced', 'total_bandwidth', 'current_month_bandwidth' ) ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $v );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( $k === 'largest_file' ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> <span class="i-color_soft">(
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $v->filesize() );
$return .= <<<IPSCONTENT
)</span>
					
IPSCONTENT;

elseif ( $k === 'most_viewed_file' ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> <span class="i-color_soft">(
IPSCONTENT;

$pluralize = array( $v->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span>
					
IPSCONTENT;

elseif ( $k === 'most_downloaded_file' ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> <span class="i-color_soft">(
IPSCONTENT;

$pluralize = array( $v->downloads ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span>
					
IPSCONTENT;

elseif ( $k === 'total_files' OR $k === 'total_views' OR $k === 'total_downloads' ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
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

				</span>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</i-data>
<div class="i-padding_2 i-color_soft">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_stats_disclaimer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}}