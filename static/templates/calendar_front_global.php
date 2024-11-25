<?php
namespace IPS\Theme;
class class_calendar_front_global extends \IPS\Theme\Template
{	function commentTableHeader( $comment, $event ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-flex i-align-items_center i-gap_2'>
	
IPSCONTENT;

$date = ( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) == NULL ) ? $event->lastOccurrence() : $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' );
$return .= <<<IPSCONTENT

	<div class='i-flex_00 i-basis_50'>
		<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $date->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
			<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $date->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $date->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		</time>
	</div>
	<div class='i-flex_11'>
		<h3 class='ipsTitle ipsTitle--h3'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'title='
IPSCONTENT;

$sprintf = array($event->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_event', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
		<div class='i-color_soft i-link-color_inherit i-flex i-align-items_center i-flex-wrap_wrap i-gap_2'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

if ( $event->container()->allow_comments ):
$return .= <<<IPSCONTENT

				<span><i class='fa-regular fa-comment i-color_soft i-margin-start_2'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $event->container()->allow_reviews ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $event->rating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT
 &nbsp;&nbsp;
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedEvent( $item, $url, $image=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--event'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $item, $item->mapped('title'), $item->mapped('date'), $url );
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $item->map( 500, 270 ) ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead ipsRichEmbed_masthead--map'>
			{$item->map( 500, 270 )}
		</a>
	
IPSCONTENT;

elseif ( $item->coverPhoto() && $item->coverPhoto()->file ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$photo = $item->coverPhoto()->file;
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
			<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photo->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading="lazy">
		</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsColumns'>
			<span class='ipsColumns__secondary i-basis_50'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

$nextOccurrence = $item->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: $item->lastOccurrence( 'startDate' );
$return .= <<<IPSCONTENT

					<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
						<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</time>
				</a>
			</span>
			<div class="ipsColumns__primary">
				<div class="i-margin-bottom_2">
					
IPSCONTENT;

if ( $item->_end_date ):
$return .= <<<IPSCONTENT

						<dl class='cCalendarEmbed_dates'>
							<dt>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</dt>
							<dd>
								<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</time>
							</dd>
							<dt>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'until', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</dt>
							<dd>
								<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

$sameDay = !( ($item->_start_date->mday != $item->_end_date->mday) or ($item->_start_date->mon != $item->_end_date->mon) or ($item->_start_date->year != $item->_end_date->year) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</time>
							</dd>
						</dl>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<p>
							<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</time>
						</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( $desc = $item->truncated(TRUE) ):
$return .= <<<IPSCONTENT

					<div class='ipsRichEmbed__snippet'>
						{$desc}
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>

		
IPSCONTENT;

if ( $item->rsvp ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$attendees = $item->attendees();
$return .= <<<IPSCONTENT

			<div class='i-flex i-align-items_center i-justify-content_space-between i-font-size_-1 i-font-weight_500'>
				
IPSCONTENT;

if ( isset( $attendees[1][ \IPS\Member::loggedIn()->member_id ] ) ):
$return .= <<<IPSCONTENT

					<div class='i-color_positive'>
						<i class='fa-solid fa-check-circle'></i>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_are_going', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

elseif ( isset( $attendees[0][ \IPS\Member::loggedIn()->member_id ] ) ):
$return .= <<<IPSCONTENT

					<div class='i-color_negative'>
						<i class='fa-solid fa-circle-xmark'></i>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_arent_going', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

elseif ( $item->can('rsvp') && !( $item->rsvp_limit > 0 AND \count($attendees[1]) >= $item->rsvp_limit ) ):
$return .= <<<IPSCONTENT

					<ul class='ipsList ipsList--inline i-gap_1'>
						<li>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('rsvp')->setQueryString( 'action', 'yes' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_attend_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
						
IPSCONTENT;

if ( $item->rsvp_limit == -1 ):
$return .= <<<IPSCONTENT

							<li>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('rsvp')->setQueryString( 'action', 'maybe' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_maybe_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<li>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('rsvp')->setQueryString( 'action', 'no' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_notgoing_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				<div class='i-margin-start_auto 
IPSCONTENT;

if ( !\count($attendees[1]) ):
$return .= <<<IPSCONTENT
i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					<i class='fa-solid fa-user'></i> 
IPSCONTENT;

$pluralize = array( \count($attendees[1]) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_users_going', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedItemStats( $item );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedEventComment( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--event-comment'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			<div>
				
IPSCONTENT;

if ( $item->map( 500, 100 ) ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead ipsRichEmbed_masthead--map'>
						{$item->map( 500, 100 )}
					</a>
				
IPSCONTENT;

elseif ( $item->coverPhoto() && $item->coverPhoto()->file ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$photo = $item->coverPhoto()->file;
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead ipsRichEmbed_masthead_small'>
						<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photo->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading="lazy">
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='i-padding_3'>
					<div class='ipsPhotoPanel'>
						<span class='cCalendarEmbed_calendar'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;

$nextOccurrence = $item->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: $item->lastOccurrence( 'startDate' );
$return .= <<<IPSCONTENT

								<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
									<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</time>
							</a>
						</span>
						<div class='ipsPhotoPanel__text'>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item, FALSE, \IPS\Theme::i()->getTemplate( 'global', 'calendar' )->embedEventItemSnippet( $item ) );
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
			</div>
		</div>

		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled AND \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) AND \count( $comment->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline i-margin-top_2'>
				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $comment, TRUE, 'small' );
$return .= <<<IPSCONTENT

				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedEventItemSnippet( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $item->_end_date ):
$return .= <<<IPSCONTENT

	<dl class='cCalendarEmbed_dates'>
		<dt>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</dt>
		<dd>
			<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</time>
		</dd>
		<dt>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'until', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</dt>
		<dd>
			<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

$sameDay = !( ($item->_start_date->mday != $item->_end_date->mday) or ($item->_start_date->mon != $item->_end_date->mon) or ($item->_start_date->year != $item->_end_date->year) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</time>
		</dd>
	</dl>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p>
		<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</time>
	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function embedEventReview( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--event-review'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			<div>
				
IPSCONTENT;

if ( $item->map( 500, 100 ) ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead ipsRichEmbed_masthead--map'>
						{$item->map( 500, 100 )}
					</a>
				
IPSCONTENT;

elseif ( $item->coverPhoto() && $item->coverPhoto()->file ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$photo = $item->coverPhoto()->file;
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead ipsRichEmbed_masthead_small'>
						<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photo->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading="lazy">
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='i-padding_3'>
					<div class='ipsPhotoPanel'>
						<span class='cCalendarEmbed_calendar'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;

$nextOccurrence = $item->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: $item->lastOccurrence( 'startDate' );
$return .= <<<IPSCONTENT

								<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
									<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</time>
							</a>
						</span>
						<div class='ipsPhotoPanel__text'>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item, FALSE, \IPS\Theme::i()->getTemplate( 'global', 'calendar' )->embedEventItemSnippet( $item ) );
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
			</div>
		</div>

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'veryLarge', $comment->mapped('rating') );
$return .= <<<IPSCONTENT
 
		
IPSCONTENT;

if ( $comment->mapped('votes_total') ):
$return .= <<<IPSCONTENT

			<p>{$comment->helpfulLine()}</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled AND \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) AND \count( $comment->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline i-margin-top_2'>
				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $comment, TRUE, 'small' );
$return .= <<<IPSCONTENT

				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function manageFollowRow( $table, $headers, $rows, $includeFirstCommentInCommentCount=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

		<li class="ipsData__item 
IPSCONTENT;

if ( method_exists( $row, 'tableClass' ) && $row->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $row->unread() ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-controller='core.front.system.manageFollowed' data-followID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_area'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_rel_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

if ( $row->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ):
$return .= <<<IPSCONTENT

				<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
					<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</time>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->lastOccurrence( 'startDate' )->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
					<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->lastOccurrence( 'startDate' )->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->lastOccurrence( 'startDate' )->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</time>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsData__content'>
				<div class='ipsData__main'>
					<div class='ipsData__title'>
						<div class="ipsBadges">
						    
IPSCONTENT;

foreach ( $row->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $row->prefix() ):
$return .= <<<IPSCONTENT

								<span>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $row->prefix( TRUE ), $row->prefix() );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<h4>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $row->mapped('title') ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
						</h4>
					</div>
					
IPSCONTENT;

if ( method_exists( $row, 'tableDescription' ) ):
$return .= <<<IPSCONTENT

						<div class='ipsData__desc ipsTruncate_2'>{$row->tableDescription()}</div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class="ipsData__meta">
	                        
IPSCONTENT;

$htmlsprintf = array($row->author()->link( $row->warningRef() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $row->__get( $row::$databaseColumnMap['date'] ) instanceof \IPS\DateTime ) ? $row->__get( $row::$databaseColumnMap['date'] ) : \IPS\DateTime::ts( $row->__get( $row::$databaseColumnMap['date'] ) );$return .= $val->html();
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>

				<!-- v5 todo: The anon badge isn't great on mobile but I can't be bothered fixing it atm -->
				
IPSCONTENT;

if ( $row->_followData['follow_is_anon'] ):
$return .= <<<IPSCONTENT

					<div class='cFollowedContent_anon'>
						<span class='ipsBadge ipsBadge--icon' data-role='followAnonymous' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_is_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				<ul class='ipsData__followedInfo'>
					<li class='i-color_hard i-font-weight_500' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_when', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='followDate'><i class='fa-regular fa-clock'></i> 
IPSCONTENT;

$val = ( $row->_followData['follow_added'] instanceof \IPS\DateTime ) ? $row->_followData['follow_added'] : \IPS\DateTime::ts( $row->_followData['follow_added'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					<li class='i-color_soft' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_how', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='followFrequency'>
						
IPSCONTENT;

if ( $row->_followData['follow_notify_freq'] == 'none' ):
$return .= <<<IPSCONTENT

							<i class='fa-regular fa-bell-slash'></i>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class='fa-solid fa-bell'></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "follow_freq_{$row->_followData['follow_notify_freq']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</li>
				</ul>
				<div class='cFollowedContent_manage'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->manageFollow( $row->_followData['follow_app'], $row->_followData['follow_area'], $row->_followData['follow_rel_id'] );
$return .= <<<IPSCONTENT

				</div>
			</div>
			
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

				<div class='ipsData__mod'>
					<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( $row->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class="ipsInput ipsInput--toggle">
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

	function rows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		<li class="ipsData__item 
IPSCONTENT;

if ( method_exists( $row, 'tableClass' ) && $row->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

if ( $row->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ):
$return .= <<<IPSCONTENT

				<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate ipsCalendarDate--large'>
					<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</time>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->lastOccurrence( 'startDate' )->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate ipsCalendarDate--large'>
					<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->lastOccurrence( 'startDate' )->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->lastOccurrence( 'startDate' )->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</time>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsData__content'>
				<div class='ipsData__main'>
					<div class='ipsData__title'>
						
IPSCONTENT;

foreach ( $row->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $row->prefix() ):
$return .= <<<IPSCONTENT

							<span>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $row->prefix( TRUE ), $row->prefix() );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<h4>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $row->tableHoverUrl ):
$return .= <<<IPSCONTENT
data-ipsHover
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								
IPSCONTENT;

if ( $row->mapped('title') ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</a>
						</h4>
					</div>
					<div class='ipsData__desc'>{$row->eventBlurb()}</div>
					
IPSCONTENT;

if ( $row->rsvp ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $attendees = $row->attendees() and isset( $attendees[1][ \IPS\Member::loggedIn()->member_id ] )  ):
$return .= <<<IPSCONTENT

							<p class='i-font-size_2 i-color_positive'><strong><i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_are_going_to_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></p>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<p class='i-font-size_2 i-color_soft'><em><i class='fa-solid fa-circle-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_are_not_going_to_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em></p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


						
IPSCONTENT;

if ( \count( $attendees[1] ) ):
$return .= <<<IPSCONTENT

							<ul class='ipsList ipsList--inline'>
								<li class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_attend_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</li>
								
IPSCONTENT;

foreach ( $attendees[1] as $idx => $attendee ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $idx < 6 ):
$return .= <<<IPSCONTENT

										<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $attendee, 'tiny', NULL, NULL, TRUE );
$return .= <<<IPSCONTENT
</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \count( $attendees[1] ) > 5 ):
$return .= <<<IPSCONTENT

									<li class='i-color_soft'>
IPSCONTENT;

$pluralize = array( \count( $attendees[1] ) - 5 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \count( $row->tags() ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $row->tags(), true, true );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class='i-basis_220'>
					
IPSCONTENT;

if ( $row->container()->allow_reviews ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $row->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $row->memberReviewRating() );
$return .= <<<IPSCONTENT
 <span class='i-color_soft'>(
IPSCONTENT;

$pluralize = array( $row->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $row->container()->allow_comments ):
$return .= <<<IPSCONTENT

						<p>
							
IPSCONTENT;

if ( $row->comments ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url()->setQueryString( 'tab', 'comments' )->setFragment('replies'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<i class='fa-solid fa-comment'></i> 
IPSCONTENT;

$pluralize = array( $row->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $row->comments ):
$return .= <<<IPSCONTENT

								</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
			
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

				<div class='ipsData__mod'>
					
IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

					<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( $row->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class="ipsInput ipsInput--toggle">
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

	function searchResultCommentSnippet( $indexData, $nextOccurance, $startDate, $endDate, $allDay, $reviewRating, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--events'>
	<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurance->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
		<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurance->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurance->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	</time>
</div>

IPSCONTENT;

if ( !$condensed ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-content ipsStreamItem__content-thumb--events'>
		
IPSCONTENT;

if ( $reviewRating !== NULL ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'medium', $reviewRating );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsStream__comment'>
			<p class="i-font-size_2 i-font-weight_600">
				<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$allDay ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</time>
				
IPSCONTENT;

if ( $endDate ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'until_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$sameDay = !( ($startDate->mday != $endDate->mday) or ($startDate->mon != $endDate->mon) or ($startDate->year != $endDate->year) );
$return .= <<<IPSCONTENT

					<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$allDay ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</time>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>			
			
IPSCONTENT;

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

				<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_3'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class='ipsRichText' data-searchable data-findTerm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

$return .= \IPS\Content\Search\Result::preDisplay( $indexData['index_content'] );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function searchResultEventSnippet( $indexData, $itemData, $nextOccurance, $startDate, $endDate, $allDay, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--events'>
	<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurance->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
		<span class='ipsCalendarDate__month' data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurance->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="%h" >
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurance->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		<span class='ipsCalendarDate__date' data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurance->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="%d">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurance->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	</time>
</div>

IPSCONTENT;

if ( !$condensed ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-content ipsStreamItem__content-thumb--events'>
		<p class="i-font-size_2 i-font-weight_600">
			<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$allDay ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</time>
			
IPSCONTENT;

if ( $endDate ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'until_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$sameDay = !( ($startDate->mday != $endDate->mday) or ($startDate->mon != $endDate->mon) or ($startDate->year != $endDate->year) );
$return .= <<<IPSCONTENT

				<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$allDay ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</time>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
		
IPSCONTENT;

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

			<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_2'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class='ipsRichText' data-searchable data-findTerm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

$return .= \IPS\Content\Search\Result::preDisplay( $indexData['index_content'] );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function subscribeMenu( $thisCalendar, $downloadLinks ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $downloadLinks ):
$return .= <<<IPSCONTENT

    <ul class='ipsMenu ipsMenu_auto ipsHide' id='elCalendarSettings_menu'>
        <li class='ipsMenu_title'>
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($thisCalendar->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'with_calendar', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'with_all_calendars', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</li>
        <li class='ipsMenu_item'><a href='
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $downloadLinks['iCalCalendar'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $downloadLinks['iCalAll'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-download'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_webcal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
        <li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( 'http://', 'https://' ), 'webcal://', $thisCalendar ? $downloadLinks['iCalCalendar'] : $downloadLinks['iCalAll'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-calendar'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subscribe_webcal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
    </ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}