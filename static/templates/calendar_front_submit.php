<?php
namespace IPS\Theme;
class class_calendar_front_submit extends \IPS\Theme\Template
{	function calendarSelector( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class=''>
    {$form}
</div>
IPSCONTENT;

		return $return;
}

	function datesForm( $name, $value, $timeZones, $error='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='calendar.front.submit.dates' id='eventDateSelection' class=''>
	
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--error">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<ul class='i-basis_100p i-padding_3 ipsList ipsList--inline'>
		<li>
			<label class="i-flex i-align-items_center i-gap_1">
				<input type="checkbox" role="checkbox" name="event_dates[single_day]" 
IPSCONTENT;

if ( isset($value['single_day']) AND $value['single_day'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 value="1" id="check_single_day" class="ipsInput ipsInput--toggle">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_single_day', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</label>
		</li>
		<li>
			<label class="i-flex i-align-items_center i-gap_1">
				<input type="checkbox" role="checkbox" name="event_dates[all_day]" 
IPSCONTENT;

if ( isset($value['all_day']) AND $value['all_day'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 value="1" id="check_all_day" class="ipsInput ipsInput--toggle">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_all_day', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</label>
		</li>
		<li class='i-color_soft i-link-color_inherit' id='elTimezone'>
			<a href='#elTimezoneSelector_menu' id='elTimezoneSelector' data-ipsMenu data-ipsMenu-closeOnClick="false" data-ipsMenu-appendTo="#eventDateSelection"><i class='fa-solid fa-earth-americas i-margin-end_icon'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_timezone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: <span class="i-color_hard i-font-weight_500" data-role="timezone_display"></span> <i class='fa-solid fa-angle-down'></i></a>
		</li>
	</ul>
	<div class='ipsFluid i-basis_340 i-gap_lines i-border-bottom_3 i-border-top_3'>
		<div class='i-padding_3 i-background_2' id='elDateGrid_start'>
			<h2 class='ipsTitle ipsTitle--h4 i-margin-bottom_2'><i class="fa-regular fa-clock i-color_soft i-margin-end_icon i-opacity_5"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_event_start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<div class='i-flex i-flex-wrap_wrap i-align-items_center i-gap_1'>
				<div class='i-flex_11 cCalendarSubmit_field'>
					<i class='fa-solid fa-calendar'></i>
					<input type="date" data-summaryControl name="event_dates[start_date]" value="
IPSCONTENT;

if ( $value['start_date'] instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['start_date']->format('Y-m-d'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['start_date'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-preferredFormat="
IPSCONTENT;

if ( $value['start_date'] instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['start_date']->fullYearLocaleDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['start_date'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" required aria-required='true' class="ipsInput ipsInput--text" data-control="date" id='event_start_date' placeholder='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( 'YYYY', 'MM', 'DD' ), array( \IPS\Member::loggedIn()->language()->addToStack('_date_format_yyyy'), \IPS\Member::loggedIn()->language()->addToStack('_date_format_mm'), \IPS\Member::loggedIn()->language()->addToStack('_date_format_dd') ), str_replace( 'Y', 'YY', \IPS\Member::loggedIn()->language()->preferredDateFormat() ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				</div>
				<div class='i-flex_11 cCalendarSubmit_field' id='start_time_wrap'>
					<i class='fa-regular fa-clock'></i>
					<input name="event_dates[start_time]" id="start_time" type="time" size='12' class="ipsInput ipsInput--text" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( '_time_format_hhmm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" step='60' min='00:00' value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['start_time'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				</div>
			</div>
		</div>
		<div class='i-padding_3 i-background_2' id='elDateGrid_end'>
			<h2 class='ipsTitle ipsTitle--h4 i-margin-bottom_2'><i class="fa-solid fa-forward-fast i-color_soft i-margin-end_icon i-opacity_5"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_event_end', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<div class='i-flex i-flex-wrap_wrap i-align-items_center i-gap_1'>
				<div class='i-flex_11 cCalendarSubmit_field' id='event_end_date_wrap'>
					<i class='fa-solid fa-calendar'></i>
					<input type="date" name="event_dates[end_date]" value="
IPSCONTENT;

if ( $value['end_date'] instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['end_date']->format('Y-m-d'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['end_date'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-preferredFormat="
IPSCONTENT;

if ( $value['end_date'] instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['end_date']->fullYearLocaleDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $value['start_date'] instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['start_date']->fullYearLocaleDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['end_date'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text" data-control="date" id="event_end_date" placeholder='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_strtoupper( str_replace( 'Y', 'YY', \IPS\Member::loggedIn()->language()->preferredDateFormat() ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				</div>
				<div class='i-flex_11 cCalendarSubmit_field' id='end_time_wrap'>
					<i class='fa-regular fa-clock'></i>
					<input name="event_dates[end_time]" id="end_time" type="time" size='12' class="ipsInput ipsInput--text" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( '_time_format_hhmm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" step='60' min='00:00' value="
IPSCONTENT;

if ( isset( $value['end_time'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['end_time'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
				</div>
			</div>
			<ul class='i-margin-top_2 ipsList ipsList--inline' id='end_date_controls'>
				<li>
					<label class="i-flex i-align-items_center i-gap_1">
						<input type="checkbox" role="checkbox" name="event_dates[no_end_time]" 
IPSCONTENT;

if ( !isset( $value['end_time'] ) OR !$value['end_time'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 value="1" id="check_no_end_time" class="ipsInput ipsInput--toggle">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_end_time', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</label>
				</li>
			</ul>
		</div>
		<div class='i-padding_3 i-background_2'>
			<strong class='ipsTitle ipsTitle--h4 i-margin-bottom_2 i-display_block'><i class="fa-solid fa-clipboard-list i-color_soft i-margin-end_icon i-opacity_5"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_event_summary', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			<p><span data-role='dateSummary'></span></p>
			<p id='elRepeatRow_hidden' class="i-margin-top_1">
				<label class="i-flex i-align-items_center i-gap_1">
					<input type='checkbox' role='checkbox' name='event_dates[event_repeat]' value='1' id="elRepeatCb" 
IPSCONTENT;

if ( isset($value['event_repeat']) AND $value['event_repeat'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_event_repeats_check', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</label>
			</p>
			<div id='elRepeatRow_shown' class='i-margin-top_1 ipsJS_hide'>
				<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_event_repeats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				<span id='elRecurEdit' data-ipsMenu data-ipsMenu-closeOnClick='false' data-ipsMenu-appendTo="#eventDateSelection" class='i-cursor_pointer i-color_hard i-font-weight_600'><span data-role='recurringSummary'></span> <i class='fa-solid fa-caret-down'></i></span> <a href='#' id='elRecurRemove' class='i-margin-start_2 i-font-size_-2'>(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_event_remove_repeat', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</a>
			</div>
		</div>
	</div>
	<div id='elTimezoneSelector_menu' class='ipsHide ipsMenu'>
		<div class='ipsForm'>
			<div class="ipsFieldRow">
				<select class="ipsInput ipsInput--select" name="event_dates[event_timezone]" id="event_timezone">
					
IPSCONTENT;

foreach ( $timeZones as $k => $v ):
$return .= <<<IPSCONTENT

						<option value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $value['event_timezone'] == $k ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-abbreviated="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</select>
			</div>
			<div class='ipsSubmitRow'>
				<button class='ipsButton ipsButton--primary' role='button' data-action="updateTimezone">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</div>
	</div>

	<div id='elRecurEdit_menu' class='ipsHide ipsMenu ipsMenu_wide'>
		<ul class="ipsForm">
			<li class='ipsFieldRow'>
				<label class='ipsFieldRow__label' for=''>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_repeats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<div class='ipsFieldRow__content'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->select( 'event_dates[event_repeats]', $value['event_repeats'], FALSE, array( 'daily' => \IPS\Member::loggedIn()->language()->addToStack('event_rpm_repeats_daily'), 'weekly' => \IPS\Member::loggedIn()->language()->addToStack('event_rpm_repeats_weekly'), 'monthly' => \IPS\Member::loggedIn()->language()->addToStack('event_rpm_repeats_monthly'), 'yearly' => \IPS\Member::loggedIn()->language()->addToStack('event_rpm_repeats_yearly') ), FALSE, '', FALSE, array( 'daily' => array( 'repeatFreqPer_day' ), 'weekly' => array( 'elRepeatOn', 'repeatFreqPer_week' ), 'monthly' => array( 'repeatFreqPer_month' ), 'yearly' => array( 'repeatFreqPer_year' ) ), 'event_repeats' );
$return .= <<<IPSCONTENT

				</div>
			</li>
			<li class='ipsFieldRow'>
				<label class='ipsFieldRow__label' for=''>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_repeatevery', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<div class='ipsFieldRow__content'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->select( 'event_dates[event_repeat_freq]', $value['event_repeat_freq'], FALSE, array_combine( range( 1, 30 ), range( 1, 30 ) ), FALSE, '', FALSE, array(), 'event_repeat_freq' );
$return .= <<<IPSCONTENT

					<span id='repeatFreqPer_day' 
IPSCONTENT;

if ( $value['event_repeats'] and $value['event_repeats'] != 'daily' ):
$return .= <<<IPSCONTENT
style="display:none"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_repeatevery_day', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span id='repeatFreqPer_week' 
IPSCONTENT;

if ( $value['event_repeats'] != 'weekly' ):
$return .= <<<IPSCONTENT
style="display:none"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_repeatevery_week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span id='repeatFreqPer_month' 
IPSCONTENT;

if ( $value['event_repeats'] != 'monthly' ):
$return .= <<<IPSCONTENT
style="display:none"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_repeatevery_month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span id='repeatFreqPer_year' 
IPSCONTENT;

if ( $value['event_repeats'] != 'yearly' ):
$return .= <<<IPSCONTENT
style="display:none"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_repeatevery_year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</div>
			</li>
			<li class='ipsFieldRow' id='elRepeatOn'>
				<label class='ipsFieldRow__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_repeaton', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<ul class='ipsFieldRow__content ipsFieldRow__content--checkboxes'>
					
IPSCONTENT;

foreach ( \IPS\calendar\Date::getDayNames() as $day  ):
$return .= <<<IPSCONTENT

						<li>
							<input type="checkbox" role="checkbox" name="event_dates[repeat_freq_on_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day['ical'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" id="check_repeat_freq_on_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day['ical'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-iCal='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day['ical'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( ( isset( $value['repeat_freq_on_' . $day['ical'] ] ) AND $value['repeat_freq_on_' . $day['ical'] ] ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
							<label for="check_repeat_freq_on_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day['ical'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $day['abbreviated'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</li>
			<li class='ipsFieldRow'>
				<label class='ipsFieldRow__label' for=''>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_ends', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<ul class='ipsFieldRow__content ipsFieldList'>
					<li>
						<input type='radio' name='event_dates[repeat_end]' id='event_repeat_end_never' value='never'
IPSCONTENT;

if ( !$value['repeat_end_occurrences'] AND !$value['repeat_end_date'] ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
						<div class='ipsFieldList__content'>
							<label for="event_repeat_end_never">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_ends_never', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</div>
					</li>
					<li>
						<input type='radio' name='event_dates[repeat_end]' id='event_repeat_end_afterx' value='after'
IPSCONTENT;

if ( $value['repeat_end_occurrences'] ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
						<div class='ipsFieldList__content'>
							<label for="event_repeat_end_afterx">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_ends_afterx', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label> <input type='number' name='event_dates[repeat_end_occurrences]' class='ipsInput ipsInput--text cCalendar_shortInput' size='4' min='1' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['repeat_end_occurrences'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_ends_occurrencesx', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</div>
					</li>
					<li>
						<input type='radio' name='event_dates[repeat_end]' id='event_repeat_end_ondate' value='date'
IPSCONTENT;

if ( $value['repeat_end_date'] ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
						<div class='ipsFieldList__content'>
							<label for="event_repeat_end_ondate">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rpm_ends_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label> <input type='date' name='event_dates[repeat_end_date]' class='ipsInput ipsInput--text cCalendar_shortInput' size='10' value='
IPSCONTENT;

if ( $value['repeat_end_date'] instanceof \IPS\calendar\Date ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['repeat_end_date']->mysqlDatetime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['repeat_end_date'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
						</div>
					</li>
				</ul>
			</li>
			<li class='ipsSubmitRow'>
				<button type='button' class='ipsButton ipsButton--primary' role='button' data-action="updateRepeat">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ok', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</li>
		</ul>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function submitForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--event-submit" action="
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
 data-ipsForm data-ipsFormSubmit>
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

	
	<div class='ipsBox ipsPull'>
		<ul class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $inputName == 'event_dates' ):
$return .= <<<IPSCONTENT

						{$input->html()}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
		<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--event-submit'>
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $inputName != 'event_dates' ):
$return .= <<<IPSCONTENT

						{$input}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
		<div class='ipsSubmitRow'>
			<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function submitPage( $output, $pageHeader, $calendar=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $calendar and $club = $calendar->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $calendar );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( $pageHeader );
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->do ) and \IPS\Widget\Request::i()->do === 'livetopic' ):
$return .= <<<IPSCONTENT

	<div class="i-margin-block_block">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ai_service_livetopics_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


{$output}
IPSCONTENT;

		return $return;
}}