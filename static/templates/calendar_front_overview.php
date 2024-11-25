<?php
namespace IPS\Theme;
class class_calendar_front_overview extends \IPS\Theme\Template
{	function byMonth( $calendars, $date, $featured, $events, $thisCalendar, $months=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='ipsBox' data-controller="calendar.front.overview.eventList">
	<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_browse_by_month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='cEvents__monthNav' data-role='monthNav'>
		
IPSCONTENT;

foreach ( $months as $monthObj  ):
$return .= <<<IPSCONTENT

			<a class='cEvents__monthNav__monthItem 
IPSCONTENT;

if ( $monthObj->format( 'n' ) == $date->mon ):
$return .= <<<IPSCONTENT
cEvents__monthNav__monthItem--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&id={$thisCalendar->_id}&y={$monthObj->format( 'Y' )}&m={$monthObj->format( 'n' )}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&y={$monthObj->format( 'Y' )}&m={$monthObj->format( 'n' )}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" rel='nofollow' data-action='changeMonth' data-month='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $monthObj->format( 'n' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-year='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $monthObj->format( 'Y' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<span class='cEvents__monthNav__month'>
IPSCONTENT;

$pluralize = array( $monthObj->format( 'n' ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( '_date_month_short', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
				<span class='cEvents__monthNav__year'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $monthObj->format( 'Y' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</a>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( \count($events)  ):
$return .= <<<IPSCONTENT

		<i-data>
			<ul class='ipsEventList ipsData ipsData--grid ipsData--events-list' data-role='eventList'>
				
IPSCONTENT;

foreach ( $events as $event ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "events", "calendar" )->event( $event, 'normal', \IPS\calendar\Date::getDate( $date->firstDayOfMonth('year'), $date->firstDayOfMonth('mon'), $date->firstDayOfMonth('mday') ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
		<div class='i-padding_2 i-text-align_center i-border-top_3'>
			<button class='ipsButton ipsButton--secondary' 
IPSCONTENT;

if ( \count( $events ) < 16 ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-action='loadMore'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_show_more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			<p class='ipsEmptyMessage i-padding_0' 
IPSCONTENT;

if ( \count( $events ) >= 16 ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role='noMoreResults'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_no_more_this_month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<i-data>
			<ul class='ipsEventList ipsData ipsData--grid ipsData--events-list' data-role='eventList'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "events", "calendar" )->noEvents(  );
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</section>
IPSCONTENT;

		return $return;
}

	function header( $date, $thisCalendar=NULL, $downloadLinks=NULL, $showTitle=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<header class="ipsPageHeader ipsPageHeader--calendar">
    <div class="ipsPageHeader__row">
        
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "header:before", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "header:inside-start", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $showTitle ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "title:before", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "title:inside-start", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'frontnavigation_calendar', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "title:inside-end", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "title:after", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "header:inside-end", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "header:after", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "buttons:before", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="buttons" class="ipsButtons ipsButtons--main">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "buttons:inside-start", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT

            <li>
                
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'cloud', 'livetopics' ) ) and \IPS\cloud\LiveTopic::canCreate( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

                    <a href="#elEventCreate_menu" id="elEventCreate" class="ipsButton ipsButton--primary" data-ipsmenu><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'livetopic_create_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
                    <ul id="elEventCreate_menu" class="ipsMenu ipsMenu_normal ipsHide">
                        <li class="ipsMenu_item" data-menuitem="livetopic">
                            
IPSCONTENT;

if ( !\IPS\Settings::i()->club_nodes_in_apps and $theOnlyNode = \IPS\calendar\Calendar::theOnlyNode()  ):
$return .= <<<IPSCONTENT

                            <a class="ipsMenu_link" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&do=livetopic&id={$theOnlyNode->_id}", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_create_livetopic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                            
IPSCONTENT;

elseif ( ( $thisCalendar AND $thisCalendar->can('add') ) ):
$return .= <<<IPSCONTENT

                            <a class="ipsMenu_link" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&do=livetopic&id={$thisCalendar->_id}", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_create_livetopic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                            
IPSCONTENT;

elseif ( \IPS\calendar\Calendar::canOnAny('add') ):
$return .= <<<IPSCONTENT

                            <a class="ipsMenu_link" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&do=livetopic", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_create_livetopic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        </li>
                        <li class="ipsMenu_item" data-menuitem="event">
                            
IPSCONTENT;

if ( !\IPS\Settings::i()->club_nodes_in_apps and $theOnlyNode = \IPS\calendar\Calendar::theOnlyNode()  ):
$return .= <<<IPSCONTENT

                            <a class="ipsMenu_link" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&do=submit&id={$theOnlyNode->_id}", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_create_normal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                            
IPSCONTENT;

elseif ( ( $thisCalendar AND $thisCalendar->can('add') ) ):
$return .= <<<IPSCONTENT

                            <a class="ipsMenu_link" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&do=submit&id={$thisCalendar->_id}", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_create_normal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                            
IPSCONTENT;

elseif ( \IPS\calendar\Calendar::canOnAny('add') ):
$return .= <<<IPSCONTENT

                            <a class="ipsMenu_link" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog="" data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_calendar', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_create_normal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        </li>
                    </ul>
                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( !\IPS\Settings::i()->club_nodes_in_apps and $theOnlyNode = \IPS\calendar\Calendar::theOnlyNode()  ):
$return .= <<<IPSCONTENT

                        <a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&do=submit&id={$theOnlyNode->_id}", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                    
IPSCONTENT;

elseif ( ( $thisCalendar AND $thisCalendar->can('add') ) ):
$return .= <<<IPSCONTENT

                        <a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&do=submit&id={$thisCalendar->_id}", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                    
IPSCONTENT;

elseif ( \IPS\calendar\Calendar::canOnAny('add') ):
$return .= <<<IPSCONTENT

                        <a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit", null, "calendar_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog="" data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_calendar', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </li>
            
IPSCONTENT;

if ( \IPS\Widget\Request::i()->view=='overview' ):
$return .= <<<IPSCONTENT

                <li>
                    <a href="#elCalendarSettings_menu" id="elCalendarSettings" data-ipsmenu data-ipsmenu-aligncenter class="ipsButton ipsButton--inherit">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subscribe_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<i class="fa-solid fa-caret-down"></i></a>
                </li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            <li>
                <ul class="ipsButtonGroup">
                    
IPSCONTENT;

if ( !$thisCalendar or !(\IPS\IPS::classUsesTrait( $thisCalendar, 'IPS\Content\ClubContainer' ) AND $thisCalendar->club() ) ):
$return .= <<<IPSCONTENT

                    <li>
                        <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=overview", null, "calendar", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->view=='overview' ):
$return .= <<<IPSCONTENT
 ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipstooltip="" data-ipstooltip-safe="" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_view_overview', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow">
                            <i class="fa-solid fa-align-justify"></i>
                        </a>
                    </li>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    <li>
                        <a href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=month&y={$date->year}&m={$date->mon}&id={$thisCalendar->_id}", null, "calendar_month", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=month&y={$date->year}&m={$date->mon}", null, "calendar_month", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->view=='month' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipstooltip="" data-ipstooltip-safe="" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_view_monthly', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow">
                            <i class="fa-solid fa-calendar"></i>
                        </a>
                    </li>
                    <li>
                        <a href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=week&w={$date->year}-{$date->mon}-{$date->mday}&id={$thisCalendar->_id}", null, "calendar_week", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=week&w={$date->year}-{$date->mon}-{$date->mday}", null, "calendar_week", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->view=='week' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipstooltip="" data-ipstooltip-safe="" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_view_weekly', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow">
                            <i class="fa-regular fa-calendar-minus"></i>
                        </a>
                    </li>
                    <li>
                        <a href="
IPSCONTENT;

if ( $thisCalendar ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=day&y={$date->year}&m={$date->mon}&d={$date->mday}&id={$thisCalendar->_id}", null, "calendar_day", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=day&y={$date->year}&m={$date->mon}&d={$date->mday}", null, "calendar_day", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->view=='day' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipstooltip="" data-ipstooltip-safe="" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_view_daily', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow">
                            <i class="fa-regular fa-calendar"></i>
                        </a>
                    </li>
                </ul>
            </li>
        
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "buttons:inside-end", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/overview/header", "buttons:after", [ $date,$thisCalendar,$downloadLinks,$showTitle ] );
$return .= <<<IPSCONTENT

    </div>
</header>

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "calendar" )->subscribeMenu( $thisCalendar, $downloadLinks );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function search( $searchForm, $results=array(), $mapMarkers=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


{$searchForm->customTemplate( array( \IPS\Theme::i()->getTemplate( 'overview', 'calendar' ), 'searchBar' ) )}

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "events", "calendar" )->searchResultsWrapper( $results, $mapMarkers );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function searchBar( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar='', $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 i-flex_11" action="
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
 data-ipsForm data-controller='calendar.front.overview.search'
IPSCONTENT;

if ( \IPS\GeoLocation::enabled() ):
$return .= <<<IPSCONTENT
 data-placeholder='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['location']->options['placeholder'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \is_array($v) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
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

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

$byLocation = FALSE;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->lat ) && isset( \IPS\Widget\Request::i()->lon ) ):
$return .= <<<IPSCONTENT

	<input type='hidden' name='lat' value='
IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->lat ) ? htmlspecialchars( \IPS\Widget\Request::i()->lat, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT
'>
	<input type='hidden' name='lon' value='
IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->lon ) ? htmlspecialchars( \IPS\Widget\Request::i()->lon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->searchNearLocation ) ):
$return .= <<<IPSCONTENT

	<input type='hidden' name='searchNearLocation' value='1'>
	
IPSCONTENT;

$byLocation = TRUE;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<ul class='cEvents__search'>
		
IPSCONTENT;

foreach ( $elements[''] as $key => $input ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $key === 'location' ):
$return .= <<<IPSCONTENT

		<li class='cEvents__search__field cEvents__search__field--location'>
			<div class="i-flex i-justify-content_space-between">
				<label class='cEvents__search__label' for="elInput_location">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<div class='i-flex i-gap_1'>
					
IPSCONTENT;

if ( $byLocation ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$input->options['placeholder'] = \IPS\Member::loggedIn()->language()->addToStack( "events_search_your_location" );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$input->options['disabled'] = TRUE;
$return .= <<<IPSCONTENT

						<button type="button" class='ipsEventSearch__searchLocation' hidden data-action='useMyLocation'><i class='fa-solid fa-crosshairs'></i> <span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_use_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
						<button type="button" class='ipsEventSearch__searchLocation' data-action='cancelLocation'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_cancel_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<button type="button" class='ipsEventSearch__searchLocation' data-action='useMyLocation'><i class='fa-solid fa-crosshairs'></i> <span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_use_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
						<button type="button" class='ipsEventSearch__searchLocation' hidden data-action='cancelLocation'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_cancel_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
			{$input->html()}
		</li>
		
IPSCONTENT;

elseif ( $key === 'show' ):
$return .= <<<IPSCONTENT

		<li class='cEvents__search__field cEvents__search__field--show'>
			<label class="cEvents__search__input">
				<span class='cEvents__search__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				{$input->html()}
			</label>
		</li>
		
IPSCONTENT;

elseif ( $key === 'date' ):
$return .= <<<IPSCONTENT

		<li class='cEvents__search__field cEvents__search__field--date cEvents__search__field--from'>
			<label class="cEvents__search__input">
				<span class='cEvents__search__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_from', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				{$input->start->html()}
			</label>
		</li>
		<li class='cEvents__search__field cEvents__search__field--date cEvents__search__field--to'>
			<label class="cEvents__search__input">
				<span class='cEvents__search__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				{$input->end->html()}
			</label>
		</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		<li class='cEvents__search__field cEvents__search__field--submit'>
			<button type='submit' class='ipsButton ipsButton--large ipsButton--primary i-width_100p'><i class="fa-solid fa-magnifying-glass"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
		</li>
	</ul>
</form>
IPSCONTENT;

		return $return;
}

	function wrapper( $featured, $nearme, $stream, $searchForm, $mapMarkers, $online, $date, $downloadLinks ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-role='eventsPage' class='ipsBlockSpacer'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "overview", "calendar" )->header( $date, NULL, $downloadLinks );
$return .= <<<IPSCONTENT


	<div class='cEvents__overview-header'>
		
IPSCONTENT;

if ( \count( $featured ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "events", "calendar" )->featured( $featured );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<section class='ipsBox 
IPSCONTENT;

if ( \count( $featured ) ):
$return .= <<<IPSCONTENT
 i-flex i-flex-direction_column
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
			<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_search_events', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<div class='ipsBox__content ipsBox__padding 
IPSCONTENT;

if ( \count( $featured ) ):
$return .= <<<IPSCONTENT
i-flex_11 i-flex i-align-items_center
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				<div class='i-flex_11 ipsEventSearch 
IPSCONTENT;

if ( \count( $featured ) ):
$return .= <<<IPSCONTENT
ipsEventSearch--vertical
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsEventSearch--horizontal
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					{$searchForm->customTemplate( array( \IPS\Theme::i()->getTemplate( 'overview', 'calendar' ), 'searchBar' ) )}
				</div>
			</div>
		</section>
	</div>

	<div data-role='eventsOverview' class='ipsBlockSpacer'>
        
IPSCONTENT;

if ( \IPS\GeoLocation::enabled()  ):
$return .= <<<IPSCONTENT

		    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "events", "calendar" )->nearMe( $nearme, $mapMarkers );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "events", "calendar" )->online( $online );
$return .= <<<IPSCONTENT


		{$stream}
	</div>

	<div data-role='searchResultsWrapper' class='ipsHide'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "events", "calendar" )->searchResultsWrapper(  );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}}