<?php
namespace IPS\Theme;
class class_core_admin_stats extends \IPS\Theme\Template
{	function activitymessage(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_activity_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>
IPSCONTENT;

		return $return;
}

	function filtersFormTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--filters-form" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
 data-ipsForm>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<input type="hidden" name="plupload" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsForm__filtering" 
IPSCONTENT;

if ( isset( $elements['']['groups'] ) ):
$return .= <<<IPSCONTENT
data-controller='core.admin.stats.filtering'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>

		{$elements['']['date']->html()}

		
IPSCONTENT;

if ( isset( $elements['']['groups'] ) ):
$return .= <<<IPSCONTENT

			<span class='i-font-size_-1'><a href='#' data-role='toggleGroupFilter'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_stats_by_group', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></span>

			<div id='elGroupFilter' class='ipsHide' data-hasGroupFilters="
IPSCONTENT;

if ( \count( $elements['']['groups']->value ) != \count( \IPS\Member\Group::groups( TRUE, FALSE ) ) ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">{$elements['']['groups']->html()}</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function filtersOverviewForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="cStatsFilters 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
 data-ipsForm data-role="dateFilter" data-defaultRange='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['predate']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<input type="hidden" name="plupload" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<ul class='i-flex i-align-items_center i-gap_2'>
		<li data-role='formTitle'>
			<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_date_range_c', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		</li>
		<li class="i-flex_00">
			{$elements['']['predate']->html()}
			<div id='dateFilterInputs'>{$elements['']['date']->html()}</div>
			<button type="submit" class="ipsButton ipsButton--primary ipsHide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'update', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			<button type="button" class="ipsButton ipsButton--text ipsHide" data-action='cancelDateRange'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</li>
	</ul>
</form>
IPSCONTENT;

		return $return;
}

