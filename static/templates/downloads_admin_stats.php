<?php
namespace IPS\Theme;
class class_downloads_admin_stats extends \IPS\Theme\Template
{	function downloadsTable( $select, $pagination, $members, $total ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox" data-ips-template="downloadsTable">
	<h1 class="ipsBox__header">
IPSCONTENT;

$pluralize = array( $total ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads_stats_downloader_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h1>
	
IPSCONTENT;

if ( trim( $pagination ) ):
$return .= <<<IPSCONTENT

		{$pagination}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<table class="ipsTable ipsTable_zebra">
		<thead>
			<tr>
				<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
			</tr>
		</thead>
		<tbody>
			
IPSCONTENT;

foreach ( $select as $row ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$member = \IPS\Member::constructFromData( $members[ $row['dmid'] ] );
$return .= <<<IPSCONTENT

				<tr>
					<td>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
					<td><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=stats&controller=member&do=downloads&id={$member->member_id}&tab=downloads&&type%5Bd74394%5D=Table", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['downloads'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></td>
				</tr>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</tbody>
	</table>
	{$pagination}
</div>
IPSCONTENT;

		return $return;
}

	function graphs( $graph ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$graph}
<div class="i-padding_2 i-background_3">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_graph_disclaimer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function information( $member, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$table}
<div class="i-padding_2 i-color_soft i-font-size_-1">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_stats_disclaimer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function link( $member, $type, $lang ) {
		$return = '';
		$return .= <<<IPSCONTENT


&nbsp;&nbsp;<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\downloads\\extensions\\core\\MemberACPProfileContentTab\\Downloads&id={$member->member_id}&chart={$type}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">(
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</a>
IPSCONTENT;

		return $return;
}

	function submittersTable( $select, $pagination, $members, $total ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox">
	<h1 class="ipsBox__header">
IPSCONTENT;

$pluralize = array( $total ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads_stats_uploader_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h1>
	
IPSCONTENT;

if ( trim( $pagination ) ):
$return .= <<<IPSCONTENT

		{$pagination}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $select ) ):
$return .= <<<IPSCONTENT

		<table class="ipsTable ipsTable_zebra">
			<thead>
				<tr>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				</tr>
			</thead>
			<tbody>
				
IPSCONTENT;

foreach ( $select as $row ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $members[ $row['file_submitter'] ] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$member = \IPS\Member::constructFromData( $members[ $row['file_submitter'] ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$member = new \IPS\Member;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<tr>
						<td>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member );
$return .= <<<IPSCONTENT
 {$member->link()}</td>
						<td>
							
IPSCONTENT;

if ( $member->member_id ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url()->setQueryString( array( 'do' => 'content', 'type' => 'downloads_file' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['files'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['files'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
		</table>
		{$pagination}
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function topDownloadsTable( $downloads, $pagination ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( trim( $pagination ) ):
$return .= <<<IPSCONTENT

{$pagination}
<br><br>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $downloads ) ):
$return .= <<<IPSCONTENT

<table class="ipsTable ipsTable_zebra">
	<thead>
	<tr>
		<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dfid', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
		<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
	</tr>
	</thead>
	<tbody>
		
IPSCONTENT;

foreach ( $downloads as $row ):
$return .= <<<IPSCONTENT

		<tr>
			<td>
				{$row['file']}
			</td>
			<td>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['downloads'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</td>
		</tr>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</tbody>
</table>
<br>

{$pagination}

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

<p class="i-padding_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}