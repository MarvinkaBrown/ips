<?php
namespace IPS\Theme;
class class_forums_admin_stats extends \IPS\Theme\Template
{	function solvedPercentage( $value, $total, $solved, $previousValue=NULL, $previousTotal=NULL, $previousSolved=NULL, $nodes=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

	<div>
		<span class='cStat__number cStat__number--medium' data-number='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title="
IPSCONTENT;

$sprintf = array($solved, $total); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_solved_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%</span>
		
IPSCONTENT;

if ( isset( $previousValue ) ):
$return .= <<<IPSCONTENT

			<p class='cStat__change cStat__change--large 
IPSCONTENT;

if ( $previousValue > $value ):
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

elseif ( $previousValue < $value ):
$return .= <<<IPSCONTENT
i-color_positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsTooltip title="
IPSCONTENT;

$sprintf = array($previousSolved, $previousTotal, $previousValue); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_solved_tooltip_prev', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

if ( $previousValue > $value ):
$return .= <<<IPSCONTENT

					-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $previousValue - $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
				
IPSCONTENT;

elseif ( $previousValue < $value ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $previousValue ):
$return .= <<<IPSCONTENT
+
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $value - $previousValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
+ ∞
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					&mdash;
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( \count( $nodes ) ):
$return .= <<<IPSCONTENT

			<hr class='ipsHr'>
			<div class='i-flex'>
				<div class='cStatTile__split'>
					<p class='cStat__change cStat__change--small'>
						
IPSCONTENT;

$sprintf = array( \IPS\Member::loggedIn()->language()->formatList($nodes) );$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'overview_stats_curfilter', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
IPSCONTENT;

		return $return;
}

	function statsTypeWrapper( $form, $solvedCount, $recommendedCount, $startTime, $endTime ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox' data-ips-template="statsTypeWrapper">
	<h1 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_posts_by_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<div>
		{$form}
	</div>
</div>
<div class='i-margin-top_3' data-ips-template="statsTypeWrapper">
	<div class="ipsTableScroll">
		<table class="ipsTable ipsTable_zebra " data-role="table">
			<thead>
				<tr class="i-background_3">
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_posts_type_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_posts_type_count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				</tr>
			</thead>
			<tbody>
				<tr class="">
					<td class="">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_posts_type_solved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</td>
					<td><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=forums&module=stats&controller=posts&do=showSolvedPosts&startTime={$startTime}&endTime={$endTime}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_posts_type_solved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_posts_show_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $solvedCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></td>
				</tr>
				<tr>
					<td class=" ipsTable_wrap ipsTable_primary ">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_posts_type_recommended', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</td>
					<td><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=forums&module=stats&controller=posts&do=showRecommendedPosts&startTime={$startTime}&endTime={$endTime}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_posts_type_recommended', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_posts_show_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recommendedCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

IPSCONTENT;

		return $return;
}

	function timeToSolved( $value, $previousValue=NULL, $nodes=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

	<div>
		<span class='cStat__number cStat__number--medium' data-number='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::roundedDiffFromSeconds( (int) $value ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

if ( isset( $previousValue ) ):
$return .= <<<IPSCONTENT

			<p class='cStat__change cStat__change--large 
IPSCONTENT;

if ( $previousValue < $value ):
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

elseif ( $previousValue > $value ):
$return .= <<<IPSCONTENT
i-color_positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'previous_period', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::roundedDiffFromSeconds( (int) $previousValue ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

if ( $previousValue > $value ):
$return .= <<<IPSCONTENT

					-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( 100 - ( $value / $previousValue * 100 ), 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
				
IPSCONTENT;

elseif ( $previousValue < $value ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $previousValue ):
$return .= <<<IPSCONTENT
+
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( ( ( $value - $previousValue ) / $previousValue ) * 100, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
+ ∞
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					&mdash;
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( \count( $nodes ) ):
$return .= <<<IPSCONTENT

			<hr class='ipsHr'>
			<div class='i-flex'>
				<div class='cStatTile__split'>
					<p class='cStat__change cStat__change--small'>
						
IPSCONTENT;

$sprintf = array( \IPS\Member::loggedIn()->language()->formatList($nodes) );$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'overview_stats_curfilter', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
IPSCONTENT;

		return $return;
}}