	function helpful( $select, $pagination, $members, $total ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsBox">

IPSCONTENT;

if ( trim( $pagination ) ):
$return .= <<<IPSCONTENT

	{$pagination}
	<br><br>

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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
			</tr>
		</thead>
		<tbody>
			
IPSCONTENT;

foreach ( $select as $row ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$member = \IPS\Member::constructFromData( $members[ $row['member_id'] ] );
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
					<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

		return $return;
}

	function memberactivity( $form, $count, $members, $type='overview' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox' data-ips-template="memberactivity">
	<h1 class='ipsBox__header'>
IPSCONTENT;

if ( $type=="overview" ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'activity_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'activity_no_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h1>
	<div>
		{$form}
	</div>
</div>

IPSCONTENT;

if ( $count !== NULL ):
$return .= <<<IPSCONTENT

	<div class='i-margin-top_3'>
		{$members}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function membervisits( $form, $count, $members ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox' data-ips-template="membervisits">
	<h1 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'visit_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<div>
		{$form}
	</div>
</div>

IPSCONTENT;

if ( $count !== NULL ):
$return .= <<<IPSCONTENT

	<div class='i-margin-top_3'>
		{$members}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function mycharts( $charts ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsSpanGrid'>

IPSCONTENT;

foreach ( $charts AS $chart ):
$return .= <<<IPSCONTENT

	<div class='ipsSpanGrid__6' data-controller='core.admin.members.lazyLoadingProfileBlock' data-url='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=mycharts&do=getChart&chartId=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<div class="i-margin-bottom_1 i-text-align_center">
			<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "tiny_loading.gif", "core", 'front', false );
$return .= <<<IPSCONTENT
">
		</div>
	</div>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function mychartsEmpty(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_3'>
	<p class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mycharts_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
IPSCONTENT;

		return $return;
}

	function overview( $form, $blocks ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.admin.stats.overview' data-url='?app=core&module=stats&controller=overview&do=loadBlock'>
	<div class='ipsBox i-padding_3 i-margin-bottom_1 i-flex i-justify-content_center i-align-items_center'>
		<div class='i-flex_11'></div>
		{$form}
	</div>
	<div class='cStatsGrid'>
		<div class='ipsGrid i-basis_340'>
			
IPSCONTENT;

foreach ( $blocks as $blockKey => $block ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $block->page == 'user' AND $subBlocks = $block->getBlocks() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $subBlocks as $subBlock ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $details = $block->getBlockDetails( $subBlock ) ):
$return .= <<<IPSCONTENT

							<div class='ipsBox cStatTile' data-role='statsBlock' data-block='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blockKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-subblock='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subBlock, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-refresh='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['refresh'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-app='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.admin.stats.overviewBlock'>
								<div class='cStatTile__header'>
									<h2 class='cStatTile__title'>
IPSCONTENT;

$val = "{$details['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
									
IPSCONTENT;

if ( $details['description'] ):
$return .= <<<IPSCONTENT

										<a href='#' class='cStatTile__info' title="
IPSCONTENT;

$val = "{$details['description']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>?</a>
									
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

	function overviewComparisonCount( $count, $previousCount=NULL, $chart=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $previousCount === NULL ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "stats", "core", 'admin' )->overviewCount( $count );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<span class='cStat__number cStat__number--large' data-number='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $count, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT
</span>
	<p class='cStat__change cStat__change--large 
IPSCONTENT;

if ( $previousCount > $count ):
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

elseif ( $previousCount < $count ):
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

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $previousCount );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

if ( $previousCount > $count ):
$return .= <<<IPSCONTENT

			-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( 100 - ( $count / $previousCount * 100 ), 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
		
IPSCONTENT;

elseif ( $previousCount < $count ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $previousCount ):
$return .= <<<IPSCONTENT
+
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( ( ( $count - $previousCount ) / $previousCount ) * 100, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

if ( $chart ):
$return .= <<<IPSCONTENT

	<hr class='ipsHr'>
	{$chart}

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function overviewCount( $count ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class='cStat__number cStat__number--large' data-number='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $count, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function overviewTable( $values, $previousValues=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $values ) ):
$return .= <<<IPSCONTENT

    <table class='ipsTable i-font-size_2 i-margin-top_3 i-text-align_start'>
        
IPSCONTENT;

foreach ( $values as $title => $count ):
$return .= <<<IPSCONTENT

            <tr>
                <td>
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</td>
                <td class='i-text-align_end 
IPSCONTENT;

if ( isset( $previousValues[ $title ] ) AND $previousValues[ $title ] > $count ):
$return .= <<<IPSCONTENT
 i-color_negative
IPSCONTENT;

elseif ( isset( $previousValues[ $title ] ) AND $previousValues[ $title ] < $count ):
$return .= <<<IPSCONTENT
 i-color_positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
                    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( isset( $previousValues[ $title ] ) ):
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

if ( $previousValues[ $title ] > $count ):
$return .= <<<IPSCONTENT

                            <i class='fa-solid fa-arrow-down' title='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( 100 - ( $count / $previousValues[ $title ] * 100 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%'></i>
                        
IPSCONTENT;

elseif ( $previousValues[ $title ] < $count ):
$return .= <<<IPSCONTENT

                            <i class='fa-solid fa-arrow-up' title='
IPSCONTENT;

if ( $previousValues[ $title ] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( ( ( $count - $previousValues[ $title ] ) / $previousValues[ $title ] ) * 100 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></i>
                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                </td>
            </tr>
        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

    </table>

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

	function rankprogressionmessage(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_stats_rank_progression_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>
IPSCONTENT;

		return $return;
}

	function tableheader( $start, $end, $count, $string ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h2 class='ipsBox__header' data-ips-template="tableheader">
	<div>
IPSCONTENT;

$val = "{$string}"; $htmlsprintf = array($start, $end); $pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
</h2>
IPSCONTENT;

		return $return;
}

	function topFollow( $select, $pagination, $members, $total, $column, $activeTab ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h2 class="ipsBox__header" data-ips-template="topFollow">
IPSCONTENT;

$val = "stats_{$activeTab}_desc"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>

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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
			</tr>
		</thead>
		<tbody>
			
IPSCONTENT;

foreach ( $select as $row ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$member = \IPS\Member::constructFromData( $members[ $row[$column] ] );
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
					<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

		return $return;
}

	function topmembers( $select, $pagination, $members, $total ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsBox" data-ips-template="topmembers">
	<h1 class="ipsBox__header">
IPSCONTENT;

$pluralize = array( $total ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_stats_referrer_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h1>
</div>
<br>

IPSCONTENT;

if ( trim( $pagination ) ):
$return .= <<<IPSCONTENT

	{$pagination}
	<br><br>

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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
			</tr>
		</thead>
		<tbody>
			
IPSCONTENT;

foreach ( $select as $row ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$member = \IPS\Member::constructFromData( $members[ $row['member_id'] ] );
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
					<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

		return $return;
}}