<?php
namespace IPS\Theme;
class class_calendar_front_widgets extends \IPS\Theme\Template
{	function recentReviews( $reviews, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsWidget__header'>
	<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_recentReviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$carouselID = 'widget-recent-event-reviews_' . mt_rand();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</header>
<div class='ipsWidget__content'>
	
IPSCONTENT;

if ( !empty( $reviews )  ):
$return .= <<<IPSCONTENT

		<i-data>
			<ul class='ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--widget-recent-event-reviews' 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $reviews as $review ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						
IPSCONTENT;

if ( \in_array( $layout, array("grid", "wallpaper", "featured")) ):
$return .= <<<IPSCONTENT

							<figure class="ipsData__image">
								
IPSCONTENT;

if ( $image = $review->item()->primaryImage() ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<i></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</figure>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<div class='ipsData__icon'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $review->author(), 'fluid' );
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class='ipsData__content'>
							<div class='ipsData__main'>
								<h4 class='ipsData__title'>
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($review->item()->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_event', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $review->rating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT

								</h4>
								<p class='ipsData__meta'>
									
IPSCONTENT;

$htmlsprintf = array($review->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
, <time datetime='
IPSCONTENT;

$val = ( $review->mapped('date' ) instanceof \IPS\DateTime ) ? $review->mapped('date' ) : \IPS\DateTime::ts( $review->mapped('date' ) );$return .= (string) $val;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = ( $review->mapped('date') instanceof \IPS\DateTime ) ? $review->mapped('date') : \IPS\DateTime::ts( $review->mapped('date') );$return .= (string) $val;
$return .= <<<IPSCONTENT
</time>
								</p>
								<p class='ipsData__desc'>{$review->truncated()}</p>
							</div>
						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_recent_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function upcomingEvents( $events, $today, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsWidget__header'>
	<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_upcomingEvents', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$carouselID = 'widget--upcoming-events_' . mt_rand();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</header>
<div class='ipsWidget__content'>
	
IPSCONTENT;

if ( !empty( $events )  ):
$return .= <<<IPSCONTENT

		<i-data>
			<ul class="ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--upcoming-events" 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $events as $event ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>							
						
IPSCONTENT;

if ( \in_array( $layout, array("grid", "wallpaper", "featured")) ):
$return .= <<<IPSCONTENT

							<figure class="ipsData__image">
								
IPSCONTENT;

if ( $image = $event->primaryImage() ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<i></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</figure>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $event->nextOccurrence( $today, 'startDate' ) ):
$return .= <<<IPSCONTENT

								<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $today, 'startDate' )->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
									<span class='ipsCalendarDate__month' data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $today, 'startDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="%b">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $today, 'startDate' )->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									<span class='ipsCalendarDate__date' data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $today, 'startDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="%d">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $today, 'startDate' )->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</time>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
									<span class='ipsCalendarDate__month' data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="%b">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									<span class='ipsCalendarDate__date' data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="%d">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</time>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class="ipsData__content">
							<div class='ipsData__main'>
								<h4 class='ipsData__title'>
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($event->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_event', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
								</h4>
								<div class='ipsData__desc'>
									{$event->truncated()}
								</div>
								<div class='ipsData__meta i-color_root i-margin-block_1'>
									
IPSCONTENT;

if ( $event->nextOccurrence( $today, 'startDate' ) ):
$return .= <<<IPSCONTENT

										<strong class="i-font-weight_600" data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $today, 'startDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::calendarDateFormat(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::localeTimeFormat( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $today, 'startDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $today, 'startDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</strong>
										
IPSCONTENT;

if ( $event->nextOccurrence( $event->nextOccurrence( $today, 'startDate' ) ?: $today, 'endDate' )  ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$sameDay = (bool) ( $event->nextOccurrence( $event->nextOccurrence( $today, 'startDate' ) ?: $today, 'endDate' ) and ( $event->nextOccurrence( $today, 'startDate' ) and $event->nextOccurrence( $today, 'startDate' )->calendarDate() == $event->nextOccurrence( $event->nextOccurrence( $today, 'startDate' ) ?: $today, 'endDate' )->calendarDate() ) );
$return .= <<<IPSCONTENT

											<span class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'until', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
											
IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT

												<strong class="i-font-weight_600" data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $event->nextOccurrence( $today, 'startDate' ) ?: $today, 'endDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::calendarDateFormat(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::localeTimeFormat( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
													
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $event->nextOccurrence( $today, 'startDate' ) ?: $today, 'endDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

													
IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $event->nextOccurrence( $today, 'startDate' ) ?: $today, 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												</strong>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT

													<strong class="i-font-weight_600" data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $event->nextOccurrence( $today, 'startDate' ) ?: $today, 'endDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::localeTimeFormat( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
														
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $event->nextOccurrence( $today, 'startDate' ) ?: $today, 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

													</strong>
												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<strong class="i-font-weight_600" data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::calendarDateFormat(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::localeTimeFormat( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 </strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $event->lastOccurrence( 'endDate' )  ):
$return .= <<<IPSCONTENT

											<span class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'until', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
											<strong class="i-font-weight_600" data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::calendarDateFormat(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::localeTimeFormat( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</strong>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</div>
							
IPSCONTENT;

if ( $event->container()->allow_comments ):
$return .= <<<IPSCONTENT

								<ul class="ipsData__stats">
									<li>
IPSCONTENT;

$pluralize = array( $event->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
								</ul>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_upcoming_events', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}}