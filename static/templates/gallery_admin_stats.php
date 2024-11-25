<?php
namespace IPS\Theme;
class class_gallery_admin_stats extends \IPS\Theme\Template
{	function bandwidthButton( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT


&nbsp;&nbsp;<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\gallery\\extensions\\core\\MemberACPProfileContentTab\\Gallery&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'bandwidth_use_gallery', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'bandwidth_use_gallery', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</a>
IPSCONTENT;

		return $return;
}

	function graphs( $graph ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$graph}
<div class="i-padding_2 i-background_3">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_graph_disclaimer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_stats_disclaimer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function uploadersTable( $select, $pagination, $members, $total ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsBox">
	<h1 class="ipsBox__header">
IPSCONTENT;

$pluralize = array( $total ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_stats_uploader_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h1>
	
IPSCONTENT;

if ( trim( $pagination ) ):
$return .= <<<IPSCONTENT

		{$pagination}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsBox i-margin-bottom_2">
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_images', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				</tr>
			</thead>
			<tbody>
				
IPSCONTENT;

foreach ( $select as $row ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $members[ $row['image_member_id'] ] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$member = \IPS\Member::constructFromData( $members[ $row['image_member_id'] ] );
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
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
						<td><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url()->setQueryString( array( 'do' => 'content', 'type' => 'gallery_image' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['images'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></td>
					</tr>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</tbody>
		</table>
	</div>
	{$pagination}
</div>
IPSCONTENT;

		return $return;
}}