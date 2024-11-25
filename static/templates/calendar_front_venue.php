<?php
namespace IPS\Theme;
class class_calendar_front_venue extends \IPS\Theme\Template
{	function upcomingStream( $date, $events, $venue ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsPager cCalendarNav i-margin-bottom_3 i-position_sticky-top'>
	<div class='ipsPager_prev'>
		<a href="
IPSCONTENT;

if ( $venue ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=venue&id={$venue->_id}&y={$date->lastMonth('year')}&m={$date->lastMonth('mon')}", null, "calendar_venue", array( $venue->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=venue&y={$date->lastMonth('year')}&m={$date->lastMonth('mon')}", null, "calendar_venue", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($date->lastMonth('monthName'), $date->lastMonth('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_stream_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
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
		<h1 class='ipsTitle ipsTitle--h3 i-text-align_center'>
IPSCONTENT;

$sprintf = array($date->monthName, $date->year); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>

	</div>
	<div class='ipsPager_next'>
		<a href="
IPSCONTENT;

if ( $venue ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=venue&id={$venue->_id}&y={$date->nextMonth('year')}&m={$date->nextMonth('mon')}", null, "calendar_venue", array( $venue->title_seo ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=venue&y={$date->nextMonth('year')}&m={$date->nextMonth('mon')}", null, "calendar_venue", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($date->nextMonth('monthName'), $date->nextMonth('year')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cal_month_stream_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
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

<section class='i-background_3 i-padding_3' id='venueStream'>
	
IPSCONTENT;

if ( \count($events)  ):
$return .= <<<IPSCONTENT

		<div>
			<div>
				
IPSCONTENT;

foreach ( $events as $event ):
$return .= <<<IPSCONTENT

					<div>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "calendar" )->eventStreamBlock( $event, $date, TRUE, array( 240, 185 ), TRUE );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_events_month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</section>
IPSCONTENT;

		return $return;
}

	function view( $venue, $upcoming=array(), $past=array(), $address=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	<section>
			<div class='ipsColumns'>
				<aside class='ipsColumns__secondary i-basis_280'>
					{$venue->map( 270, 270 )}
					<div class='i-background_2'>
						
IPSCONTENT;

if ( $address ):
$return .= <<<IPSCONTENT

						<div class='i-padding_2 i-background_3'>
							<h3 class='ipsMinorTitle'>
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'venue_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</h3>
							{$address}
						</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

					<ul class="ipsButtons ipsButtons--main">
						<li>
							<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&venue={$venue->id}", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsdialog-size="narrow" rel='nofollow noindex'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</aside>
				<div class='ipsColumns__primary'>
					<div class='ipsBox i-padding_3'>
						
IPSCONTENT;

if ( $venue->description ):
$return .= <<<IPSCONTENT

						<h2 class='ipsTitle ipsTitle--h3 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'venue_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>

						<div>
							<div class='ipsRichText'>
								{$venue->description}
							</div>
						</div>
						<br>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<h2 class='ipsTitle ipsTitle--h3 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'venue_upcoming_events', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						<div data-controller='calendar.front.venue.main'>
								
IPSCONTENT;

if ( $upcoming ):
$return .= <<<IPSCONTENT

									{$upcoming}
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_upcoming_events', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
			</div>

	</section>
</div>
IPSCONTENT;

		return $return;
}}