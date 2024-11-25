<?php
namespace IPS\Theme;
class class_calendar_front_modcp extends \IPS\Theme\Template
{	function approvalQueueItem( $item, $ref, $container, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elApprovePanel" class='ipsBox'>
	<article class="">
		<div class='i-flex i-flex-wrap_wrap i-gap_2 i-padding_3'>
			<div class="ipsPhotoPanel"> 
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userPhoto( $item->author() );
$return .= <<<IPSCONTENT

				<div class='ipsPhotoPanel__text'>
					<a href="#user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsTitle ipsTitle--h3" data-ipsmenu="">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &nbsp;<i class="fa-solid fa-caret-down"></i></a>
					<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('mod_can_warn') ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=warn&id={$item->author()->member_id}&ref={$ref}", null, "warn_add", array( $item->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$sprintf = array($item->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_member', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" data-ipsDialog-remoteSubmit data-ipsDialog-flashMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_issued', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-role="warnUserDialog">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $item->author()->member_id != \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $item->author()->members_bitoptions['bw_is_spammer'] ):
$return .= <<<IPSCONTENT

								<li class='ipsMenu_item' data-ipsMenuValue='spamFlagButton'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$item->author()->member_id}&s=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $item->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<li class='ipsMenu_item' data-ipsMenuValue='spamFlagButton'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$item->author()->member_id}&s=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $item->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_flag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<li class="ipsMenu_item"><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$item->author()->member_id}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-remoteSubmit data-ipsDialog-flashMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_sent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					</ul>
					
IPSCONTENT;

if ( $container ):
$return .= <<<IPSCONTENT

						<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posted_in_container', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<p class="i-color_soft">
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $item->mapped('date') )->html(FALSE)); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'date_replied', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
				
					<p>
						<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $item->_start_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

if ( $item->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->lastOccurrence( 'startDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->lastOccurrence( 'startDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</time>
						
IPSCONTENT;

if ( $item->_end_date ):
$return .= <<<IPSCONTENT

							&nbsp;&nbsp;<i class='fa-solid fa-circle-arrow-right i-font-size_2 i-color_soft'></i>&nbsp;&nbsp;
							<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $item->_end_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

if ( $item->nextOccurrence( $item->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: \IPS\calendar\Date::getDate(), 'endDate' ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->nextOccurrence( $item->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: \IPS\calendar\Date::getDate(), 'endDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->nextOccurrence( $item->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: \IPS\calendar\Date::getDate(), 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->lastOccurrence( 'endDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$item->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->lastOccurrence( 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</time>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
			<p class="i-margin-start_auto">
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_soft'>
					<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$val = "{$item::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</a>
			</p>
		</div>
		<div class='i-background_2 i-padding_3'>
			<div class='ipsBox'>
				<h2 class="ipsBox__header"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $item->content(), array('ipsPost', 'i-padding_3') );
$return .= <<<IPSCONTENT

			</div>
		</div>
	</article>
</div>

IPSCONTENT;

if ( $reason = $item->approvalQueueReason() ):
$return .= <<<IPSCONTENT

	<div class='ipsBox'>
		<div class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reason_for_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		<div class='i-background_2 i-border-bottom_3 i-padding_2'>
			<p class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reason, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}