<?php
namespace IPS\Theme;
class class_core_admin_activitystats extends \IPS\Theme\Template
{	function contentCell( $comment ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	
IPSCONTENT;

if ( $comment instanceof \IPS\Content\Comment ):
$return .= <<<IPSCONTENT

		<h3><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel="noopener">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
		<p class='i-margin-bottom_1 i-color_soft'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "tables", "core", 'front' )->icon( \get_class($comment), $comment->item()->containerWrapper() );
$return .= <<<IPSCONTENT

		</p>
		<p class='i-margin-bottom_1 i-color_soft'>
			
IPSCONTENT;

$htmlsprintf = array($comment->author()->link(), \IPS\DateTime::ts( $comment->mapped('date') )->html(FALSE)); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posted_by_x', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

		</p>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<h3><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel="noopener">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
		<p class='i-margin-bottom_1 i-color_soft'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "tables", "core", 'front' )->icon( \get_class($comment), $comment->containerWrapper() );
$return .= <<<IPSCONTENT

		</p>
		<p class='i-margin-bottom_1 i-color_soft'>
			
IPSCONTENT;

$htmlsprintf = array($comment->author()->link(), \IPS\DateTime::ts( $comment->mapped('date') )->html(FALSE)); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posted_by_x', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

		</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function deletedPercentage( $value, $total, $deleted ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->dellog_retention_period ):
$return .= <<<IPSCONTENT

<div>
	<span class='cStat__number cStat__number--medium' data-number='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title="
IPSCONTENT;

$sprintf = array($deleted, $total); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_deleted_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%</span>
</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <p class='i-color_soft i-text-align_center'>
        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_data', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function overview( $form, $blocks, $excludedApps=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.admin.stats.overview' data-url='?app=core&module=activitystats&controller=overview&do=loadBlock'>
	<div class='ipsBox i-padding_3 i-margin-bottom_2 i-flex i-flex-wrap_wrap i-align-items_center i-gap_2'>
		<ul class='ipsList ipsList--inline i-flex_11 i-margin-block_1'>
			<li>
				<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_show_c', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			</li>
			
IPSCONTENT;

$apps = array();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $blocks as $blockKey => $block ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $block->page == 'activity' AND $subBlocks = $block->getBlocks() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $subBlocks as $subBlock ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $details = $block->getBlockDetails( $subBlock ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $details['app'] && !isset( $apps[ $details['app'] ] ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$apps[ $details['app'] ] = \IPS\Application::load( $details['app'] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

foreach ( \IPS\Application::applications() as $key => $app ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $apps[ $key ] ) ):
$return .= <<<IPSCONTENT

					<li>
						<label>
							<input type='checkbox' id='toggle_app_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->directory, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' name='toggle_app_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->directory, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsInput ipsInput--toggle i-margin-end_1" data-action='toggleApp' data-toggledApp='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->directory, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( !\count( $excludedApps ) || !\in_array( $app->directory, $excludedApps ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</label>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
		{$form}
	</div>
	<div class='cStatsGrid'>
		<div class='ipsGrid i-basis_340'>
			
IPSCONTENT;

foreach ( $blocks as $blockKey => $block ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $block->page == 'activity' AND $subBlocks = $block->getBlocks() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $subBlocks as $subBlock ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $details = $block->getBlockDetails( $subBlock ) ):
$return .= <<<IPSCONTENT

							<div class='ipsBox cStatTile 
IPSCONTENT;

if ( \count( $excludedApps ) && \in_array( $details['app'], $excludedApps ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='statsBlock' data-refresh='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['refresh'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-block='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blockKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-subblock='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subBlock, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-app='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.admin.stats.overviewBlock'
IPSCONTENT;

if ( isset( $details['form'] ) AND $details['form'] === TRUE ):
$return .= <<<IPSCONTENT
 data-nodeFilter=''
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								<div class='cStatTile__header'>
									<h2 class='cStatTile__title'>
IPSCONTENT;

$val = "{$details['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
									
IPSCONTENT;

if ( $details['description'] ):
$return .= <<<IPSCONTENT

										<a href='javascript:void(0)' class='cStatTile__info' title="
IPSCONTENT;

$val = "{$details['description']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>?</a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( isset( $details['form'] ) AND $details['form'] === TRUE ):
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=activitystats&controller=overview&do=loadBlockForm&blockKey={$blockKey}&subBlockKey={$subBlock}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsHover data-ipsHover-onClick="true" data-ipsHover-width="600" class='cStatTile__info cStatTile__form' data-role="filterForm" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'overview_stats_form', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blockKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($subBlock), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-gear'></i></a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
								<div class='cStatTile__body ipsLoading ipsLoading--small'>
									<div class='cStatTile__bodyInner i-text-align_center' data-role='statBlockContent'></div>
								</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function overviewCounts( $values, $previousValues=array(), $nodes=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

	
IPSCONTENT;

reset($values);
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$firstTitle = key( $values );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$firstCount = array_shift( $values );
$return .= <<<IPSCONTENT


	<div>
		<span class='cStat__number cStat__number--medium' data-number='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $firstCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $firstCount );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

if ( isset( $previousValues[ $firstTitle ] ) ):
$return .= <<<IPSCONTENT

			<p class='cStat__change cStat__change--large 
IPSCONTENT;

if ( $previousValues[ $firstTitle ] > $firstCount ):
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

elseif ( $previousValues[ $firstTitle ] < $firstCount ):
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

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $previousValues[ $firstTitle ] );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

if ( $previousValues[ $firstTitle ] > $firstCount ):
$return .= <<<IPSCONTENT

					-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( 100 - ( $firstCount / $previousValues[ $firstTitle ] * 100 ), 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
				
IPSCONTENT;

elseif ( $previousValues[ $firstTitle ] < $firstCount ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $previousValues[ $firstTitle ] ):
$return .= <<<IPSCONTENT
+
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( ( ( $firstCount - $previousValues[ $firstTitle ] ) / $previousValues[ $firstTitle ] ) * 100, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

if ( \count( $values ) ):
$return .= <<<IPSCONTENT

			<hr class='ipsHr'>
			<div class='i-flex'>
				
IPSCONTENT;

foreach ( $values as $title => $count ):
$return .= <<<IPSCONTENT

					<div class='cStatTile__split'>
						<h3 class='cStatTile__subTitle ipsTruncate ipsTruncate_line'>
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<span class='cStat__number cStat__number--small' data-number='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $count, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

if ( isset( $previousValues[ $title ] ) ):
$return .= <<<IPSCONTENT

							<p class='cStat__change cStat__change--small 
IPSCONTENT;

if ( $previousValues[ $title ] > $count ):
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

elseif ( $previousValues[ $title ] < $count ):
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

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $previousValues[ $title ] );
$return .= <<<IPSCONTENT
">
								
IPSCONTENT;

if ( $previousValues[ $title ] > $count ):
$return .= <<<IPSCONTENT

									-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( 100 - ( $count / $previousValues[ $title ] * 100 ), 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
								
IPSCONTENT;

elseif ( $previousValues[ $title ] < $count ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $previousValues[ $title ] ):
$return .= <<<IPSCONTENT
+
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( ( ( $count - $previousValues[ $title ] ) / $previousValues[ $title ] ) * 100, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		
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

	function repWrapper( $form, $count, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ips-template="repWrapper">
	<div class='ipsBox'>
		<h1 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rep_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<div>
			{$form}
		</div>
	</div>
</div>

IPSCONTENT;

if ( $count !== NULL ):
$return .= <<<IPSCONTENT

	<div class='i-margin-top_4'>
		{$table}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}