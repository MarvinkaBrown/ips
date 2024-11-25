<?php
namespace IPS\Theme;
class class_core_global_members extends \IPS\Theme\Template
{	function attachmentLocations( $locations, $truncateLinks=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $locations ) ):
$return .= <<<IPSCONTENT

	<ul>
		
IPSCONTENT;

foreach ( $locations as $location ):
$return .= <<<IPSCONTENT

			<li>
				
IPSCONTENT;

if ( $location instanceof \IPS\Content or $location instanceof \IPS\Node\Model ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

if ( \IPS\Dispatcher::i()->controllerLocation == 'admin' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( method_exists( $location, 'acpUrl' ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" target="_blank" rel="noreferrer" class="i-color_inherit">
						
IPSCONTENT;

if ( isset( $location::$icon ) ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $location::$title ) ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;

$val = "{$location::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $location instanceof \IPS\Content\Item ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( $location instanceof \IPS\Node\Model ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

elseif ( $location instanceof \IPS\Http\Url ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

if ( \IPS\Dispatcher::i()->controllerLocation == 'admin' ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="i-color_inherit" target="_blank" rel="noreferrer"
IPSCONTENT;

if ( $truncateLinks ):
$return .= <<<IPSCONTENT
 title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

if ( $truncateLinks ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= htmlspecialchars( mb_substr( html_entity_decode( $location ), '0', "60" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) . ( ( mb_strlen( html_entity_decode( $location ) ) > "60" ) ? '&hellip;' : '' );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class="">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'attach_locations_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function bdayForm_day( $name, $value, $error='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<select class="ipsInput ipsInput--select" name="bday[day]">
	<option value='0' 
IPSCONTENT;

if ( isset( $value['day'] ) and $value['day'] == 0  ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
></option>
	
IPSCONTENT;

foreach ( range( 1, 31 ) as $day ):
$return .= <<<IPSCONTENT

		<option value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( isset( $value['day'] ) and $value['day'] == $day  ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</select>

IPSCONTENT;

		return $return;
}

	function bdayForm_month( $name, $value, $error='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<select class="ipsInput ipsInput--select" name="bday[month]">
	<option value='0' 
IPSCONTENT;

if ( isset( $value['month'] ) and $value['month'] == 0  ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
></option>
	
IPSCONTENT;

foreach ( range( 1, 12 ) as $month ):
$return .= <<<IPSCONTENT

		<option value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $month, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( isset( $value['month'] ) and $value['month'] == $month  ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::create()->setDate( 2000, $month, 15 )->strFormat('%B'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</select>


IPSCONTENT;

		return $return;
}

	function bdayForm_year( $name, $value, $error='', $required=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<select class="ipsInput ipsInput--select" name="bday[year]">
    
IPSCONTENT;

if ( !$required  ):
$return .= <<<IPSCONTENT

	<option value='0'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'not_telling', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( array_reverse( range( date('Y') - 150, date('Y') ) ) as $year ):
$return .= <<<IPSCONTENT

		<option value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( isset( $value['year'] ) and $value['year'] == $year  ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</select>

IPSCONTENT;

		return $return;
}

	function dateFilters( $dateRange, $element ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsFieldList'>
	<li>
		<input name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]" value='none' type='radio' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[3]_radio'
IPSCONTENT;

if ( empty($element->value[0]) AND empty($element->value[1]) AND empty($element->value[3]) ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
		<div class='ipsFieldList__content'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'any_time', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>
	</li>
	<li>
		<input name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]" value='range' type='radio' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[3]_radio' data-control="toggle" data-toggles=""
IPSCONTENT;

if ( $element->value[0] ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle"> 
		<div class='ipsFieldList__content'>
			{$dateRange->html()}
		</div>
	</li>
	<li class="i-margin-top_2">
		<input name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]" value='days' type='radio' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[3]_radio'
IPSCONTENT;

if ( $element->value[1] ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
		<div class='ipsFieldList__content'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or_more_than', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->number( $element->name . '[1]', $element->value[1], $element->required, NULL, FALSE, 0, NULL, 1, 0, NULL, FALSE, \IPS\Member::loggedIn()->language()->addToStack( 'days_ago' ), array(), TRUE, array(), $element->name . '_number' );
$return .= <<<IPSCONTENT

		</div>
	</li>
	<li class="i-margin-top_2">
		<input name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]" value='days_lt' type='radio' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[3]_radio'
IPSCONTENT;

if ( ! empty( $element->value[3]) ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
		<div class='ipsFieldList__content'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or_less_than', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->number( $element->name . '[3]', isset( $element->value[3] ) ? $element->value[3] : NULL, $element->required, NULL, FALSE, 0, NULL, 1, 0, NULL, FALSE, \IPS\Member::loggedIn()->language()->addToStack( 'days_ago' ), array(), TRUE, array(), $element->name . '_number_lt' );
$return .= <<<IPSCONTENT

		</div>
	</li>
</ul>
IPSCONTENT;

		return $return;
}

	function ipLookup( $url, $geolocation, $map, $hostName, $counts ) {
		$return = '';
		$return .= <<<IPSCONTENT


<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_address_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
<div class='i-padding_3 i-background_2 cIPInfo' data-ips-template="ipLookup">
	
IPSCONTENT;

if ( $geolocation or $hostName ):
$return .= <<<IPSCONTENT

		<div class='ipsColumns'>
			<div class='ipsColumns__secondary i-basis_280 i-background_2'>
				<div class='i-padding_3 cIPInfo_map'>
					
IPSCONTENT;

if ( $hostName ):
$return .= <<<IPSCONTENT

						<p>
IPSCONTENT;

$sprintf = array($hostName); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_geolocation_hostname', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $geolocation ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $map ):
$return .= <<<IPSCONTENT

							{$map}
							<br>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<p>{$geolocation}</p>
						<p class="i-color_soft i-font-size_-2"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_geolocation_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
			<div class='ipsColumns__primary'>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsSpanGrid i-background_1">
		
IPSCONTENT;

foreach ( $counts as $key => $value ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

				<div class='ipsSpanGrid__4 i-padding_3 i-text-align_center'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'area', $key ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-color_inherit">
						<span class='i-font-size_6 cIPInfo_value'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><br>
						<p class='ipsTruncate_1 ipsMinorTitle'>
IPSCONTENT;

$val = "ipAddresses__{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					</a>
				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class='ipsSpanGrid__4 i-padding_3 i-text-align_center i-color_soft i-opacity_4'>
					<span class='i-font-size_6 cIPInfo_value'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><br>
					<p class='ipsTruncate_1 ipsMinorTitle'>
IPSCONTENT;

$val = "ipAddresses__{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( $geolocation ):
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function messengerQuota( $member, $count ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $member->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging', 'front' ) ) AND $member->group['g_max_messages'] > 0 ):
$return .= <<<IPSCONTENT

	<div class='ipsMessenger__quota i-text-align_center'>
		<div data-role="quotaTooltip" data-ipsTooltip data-ipsTooltip-label="
IPSCONTENT;

$sprintf = array($member->group['g_max_messages']); $pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_quota', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

$percent = floor( 100 / $member->group['g_max_messages'] * $count );
$return .= <<<IPSCONTENT

			<meter class="ipsMeter" data-role='quotaWidth' max='100' high='90' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $percent > 100 ? 100 : $percent, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></meter>
			<small class='i-color_soft i-margin-top_1 i-font-size_-1 i-font-weight_500 i-display_block'>
IPSCONTENT;

$sprintf = array($percent); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_quota_short', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</small>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function nameHistoryRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		<li class='ipsData__item'>
			<div class="ipsData__main">
		   		<h4 class='ipsData__title'>
IPSCONTENT;

$val = ( $row['log_date'] instanceof \IPS\DateTime ) ? $row['log_date'] : \IPS\DateTime::ts( $row['log_date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</h4>
		   		<p class='ipsData__meta'>
		      		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['log_data']['old'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<i class='fa-solid fa-angle-right i-margin-start_icon i-margin-end_icon'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['log_data']['new'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		      	</p>
		   </div>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function nameHistoryTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox'>
    
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

        <div class="ipsButtonBar ipsButtonBar--top">
            <div class="ipsButtonBar__pagination" data-role="tablePagination">
                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

            </div>
        </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

    <i-data>
        <ol class="ipsData ipsData--table ipsData--name-history-table 
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
" id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="tableRows">
            
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

        </ol>
    </i-data>
    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

        <p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rows_in_table', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

        <div class="ipsButtonBar ipsButtonBar--bottom">
            <div class="ipsButtonBar__pagination" data-role="tablePagination">
                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

            </div>
        </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function notificationLabel( $key, $data ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $data['icon'] ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$val = "{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function notificationsSettingsRow( $field, $details ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsFieldRow 
IPSCONTENT;

if ( $field->error ):
$return .= <<<IPSCONTENT
ipsFieldRow_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $field->htmlId ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( \IPS\Dispatcher::i()->controllerLocation === 'admin' or $details['showTitle'] ):
$return .= <<<IPSCONTENT

		<label class='ipsFieldRow__label'>
			
IPSCONTENT;

$val = "{$details['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</label>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsFieldRow__content'>
		
IPSCONTENT;

if ( $details['description'] ):
$return .= <<<IPSCONTENT

			<div class='i-font-weight_500 i-margin-bottom_2'>
IPSCONTENT;

$val = "{$details['description']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<ul class="ipsFieldList">
			
IPSCONTENT;

if ( isset( $details['extra'] ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $details['extra'] as $k => $option ):
$return .= <<<IPSCONTENT

					<li>
						<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" 
IPSCONTENT;

if ( $option['value'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
						<div class='ipsFieldList__content'>
							<label for='elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>
IPSCONTENT;

$val = "{$option['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
							
IPSCONTENT;

if ( isset( $option['description'] ) ):
$return .= <<<IPSCONTENT

								<div class='ipsFieldRow__desc'>
IPSCONTENT;

$val = "{$option['description']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $details['options'] as $k => $option ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $k === 'inline' and isset( $details['options']['push'] ) ):
$return .= <<<IPSCONTENT

					<li>
						<ul class="ipsFieldList " role="radiogroup">
							<li>
								<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="push" 
IPSCONTENT;

if ( $details['options']['push']['value'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_push" 
IPSCONTENT;

if ( !$option['editable'] ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
								<div class='ipsFieldList__content'>
									<label for="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_push">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications_list_and_app', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
								</div>
							</li>
							<li>
								<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="inline" 
IPSCONTENT;

if ( $details['options']['inline']['value'] and !$details['options']['push']['value'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_inline" 
IPSCONTENT;

if ( !$option['editable'] ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
								<div class='ipsFieldList__content'>
									<label for="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_inline">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications_list_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
								</div>
							</li>
							<li>
								<input type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="" 
IPSCONTENT;

if ( !$details['options']['inline']['value'] and !$details['options']['push']['value'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_off" 
IPSCONTENT;

if ( !$option['editable'] ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
								<div class='ipsFieldList__content'>
									<label for="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_off">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications_no_list', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
								</div>
							</li>
						</ul>
					</li>
				
IPSCONTENT;

elseif ( $k !== 'push' or !isset( $details['options']['inline'] ) ):
$return .= <<<IPSCONTENT

					<li>
						<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" 
IPSCONTENT;

if ( $option['value'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !$option['editable'] ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
						<div class='ipsFieldList__content'>
							<label for='elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>
IPSCONTENT;

$val = "{$option['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function photoCrop( $name, $value, $photo ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='core.global.core.cropper' id='elPhotoCropper' class='i-background_2 i-text-align_center i-padding_3'>
	<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'photo_crop_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'photo_crop_instructions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<br>

	<div class='ipsForm_cropper'>
		<div data-role='cropper'>
			<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role='profilePhoto'>
		</div>
	</div>

	<input type='hidden' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='topLeftX'>
	<input type='hidden' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='topLeftY'>
	<input type='hidden' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[2], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='bottomRightX'>
	<input type='hidden' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[3]' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[3], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='bottomRightY'>
</div>
IPSCONTENT;

		return $return;
}}