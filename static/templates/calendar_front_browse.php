<?php
namespace IPS\Theme;
class class_calendar_front_browse extends \IPS\Theme\Template
{	function calendarDay( $calendars, $date, $events, $tomorrow, $yesterday, $today, $thisCalendar, $jump ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class=''>
	<div class='ipsPager cCalendarNav i-padding-block_2 i-padding-inline_3 i-background_2 i-border-start-start-radius_box i-border-start-end-radius_box'>
		<div class='ipsPager_prev'>
			<a href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&id={$thisCalendar->_id}&y={$yesterday->year}&m={$yesterday->mon}&d={$yesterday->mday}", null, "calendar_calday", array( $thisCalendar->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&y={$yesterday->year}&m={$yesterday->mon}&d={$yesterday->mday}", null, "calendar_day", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($yesterday->monthName, $yesterday->mday, $yesterday->year); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_day', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' rel='prev nofollow' data-action='changeView'>
				<span class='ipsPager_type'>
IPSCONTENT;

$sprintf = array($yesterday->monthName, $yesterday->mday); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_day_noyear', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
				<span class='ipsPager_title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $yesterday->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</a>
		</div>
		<div class='ipsPager_center cCalendarNav' data-role='calendarNav'>
			<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$sprintf = array($date->monthName, $date->mday, $date->year); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_day', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
		</div>
		<div class='ipsPager_next'>
			<a href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&id={$thisCalendar->_id}&y={$tomorrow->year}&m={$tomorrow->mon}&d={$tomorrow->mday}", null, "calendar_calday", array( $thisCalendar->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&y={$tomorrow->year}&m={$tomorrow->mon}&d={$tomorrow->mday}", null, "calendar_day", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($tomorrow->monthName, $tomorrow->mday, $tomorrow->year); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_day', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' rel='next nofollow' data-action='changeView'>
				<span class='ipsPager_type'>
IPSCONTENT;

$sprintf = array($tomorrow->monthName, $tomorrow->mday); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_day_noyear', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
				<span class='ipsPager_title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tomorrow->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</a>
		</div>
	</div>

	<div class=''>

		<div class=''>
			<h2 class='ipsTitle ipsTitle--h4 ipsTitle--padding'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_happening_today', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

if ( $events['count'] > 0 ):
$return .= <<<IPSCONTENT

				<ol class='cCalendarDay ipsData'>
					
IPSCONTENT;

if ( isset( $events['allDay'] ) && \count( $events['allDay'] ) ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item cCalendarDay_allDay'>
							<div class='i-basis_140 cCalendarDay_hour'>
								<h2 class='i-font-weight_600 i-color_hard i-font-size_4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'day_view_all_day', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
							</div>
							<div class='ipsData__main'>
								<ul class='ipsGrid ipsGrid--event-daily i-basis_300'>
									
IPSCONTENT;

foreach ( $events['allDay'] as $event ):
$return .= <<<IPSCONTENT

										<li>
											
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "calendar" )->eventBlock( $event, $date, true );
$return .= <<<IPSCONTENT

										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							</div>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

foreach ( $events as $hour => $hourEvents ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !empty( $hourEvents ) && $hour !== 'allDay' && $hour !== 'count' ):
$return .= <<<IPSCONTENT

							<li class='ipsData__item'>
								<div class='i-basis_140 cCalendarDay_hour'>
									<h2 class='i-font-weight_600 i-color_hard i-font-size_4'>
										
IPSCONTENT;

if ( \IPS\calendar\Date::usesAmPm() ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::getTwelveHour( $hour ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<span>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::getAmPm( $hour ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $hour, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<span>:00</span>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</h2>
								</div>
								<div class='ipsData__main'>
									<ul class='ipsGrid ipsGrid--event-daily i-basis_300'>
										
IPSCONTENT;

foreach ( $hourEvents as $idx => $event ):
$return .= <<<IPSCONTENT

											<li>
												
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "calendar" )->eventBlock( $event, $date, true );
$return .= <<<IPSCONTENT

											</li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</div>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_events_today', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function calendarHeader( $calendars, $thisCalendar, $jump, $downloadLinks ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $thisCalendar and $club = $thisCalendar->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $thisCalendar );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class="ipsPageHeader ipsBox ipsPull" id='elCalendarsHeader'>
	<div class='ipsPageHeader__row'>
		<div class="ipsPageHeader__primary">
			<span class="ipsPageHeader__title">
				
IPSCONTENT;

if ( $thisCalendar and $club = $thisCalendar->club() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisCalendar->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href='#elCalendars_menu' id='elCalendars' data-ipsMenu>
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisCalendar->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all_calendars', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-angle-down'></i></a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</span>
		</div>
		<div class='ipsButtons'>
			
IPSCONTENT;

if ( $thisCalendar and \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "analytics", "cloud" )->analyticsButton( $thisCalendar, $thisCalendar->id );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<a href='#elCalendarSettings_menu' id='elCalendarSettings' data-ipsMenu data-ipsMenu-alignCenter class='ipsButton ipsButton--inherit'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subscribe_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></a>
			<a href='#elCalendarJump' data-ipsMenu data-ipsMenu-menuID='elCalendarJump' data-ipsMenu-closeOnClick='false' class='ipsButton ipsButton--inherit'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'jump_to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></a>
			
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

				<div class='ipsResponsive_hidePhone'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'calendar', 'calendar', $thisCalendar->id, \IPS\calendar\Event::containerFollowerCount( $thisCalendar ) );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>

{$jump->customTemplate( array( \IPS\Theme::i()->getTemplate( 'browse' ), 'dateJump' ) )}

<ul class='ipsMenu ipsMenu_auto ipsHide' id='elCalendars_menu'>
	<li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=month", null, "calendar", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'><span class='cCalendarIcon cEvents_style_blank'></span> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all_calendars', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
	
IPSCONTENT;

foreach ( $calendars as $calendar ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $club = $calendar->club() ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $calendar->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span class='cCalendarIcon cEvents_style_blank'></span> 
IPSCONTENT;

$sprintf = array($club->name, $calendar->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $calendar->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span class='cCalendarIcon cEvents_style
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $calendar->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></span> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $calendar->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "calendar" )->subscribeMenu( $thisCalendar, $downloadLinks );
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function calendarMonth( $calendars, $date, $events, $today, $thisCalendar, $jump, $startDates=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	<div class='ipsPager cCalendarNav i-padding-block_2 i-padding-inline_3 i-background_2 i-border-start-start-radius_box i-border-start-end-radius_box'>
		<div class='ipsPager_prev'>
			<a href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=month&id={$thisCalendar->_id}&y={$date->lastMonth('year')}&m={$date->lastMonth('mon')}", null, "calendar_calmonth", array( $thisCalendar->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&y={$date->lastMonth('year')}&m={$date->lastMonth('mon')}", null, "calendar_month", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($date->lastMonth('monthName'), $date->lastMonth('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' rel='prev nofollow' data-action='changeView'>
				<span class='ipsPager_type'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $date->lastMonth('monthName'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				<span class='ipsPager_title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $date->lastMonth('year'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</a>
		</div>
		<div class='ipsPager_center cCalendarNav' data-role='calendarNav'>
			<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$sprintf = array($date->monthName, $date->year); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
		</div>
		<div class='ipsPager_next'>
			<a href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=month&id={$thisCalendar->_id}&y={$date->nextMonth('year')}&m={$date->nextMonth('mon')}", null, "calendar_calmonth", array( $thisCalendar->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&y={$date->nextMonth('year')}&m={$date->nextMonth('mon')}", null, "calendar_month", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($date->nextMonth('monthName'), $date->nextMonth('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' rel='next nofollow' data-action='changeView'>
				<span class='ipsPager_type'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $date->nextMonth('monthName'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				<span class='ipsPager_title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $date->nextMonth('year'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</a>
		</div>
	</div>

	<div class=''>
		<table class='cCalendar' data-controller='calendar.front.browse.monthView'>
			<tr>
				
IPSCONTENT;

foreach ( \IPS\calendar\Date::getDayNames() as $day  ):
$return .= <<<IPSCONTENT

					<th class='' data-short='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day['abbreviated'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-veryShort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day['letter'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day['full'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></th>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</tr>
			<tr>
				
IPSCONTENT;

for ( $i=0; $i < $date->firstDayOfMonth('wday'); $i++  ):
$return .= <<<IPSCONTENT

					<td class='cCalendar_nonDate'>&nbsp;</td>
				
IPSCONTENT;

endfor;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

for ( $j=$i+$date->lastDayOfMonth('mday'), $k=1; $i < $j; $i++, $k++  ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$k = str_pad( $k, 2, '0', STR_PAD_LEFT );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $i%7 == 0 ):
$return .= <<<IPSCONTENT

						</tr>
						<tr>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<td class='cCalendar_date
IPSCONTENT;

if ( $k == 1 ):
$return .= <<<IPSCONTENT
 cCalendar_firstDay
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $today->mysqlDatetime( FALSE ) == $date->year . '-' . $date->mon . '-' . $k  ):
$return .= <<<IPSCONTENT
 cCalendar_today
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( isset( $events[ $date->year . '-' . $date->mon . '-' . $k ] ) ):
$return .= <<<IPSCONTENT
 cCalendar_hasEvents
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
						<div>
							<a href='
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&id={$thisCalendar->_id}&y={$date->year}&m={$date->mon}&d={$k}&view=day", null, "calendar_calday", array( $thisCalendar->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&y={$date->year}&m={$date->mon}&d={$k}&view=day", null, "calendar_day", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='cCalendar_dayNumber'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

if ( ( $thisCalendar AND $thisCalendar->can('add') ) OR ( !$thisCalendar AND \IPS\calendar\Calendar::canOnAny('add') ) ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&do=submit&id={$thisCalendar->_id}&y={$date->year}&m={$date->mon}&d={$k}&view=day", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&y={$date->year}&m={$date->mon}&d={$k}&view=day", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='cCalendar_miniAddEvent'><i class='fa-solid fa-plus'></i></a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $events[ $date->year . '-' . $date->mon . '-' . $k ] ) ):
$return .= <<<IPSCONTENT

								<ul class='cEvents_wrapper'>
									
IPSCONTENT;

if ( isset( $events[ $date->year . '-' . $date->mon . '-' . $k ]['ranged'] ) ):
$return .= <<<IPSCONTENT

										<li class='cEvents_ranged'>
											<ul class='cEvents'>
												
IPSCONTENT;

foreach ( $events[ $date->year . '-' . $date->mon . '-' . $k ]['ranged'] as $event  ):
$return .= <<<IPSCONTENT

													<li class='cEvents_event cEvents_style
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->calendar_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $event->nextOccurrence( \IPS\calendar\Date::getDate( $date->year, $date->mon, $k ), 'startDate' ) AND $event->nextOccurrence( \IPS\calendar\Date::getDate( $date->year, $date->mon, $k ), 'startDate' )->mysqlDatetime( FALSE ) == $date->year . '-' . $date->mon . '-' . $k  ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$startDates[ $event->id ] = $date->year . '-' . $date->mon . '-' . $k;
$return .= <<<IPSCONTENT
 cEvents_first
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $event->nextOccurrence( \IPS\calendar\Date::getDate( $date->year, $date->mon, $k ) ?: $event->nextOccurrence( \IPS\calendar\Date::getDate( $date->year, $date->mon, $k ), 'startDate' ), 'endDate' ) AND $event->nextOccurrence( \IPS\calendar\Date::getDate( $date->year, $date->mon, $k ) ?: $event->nextOccurrence( \IPS\calendar\Date::getDate( $date->year, $date->mon, $k ), 'startDate' ), 'endDate' )->mysqlDatetime( FALSE ) == $date->year . '-' . $date->mon . '-' . $k  ):
$return .= <<<IPSCONTENT
 cEvents_last
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-eventID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
														<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsHover data-ipsHover-target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('hovercard')->setQuerystring( 'sd', isset( $startDates[ $event->id ] ) ? $startDates[ $event->id ] : $date->year . '-' . $date->mon . '-' . $k ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
<span class='cEvents_time'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_start_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( mb_substr( html_entity_decode( $event->title ), '0', "15" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) . ( ( mb_strlen( html_entity_decode( $event->title ) ) > "15" ) ? '&hellip;' : '' );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $event->hidden() === 1 ):
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-triangle-exclamation'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
													</li>
												
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

											</ul>
										</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( isset( $events[ $date->year . '-' . $date->mon . '-' . $k ]['single'] ) ):
$return .= <<<IPSCONTENT

										<li class='cEvents_single'>
											<ul class='cEvents'>
												
IPSCONTENT;

foreach ( $events[ $date->year . '-' . $date->mon . '-' . $k ]['single'] as $event  ):
$return .= <<<IPSCONTENT

													<li class='cEvents_event cEvents_style
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->calendar_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-eventID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
														<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsHover data-ipsHover-target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('hovercard')->setQuerystring( 'sd', $date->year . '-' . $date->mon . '-' . $k ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
<span class='cEvents_time'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_start_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( mb_substr( html_entity_decode( $event->title ), '0', "15" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) . ( ( mb_strlen( html_entity_decode( $event->title ) ) > "15" ) ? '&hellip;' : '' );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $event->hidden() === 1 ):
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-triangle-exclamation'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
													</li>
												
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

											</ul>
										</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</td>
				
IPSCONTENT;

endfor;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

for ( ; $i%7 != 0; $i++ ):
$return .= <<<IPSCONTENT

					<td class='cCalendar_nonDate'>&nbsp;</td>
				
IPSCONTENT;

endfor;
$return .= <<<IPSCONTENT

			</tr>
		</table>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function calendarWeek( $calendars, $date, $events, $nextWeek, $lastWeek, $days, $today, $thisCalendar, $jump, $startDates=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class=''>
	<div class='ipsPager cCalendarNav i-padding-block_2 i-padding-inline_3 i-background_2 i-border-start-start-radius_box i-border-start-end-radius_box'>
		<div class='ipsPager_prev'>
			<a href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=week&id={$thisCalendar->_id}&w={$lastWeek->year}-{$lastWeek->mon}-{$lastWeek->mday}", null, "calendar_calweek", array( $thisCalendar->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=week&w={$lastWeek->year}-{$lastWeek->mon}-{$lastWeek->mday}", null, "calendar_week", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($lastWeek->firstDayOfWeek('monthNameShort'), $lastWeek->firstDayOfWeek('mday'), $lastWeek->firstDayOfWeek('year'), $lastWeek->lastDayOfWeek('monthNameShort'), $lastWeek->lastDayOfWeek('mday'), $lastWeek->lastDayOfWeek('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_week_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' rel='prev nofollow' data-action='changeView'>
				<span class='ipsPager_type'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'previous_week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				<span class='ipsPager_title'>
					<span class='ipsResponsive_hidePhone'>
						
IPSCONTENT;

$sprintf = array($lastWeek->firstDayOfWeek('monthNameShort'), $lastWeek->firstDayOfWeek('mday'), $lastWeek->firstDayOfWeek('year'), $lastWeek->lastDayOfWeek('monthNameShort'), $lastWeek->lastDayOfWeek('mday'), $lastWeek->lastDayOfWeek('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_week_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</span>
					<span class='ipsResponsive_showPhone'>
						
IPSCONTENT;

$sprintf = array($lastWeek->firstDayOfWeek('monthNameShort'), $lastWeek->firstDayOfWeek('mday'), $lastWeek->firstDayOfWeek('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_week_title_wb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</span>
				</span>
			</a>
		</div>
		<div class='ipsPager_center cCalendarNav' data-role='calendarNav'>
			<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$sprintf = array($date->firstDayOfWeek('monthNameShort'), $date->firstDayOfWeek('mday'), $date->firstDayOfWeek('year'), $date->lastDayOfWeek('monthNameShort'), $date->lastDayOfWeek('mday'), $date->lastDayOfWeek('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_week_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
		</div>
		<div class='ipsPager_next'>
			<a href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=week&id={$thisCalendar->_id}&w={$nextWeek->year}-{$nextWeek->mon}-{$nextWeek->mday}", null, "calendar_calweek", array( $thisCalendar->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=week&w={$nextWeek->year}-{$nextWeek->mon}-{$nextWeek->mday}", null, "calendar_week", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($nextWeek->firstDayOfWeek('monthNameShort'), $nextWeek->firstDayOfWeek('mday'), $nextWeek->firstDayOfWeek('year'), $nextWeek->lastDayOfWeek('monthNameShort'), $nextWeek->lastDayOfWeek('mday'), $nextWeek->lastDayOfWeek('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_week_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' rel='next nofollow' data-action='changeView'>
				<span class='ipsPager_type'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				<span class='ipsPager_title'>
					<span class='ipsResponsive_hidePhone'>
						
IPSCONTENT;

$sprintf = array($nextWeek->firstDayOfWeek('monthNameShort'), $nextWeek->firstDayOfWeek('mday'), $nextWeek->firstDayOfWeek('year'), $nextWeek->lastDayOfWeek('monthNameShort'), $nextWeek->lastDayOfWeek('mday'), $nextWeek->lastDayOfWeek('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_week_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</span>
					<span class='ipsResponsive_showPhone'>
						
IPSCONTENT;

$sprintf = array($nextWeek->firstDayOfWeek('monthNameShort'), $nextWeek->firstDayOfWeek('mday'), $nextWeek->firstDayOfWeek('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_week_title_wb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</span>
				</span>
			</a>
		</div>
	</div>

	<div class=''>
		<ul class='cCalendarWeek'>
			
IPSCONTENT;

foreach ( $days as $day ):
$return .= <<<IPSCONTENT

			<li class='ipsColumns ipsColumns--lines i-border-bottom_1'>
				<div class='ipsColumns__secondary i-basis_280 i-padding_3 cCalendarWeek_day 
IPSCONTENT;

if ( $today->year == $day->year AND $today->mon == $day->mon AND $today->mday == $day->mday ):
$return .= <<<IPSCONTENT
 i-background_5
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-background_2
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					<h2 class='ipsTitle ipsTitle--h5 ipsTitle--margin i-color_hard i-link-color_inherit'><a href='
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&id={$thisCalendar->_id}&y={$day->year}&m={$day->mon}&d={$day->mday}&view=day", null, "calendar_calday", array( $thisCalendar->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&y={$day->year}&m={$day->mon}&d={$day->mday}&view=day", null, "calendar_day", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
					<p class='i-color_soft i-text-transform_uppercase i-font-weight_600 i-font-size_-1'>
IPSCONTENT;

$sprintf = array($day->monthName, $day->mday, $day->year); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_day', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
				</div>
				<div class='ipsColumns__primary i-padding_3 
IPSCONTENT;

if ( $today->year == $day->year AND $today->mon == $day->mon AND $today->mday == $day->mday ):
$return .= <<<IPSCONTENT
 i-background_2
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

if ( isset( $events[ $day->year . '-' . $day->mon . '-' . $day->mday ] ) ):
$return .= <<<IPSCONTENT

						<ul class='cEvents_wrapper'>
							
IPSCONTENT;

if ( isset( $events[ $day->year . '-' . $day->mon . '-' . $day->mday ]['ranged'] ) ):
$return .= <<<IPSCONTENT

								<li class='cEvents_ranged'>
									<ul class='cEvents'>
										
IPSCONTENT;

foreach ( $events[ $day->year . '-' . $day->mon . '-' . $day->mday ]['ranged'] as $event  ):
$return .= <<<IPSCONTENT

											<li class='cEvents_event cEvents_style
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->calendar_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $event->nextOccurrence( $day, 'startDate' ) AND $event->nextOccurrence( $day, 'startDate' )->mysqlDatetime( FALSE ) == $day->mysqlDatetime( FALSE )  ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$startDates[ $event->id ] = $day->mysqlDatetime( FALSE );
$return .= <<<IPSCONTENT
 cEvents_first
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $event->nextOccurrence( $day ?: $event->nextOccurrence( $day, 'startDate' ), 'endDate' ) AND $event->nextOccurrence( $day ?: $event->nextOccurrence( $day, 'startDate' ), 'endDate' )->mysqlDatetime( FALSE ) == $day->mysqlDatetime( FALSE )  ):
$return .= <<<IPSCONTENT
 cEvents_last
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
												<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsHover data-ipsHover-target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('hovercard')->setQuerystring( 'sd', isset( $startDates[ $event->id ] ) ? $startDates[ $event->id ] : $day->mysqlDatetime( FALSE ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $event->hidden() === 1 ):
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-triangle-exclamation'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
											</li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $events[ $day->year . '-' . $day->mon . '-' . $day->mday ]['single'] ) ):
$return .= <<<IPSCONTENT

								<li class='cEvents_single'>
									<ul class='cEvents'>
										
IPSCONTENT;

foreach ( $events[ $day->year . '-' . $day->mon . '-' . $day->mday ]['single'] as $event  ):
$return .= <<<IPSCONTENT

											<li class='cEvents_event cEvents_style
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->calendar_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
												<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsHover data-ipsHover-target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('hovercard')->setQuerystring( 'sd', $day->year . '-' . $day->mon . '-' . $day->mday ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $event->hidden() === 1 ):
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-triangle-exclamation'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
											</li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_events_today', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function calendarWrapper( $calendar, $calendars, $thisCalendar, $jump, $date, $downloadLinks ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !$thisCalendar or !$thisCalendar->club() ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "overview", "calendar" )->header( $date, $thisCalendar );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "calendar" )->calendarHeader( $calendars, $thisCalendar, $jump, $downloadLinks );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $thisCalendar and $club = $thisCalendar->club() ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "overview", "calendar" )->header( $date, $thisCalendar, null, false );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div data-controller='calendar.front.browse.main' class='ipsBox ipsPull'>
	{$calendar}
</div>


IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

	<div class="ipsBox ipsBox--padding ipsPull ipsResponsive_showPhone">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'calendar', 'calendar', $thisCalendar->id, \IPS\calendar\Event::containerFollowerCount( $thisCalendar ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function dateJump( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elCalendarJump' class='ipsMenu ipsMenu_medium ipsHide'>
	<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--date-jump" method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsForm>
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

		<ul class="ipsForm ipsForm--vertical ipsForm--data-jump">
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Date ):
$return .= <<<IPSCONTENT

						<li class='ipsFieldRow ipsFieldRow--fullWidth'>
							<input type="date" class='ipsInput ipsInput--text' data-control="date" name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $input->required === TRUE ):
$return .= <<<IPSCONTENT
required
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 value="
IPSCONTENT;

if ( $input->defaultValue instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->defaultValue->format('Y-m-d'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						</li>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						{$input->rowHtml($form)}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<li class='ipsFieldRow'>
				<input type="submit" class="ipsButton ipsButton--primary ipsButton--wide ipsButton--small" value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'calendar_jump', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
			</li>
			<li class='ipsFieldRow'>
				<input type="submit" class="ipsButton ipsButton--text ipsButton--wide ipsButton--small" value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'calendar_jump_today', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" name="goto">
			</li>
		</ul>
	</form>
</div>
IPSCONTENT;

		return $return;
}}