<?php
namespace IPS\Theme;
class class_core_front_modcp extends \IPS\Theme\Template
{	function alertRow( $table, $headers, $rows ) {
		$return = '';
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

if ( !$row->enabled ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
		<a href='#' class="ipsLinkPanel" aria-hidden="true" tabindex="-1" data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-content='#alertContent_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog-title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->author(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<div class='ipsData__title'>
					<h4><a href='#' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-content='#alertContent_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog-title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
	                <div class="ipsBadges">
		                
IPSCONTENT;

if ( $row->anonymous ):
$return .= <<<IPSCONTENT

		                   <span class='ipsBadge ipsBadge--neutral ipsBadge--icon'><i class="fa-solid fa-eye" data-ipstooltip="" _title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i></span>
		                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		                    
IPSCONTENT;

if ( $row->reply == 1 ):
$return .= <<<IPSCONTENT

		                        <span class='ipsBadge ipsBadge--neutral ipsBadge--icon'><i class="fa-regular fa-envelope" data-ipstooltip="" _title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_can_reply', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i></span>
		                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		                    
IPSCONTENT;

if ( $row->reply == 2 ):
$return .= <<<IPSCONTENT

		                         <span class='ipsBadge ipsBadge--neutral ipsBadge--icon'><i class="fa-solid fa-envelope" data-ipstooltip="" _title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_must_reply', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i></span>
		                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		                
IPSCONTENT;

if ( $row->recipient_type == 'user' ):
$return .= <<<IPSCONTENT

		                   <span class='ipsBadge ipsBadge--neutral ipsBadge--icon'><i class="fa-solid fa-user" data-ipstooltip="" _title="
IPSCONTENT;

$sprintf = array($row->memberName()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_tooltip_member', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
"></i></span>
		                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		                    
IPSCONTENT;

$names = \IPS\Member::loggedIn()->language()->formatList( $row->groupNames() );
$return .= <<<IPSCONTENT

		                    <span class='ipsBadge ipsBadge--neutral ipsBadge--icon'><i class="fa-solid fa-users" data-ipstooltip="" _title="
IPSCONTENT;

$htmlsprintf = array($names); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_tooltip_groups', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
"></i></span>
		                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		                
IPSCONTENT;

if ( $row->enabled ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--negative'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'inactive', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		                
IPSCONTENT;

if ( $row->author()->member_id === \IPS\Member::loggedIn()->member_id AND $row->reply > 0 and ( $count = $row->membersRepliedCount() ) ):
$return .= <<<IPSCONTENT

		                    <span class='ipsBadge ipsBadge--positive'><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=alerts&do=viewReplies&id={$row->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "alert", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($count); $pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_users_have_replied', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a></span>
		                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	                </div>
				</div>
				<div class="ipsData__meta">
					<ul class='ipsList ipsList--sep'>
						<li class='i-font-weight_600'>
IPSCONTENT;

if ( ! $row->viewed ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_not_viewed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($row->viewed); $pluralize = array( $row->viewed ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_viewed_times', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</li>
						<li>
IPSCONTENT;

$sprintf = array($row->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

$val = ( $row->start instanceof \IPS\DateTime ) ? $row->start : \IPS\DateTime::ts( $row->start );$return .= (string) $val->localeDate();
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $row->end ):
$return .= <<<IPSCONTENT
 - 
IPSCONTENT;

$val = ( $row->end instanceof \IPS\DateTime ) ? $row->end : \IPS\DateTime::ts( $row->end );$return .= (string) $val->localeDate();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</li>
					</ul>
				</div>
				<div hidden>
					<div id='alertContent_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="i-padding_3">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $row->content() );
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
			
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

				<div class=''>
					<a href='#elAlert
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' id='elAlert
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_soft' data-ipsMenu>
						<i class='fa-solid fa-gear'></i> <i class='fa-solid fa-caret-down'></i>
						<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</a>
					<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide' id='elAlert
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
						<li class='ipsMenu_item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('status')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_toggle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
							<span data-role="ipsMenu_selectedText">
IPSCONTENT;

if ( $row->enabled ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_mark_inactive', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_mark_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
							</a>
						</li>
						<li class='ipsMenu_item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('create'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_alert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-modal='true' data-ipsDialog-destructOnClose='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_alert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								<span data-role="ipsMenu_selectedText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</a>
						</li>
						<li class='ipsMenu_item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('changeAuthor'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_alert_author', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-modal='true' data-ipsDialog-destructOnClose='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_alert_author', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
							<span data-role="ipsMenu_selectedText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_change_author', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</a>
						</li>
						<li class='ipsMenu_sep'></li>
						<li class='ipsMenu_item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('delete')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</a>
						</li>
					</ul>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

			<div class='ipsData__mod'>
				<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsInput ipsInput--toggle'>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function alerts( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsPageHeader'>
    <div class="ipsPageHeader__row">
		<div class="ipsPageHeader__primary">
			<h1 class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_alerts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		</div>
		<ul class='ipsButtons ipsButtons--main'>
			<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&tab=alerts&action=create", null, "modcp_alerts", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' id='elAdd_Alert' class='ipsButton ipsButton--primary' data-ipsDialog data-ipsDialog-modal='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_alert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_alert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
		</ul>
	</div>
</header>
<div class=''>
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function announcementGroupCheck( $groups ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsMessage ipsMessage--warning" id="elAnnouncementGroupWarning">
	<div>
IPSCONTENT;

$sprintf = array( \IPS\Member::loggedIn()->language()->formatList($groups) );$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_not_all_view_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
</div>
IPSCONTENT;

		return $return;
}

	function announcementRow( $table, $headers, $rows ) {
		$return = '';
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

if ( !$row->active ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('create'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1" data-ipsDialog data-ipsDialog-modal='true' data-ipsDialog-destructOnClose='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_announcement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->author(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<div class='ipsData__title'>
					<h4>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-content='#announcementContent_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_announcement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
					<div class='ipsBadges'>
						
IPSCONTENT;

if ( $row->active ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--negative'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'inactive', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
				<div class="ipsData__meta">
					
IPSCONTENT;

$sprintf = array($row->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $row->__get( $row::$databaseColumnMap['date'] ) instanceof \IPS\DateTime ) ? $row->__get( $row::$databaseColumnMap['date'] ) : \IPS\DateTime::ts( $row->__get( $row::$databaseColumnMap['date'] ) );$return .= (string) $val->localeDate();
$return .= <<<IPSCONTENT

				</div>
			</div>
			<div class='ipsDialog_inlineContent i-padding_3' id='announcementContent_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

if ( $row->type == \IPS\core\Announcements\Announcement::TYPE_CONTENT ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $row->content() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $row->type == \IPS\core\Announcements\Announcement::TYPE_URL ):
$return .= <<<IPSCONTENT

					<div><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel='noopener' class='i-font-weight_600'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_soft i-text-decoration_underline'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class='i-font-weight_600'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

				<div class=''>
					<a href='#elAnnouncement
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' id='elAnnouncement
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_soft' data-ipsMenu>
						<i class='fa-solid fa-gear'></i> <i class='fa-solid fa-caret-down'></i>
						<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</a>
					<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide' id='elAnnouncement
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
						<li class='ipsMenu_item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('status')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_toggle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								<span data-role="ipsMenu_selectedText">
IPSCONTENT;

if ( $row->active ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_mark_inactive', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_mark_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
							</a>
						</li>
						<li class='ipsMenu_item'>
						    <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_announcement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-content='#announcementContent_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $row->type == \IPS\core\Announcements\Announcement::TYPE_CONTENT ):
$return .= <<<IPSCONTENT
data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						        <span data-role="ipsMenu_selectedText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						    </a>
						</li>
						<li class='ipsMenu_item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('create'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_announcement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-modal='true' data-ipsDialog-destructOnClose='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_announcement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								<span data-role="ipsMenu_selectedText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</a>
						</li>
						<li class='ipsMenu_sep'></li>
						<li class='ipsMenu_item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('delete')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</a>
						</li>
					</ul>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

			<div class='ipsData__mod'>
				<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsInput ipsInput--toggle'>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function announcements( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsPageHeader'>
    <div class="ipsPageHeader__row">
		<div class="ipsPageHeader__primary">
			<h1 class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_announcements', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		</div>
		<ul class='ipsButtons ipsButtons--main'>
			<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&tab=announcements&action=create", null, "modcp_announcements", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' id='elAdd_Announcement' class='ipsButton ipsButton--primary' data-ipsDialog data-ipsDialog-modal='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_announcement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-plus'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_announcement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
		</ul>
	</div>
</header>
<div class=''>
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function approvalQueueClubWrapper( $html, $actions ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsApprovalQueueItem'>
    <ul class='ipsButtons'>
        <li>
            <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $actions['delete'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small" data-action="approvalQueueAction" data-type="delete"><i class="fa-solid fa-xmark"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
        </li>
        <li>
            <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $actions['approve'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--positive" data-action="approvalQueueAction" data-type="approve"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
        </li>
    </ul>
    <div class="ipsClearfix">
        {$html}
    </div>
</div>
IPSCONTENT;

		return $return;
}

	function approvalQueueItem( $item, $ref, $container, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	<div class=''>
		<article class="">
			<div class='i-background_1 i-padding_2'>
				<div class="ipsPhotoPanel"> 
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userPhoto( $item->author() );
$return .= <<<IPSCONTENT

					<div class="ipsPhotoPanel__text">
						<div class="ipsPhotoPanel__primary">
							
IPSCONTENT;

if ( $item->author()->member_id ):
$return .= <<<IPSCONTENT

								<a href="#user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsmenu="">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i></a>
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
" data-ipsDialog-remoteSubmit data-ipsDialog-destructOnClose data-ipsDialog-flashMessage="
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

if ( $item->author()->member_id != \IPS\Member::loggedIn()->member_id AND \IPS\member::loggedIn()->modPermission('can_flag_as_spammer') ):
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

else:
$return .= <<<IPSCONTENT

	                        	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<div class="ipsPhotoPanel__secondary i-font-size_-1">
							<ul class='ipsList ipsList--sep'>
								<li class='i-font-weight_600'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' hidden></i>
IPSCONTENT;

$val = "{$item::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								<li>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $item->mapped('date') )->html(FALSE)); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'date_replied', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</li>
								
IPSCONTENT;

if ( $container ):
$return .= <<<IPSCONTENT
<li>
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
</a></li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class=''>
				<div class='i-padding_3 i-padding-bottom_0 i-flex i-align-items_center'>
					<h2 class="ipsTitle ipsTitle--h4 i-flex_11"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
				</div>
				<div class="ipsRichText ipsPost i-padding_3" data-controller='core.front.core.lightboxedImages'>{$item->content()}</div>
			</div>
		</article>
	</div>
	
IPSCONTENT;

if ( $reason = $item->approvalQueueReason() ):
$return .= <<<IPSCONTENT

		<div class='i-background_2 i-padding_3 i-margin_2 i-border-radius_box'>
			<div class='ipsTitle ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reason_for_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			<p class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reason, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function approvalQueueItemWrapper( $html, $actions, $id ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsApprovalQueueItem'>
    
IPSCONTENT;

if ( \is_array( $actions ) AND \count( $actions ) ):
$return .= <<<IPSCONTENT

        <ul class='ipsButtons ipsButtons--end i-padding_2 i-background_2'>
            
IPSCONTENT;

if ( isset( $actions['delete'] ) ):
$return .= <<<IPSCONTENT

            <li>
                <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $actions['delete'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--text ipsButton--small" data-action="approvalQueueAction" data-type="delete"><i class="fa-solid fa-xmark"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
            </li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( isset( $actions['hide'] ) ):
$return .= <<<IPSCONTENT

            <li>
                <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $actions['hide'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--text ipsButton--small" data-action="approvalQueueAction" data-type="hide"><i class="fa-solid fa-eye-low-vision"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
            </li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( isset( $actions['approve'] ) ):
$return .= <<<IPSCONTENT

            <li>
                <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $actions['approve'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--text ipsButton--small ipsButton--positive" data-action="approvalQueueAction" data-type="approve"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
            </li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            <li>
                <div class='i-padding-inline_1'>
                    <input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', array_keys( $actions ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='' class="ipsInput ipsInput--toggle">
                </div>
            </li>
        </ul>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    {$html}
</div>
IPSCONTENT;

		return $return;
}

	function approvalQueueRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $rows ) ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$row = \IPS\core\Approval::constructFromData( $row );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( $row->item() ):
$return .= <<<IPSCONTENT

        <li class='ipsData__item'>
            
IPSCONTENT;

$return .= $row->html();
$return .= <<<IPSCONTENT

        </li>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <div class="ipsEmpty">
        <i class="fa-solid fa-check"></i>
        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approval_queue_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
    </div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function approvalQueueTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.global.core.table,core.front.modcp.approveQueue
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT
,core.front.core.moderation
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $table->dummyLoading ):
$return .= <<<IPSCONTENT
data-dummyLoading
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $table->getPaginationKey() != 'page' ):
$return .= <<<IPSCONTENT
data-pageParam='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getPaginationKey(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-tableID='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($table->baseUrl->stripQueryString($table->getPaginationKey())), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( $table->title ):
$return .= <<<IPSCONTENT

		<h2 class='ipsBox__header'>
IPSCONTENT;

$val = "{$table->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ( $table->canModerate() AND $table->showFilters ) OR ( $table->showAdvancedSearch AND ( ( isset( $table->sortOptions ) and \count( $table->sortOptions ) > 1 ) OR $table->advancedSearch ) ) OR ( !empty( $table->filters ) ) OR ( $table->pages > 1 ) ):
$return .= <<<IPSCONTENT

	<div class="ipsButtonBar ipsButtonBar--top">
		<div class="ipsButtonBar__pagination 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="tablePagination">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsButtonBar__end">
			<ul class="ipsDataFilters">
				
IPSCONTENT;

if ( $table->showAdvancedSearch AND ( ( isset( $table->sortOptions ) and \count( $table->sortOptions ) > 1 ) OR $table->advancedSearch ) ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

if ( isset($table->sortOptions)  ):
$return .= <<<IPSCONTENT

						<a href="#elSortByMenu_menu" class="ipsDataFilters__button" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="sortButton" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
						<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
								
IPSCONTENT;

$custom = TRUE;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

foreach ( $table->sortOptions as $k => $col ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item 
IPSCONTENT;

if ( $col === $table->getSortByColumn() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$custom = FALSE;
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $col, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-sortDirection='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getSortDirection( $k ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $col, 'sortdirection' => $table->getSortDirection( $k ) ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
IPSCONTENT;

$val = "{$table->langPrefix}sort_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

								<li class="ipsMenu_item 
IPSCONTENT;

if ( $custom ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-noSelect="true">
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' rel="nofollow">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
						
IPSCONTENT;

elseif ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' rel="nofollow">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

					<li>
						<a href="#elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="ipsDataFilters__button" data-role="tableFilterMenu" id="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
						<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
							<li data-action="tableFilter" data-ipsMenuValue='' class='ipsMenu_item 
IPSCONTENT;

if ( !$table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => '', 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="nofollow">
IPSCONTENT;

$val = "{$table->langPrefix}all"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
							
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

								<li data-action="tableFilter" data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsMenu_item 
IPSCONTENT;

if ( $k === $table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="nofollow">
IPSCONTENT;

$val = "{$table->langPrefix}{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

				<li>
				    <a class="ipsJS_show ipsDataFilters__button" href="#elCheck_menu" id="elCheck_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$val = "{$table->langPrefix}select_rows_tooltip"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsAutoCheck data-ipsAutoCheck-context="#elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active">
				        <span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span> <i class="fa-solid fa-caret-down"></i>
				        <span class='ipsNotification' data-role='autoCheckCount'>0</span>
				    </a>
				    <ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="elCheck_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
				        <li class="ipsMenu_title">
IPSCONTENT;

$val = "{$table->langPrefix}select_rows"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
				        <li class="ipsMenu_item" data-ipsMenuValue="all"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				        <li class="ipsMenu_item" data-ipsMenuValue="none"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				    </ul>
                </li>
			</ul>
		</div>
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    <form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-role='moderationTools' data-ipsPageAction>
        
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

            <i-data>
				<ol class="ipsData ipsData--table ipsData--approval-queue 
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

            <div class="ipsEmpty">
                <i class="fa-solid fa-check"></i>
                <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approval_queue_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
            </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


        <div class="ipsJS_hide ipsData__modBar" data-role="pageActionOptions">
			<select class="ipsInput ipsInput--select i-basis_300" name="modaction" data-role="moderationAction">
				<option value='delete' data-icon='trash'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
				<option value='hide' data-icon='eye-low-vision'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
				<option value='approve' data-icon='check'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
			</select>
			<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
        </div>
    </form>

	<div class="ipsButtonBar ipsButtonBar--bottom 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="tablePagination">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function commentsList( $comments, $url, $totalCount, $perPage ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $url, $totalCount, isset( \IPS\Request::i()->page ) ? \intval( \IPS\Request::i()->page ) : 1, $perPage );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $comments )  ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $comments as $comment ):
$return .= <<<IPSCONTENT

		{$comment->html()}
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $url, $totalCount, isset( \IPS\Request::i()->page ) ? \intval( \IPS\Request::i()->page ) : 1, $perPage );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function deletedContent( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsModCpanel__deletedContent'>
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function deletedRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows AS $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$item = $row->object();
$return .= <<<IPSCONTENT

	<li class='ipsData__item i-padding_0'>
		<article class="ipsEntry ipsEntry--simple">
			<div class='ipsEntry__header'>
				<div class="ipsEntry__header-align">
					<div class="ipsPhotoPanel">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userPhoto( $item->author() );
$return .= <<<IPSCONTENT

						<div class="ipsPhotoPanel__text">
							<div class="ipsPhotoPanel__primary i-color_hard">
								
IPSCONTENT;

if ( $item->author()->member_id ):
$return .= <<<IPSCONTENT

									<a href="#user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsmenu="">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <i class="ipsMenuCaret"></i></a>
									<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
										
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('mod_can_warn') ):
$return .= <<<IPSCONTENT

											<li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=warn&id={$item->author()->member_id}", null, "warn_add", array( $item->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$sprintf = array($item->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_member', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" data-ipsDialog-remoteSubmit data-ipsDialog-destructOnClose data-ipsDialog-flashMessage="
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

if ( $item->author()->member_id != \IPS\Member::loggedIn()->member_id AND \IPS\member::loggedIn()->modPermission('can_flag_as_spammer') ):
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

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
							<div class="ipsPhotoPanel__secondary i-font-size_-1">
								<ul class='ipsList ipsList--sep'>
									<li><span class="i-color_hard i-font-weight_500"><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 i-margin-end_icon' hidden></i>
IPSCONTENT;

$val = "{$item::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></li>
									<li>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $item->mapped('date') )->html(FALSE)); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'date_replied', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</li>
									
IPSCONTENT;

if ( $row->objectContainer() ):
$return .= <<<IPSCONTENT
<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posted_in_container', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->objectContainer()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-color_inherit">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->objectContainer()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
							</div>
						</div>
					</div>
					<div>
						<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='' class="ipsInput ipsInput--toggle">
					</div>
				</div>
			</div>
			<div class="ipsEntry__content">
				<div class='ipsEntry__post'>
					<h2 class="i-font-size_3 i-font-weight_600 i-margin-bottom_2"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->objectTitle(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
					<div class="ipsRichText" data-controller='core.front.core.lightboxedImages'>{$item->content()}</div>
				</div>
			</div>
		</article>
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

	function deletedTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.global.core.table
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT
,core.front.core.moderation
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $table->getPaginationKey() != 'page' ):
$return .= <<<IPSCONTENT
data-pageParam='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getPaginationKey(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-tableID='deletedcontent'>
	
IPSCONTENT;

if ( $table->title ):
$return .= <<<IPSCONTENT

		<h2 class='ipsBox__header'>
IPSCONTENT;

$val = "{$table->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsButtonBar">
		
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar__pagination" data-role="tablePagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar__end">
			<ul class="ipsDataFilters">
				
IPSCONTENT;

if ( $table->showAdvancedSearch AND ( ( isset( $table->sortOptions ) and \count( $table->sortOptions ) > 1 ) OR $table->advancedSearch ) ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

if ( isset($table->sortOptions)  ):
$return .= <<<IPSCONTENT

						<a href="#elSortByMenu_menu" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button" data-role="sortButton" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
						<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
								
IPSCONTENT;

$custom = TRUE;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

foreach ( $table->sortOptions as $k => $col ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item 
IPSCONTENT;

if ( $col === $table->getSortByColumn() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$custom = FALSE;
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $col, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-sortDirection='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getSortDirection( $k ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $col, 'sortdirection' => $table->getSortDirection( $k ) ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$col}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

								<li class="ipsMenu_item 
IPSCONTENT;

if ( $custom ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-noSelect="true">
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
						
IPSCONTENT;

elseif ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

					<li>
						<a href="#elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="ipsDataFilters__button" data-role="tableFilterMenu" id="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
						<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
							<li data-action="tableFilter" data-ipsMenuValue='' class='ipsMenu_item 
IPSCONTENT;

if ( !$table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => '', 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$table->langPrefix}all"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
							
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

								<li data-action="tableFilter" data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsMenu_item 
IPSCONTENT;

if ( $k === $table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$table->langPrefix}{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

				<li>
					<a class="ipsJS_show ipsDataFilters__button" href="#elCheck_menu" id="elCheck_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$val = "{$table->langPrefix}select_rows_tooltip"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsAutoCheck data-ipsAutoCheck-context="#elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active">
						<span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span> <i class="fa-solid fa-caret-down"></i>
						<span class='ipsNotification' data-role='autoCheckCount'>0</span>
					</a>
					<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="elCheck_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
						<li class="ipsMenu_title">
IPSCONTENT;

$val = "{$table->langPrefix}select_rows"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
						<li class="ipsMenu_item" data-ipsMenuValue="all"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class="ipsMenu_item" data-ipsMenuValue="none"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					</ul>
				</li>
			</ul>
		</div>
	</div>

	
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

		<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-role='moderationTools' data-ipsPageAction>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

			<ol class='ipsData
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="tableRows" itemscope itemtype="http://schema.org/ItemList">
				
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

			</ol>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='ipsEmptyMessage'><p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rows_in_table', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p></div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

			<div class="ipsJS_hide ipsData__modBar" data-role="pageActionOptions">
				<select class="ipsInput ipsInput--select i-basis_300" name="modaction" data-role="moderationAction">
					
IPSCONTENT;

if ( $table->canModerate('restore') ):
$return .= <<<IPSCONTENT

						<option value='restore' data-icon='undo'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_visible', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate('restore_as_hidden') ):
$return .= <<<IPSCONTENT

						<option value='restore_as_hidden' data-icon='low-vision'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate('delete') ):
$return .= <<<IPSCONTENT

						<option value='delete' data-icon='trash'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_immediately', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</select>
				<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</form>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar">
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

	function hiddenTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.global.core.table,core.front.modcp.approveQueue
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT
,core.front.core.moderation
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $table->dummyLoading ):
$return .= <<<IPSCONTENT
data-dummyLoading
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $table->getPaginationKey() != 'page' ):
$return .= <<<IPSCONTENT
data-pageParam='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getPaginationKey(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-tableID='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($table->baseUrl->stripQueryString($table->getPaginationKey())), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<!-- core/front/tables/table -->
	
IPSCONTENT;

if ( $table->title ):
$return .= <<<IPSCONTENT

		<h2 class='ipsBox__header'>
IPSCONTENT;

$val = "{$table->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( ( $table->canModerate() AND $table->showFilters ) OR ( $table->showAdvancedSearch AND ( ( isset( $table->sortOptions ) and \count( $table->sortOptions ) > 1 ) OR $table->advancedSearch ) ) OR ( !empty( $table->filters ) ) OR ( $table->pages > 1 ) ):
$return .= <<<IPSCONTENT

	<div class="ipsButtonBar ipsButtonBar--top">
		<div class="ipsButtonBar__pagination 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="tablePagination">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsButtonBar__end">
			<ul class='ipsDataFilters'>
				<li>
					
IPSCONTENT;

if ( isset($table->sortOptions)  ):
$return .= <<<IPSCONTENT

					<a href="#elSortByMenu_menu" class="ipsDataFilters__button" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="sortButton" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
					<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
					
IPSCONTENT;

foreach ( $table->sortOptions as $k ):
$return .= <<<IPSCONTENT

						<li class="ipsMenu_item 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->sortby === $k ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => \IPS\Request::i()->filter, 'sortby' => $k ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
					
IPSCONTENT;

elseif ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' rel="nofollow">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
				<li>
					<a href="#elFilterMenu_menu" class='ipsDataFilters__button' id="elFilterMenu_search_results" data-role="filterButton" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
					<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elFilterMenu_search_results_menu">
						<li class="ipsMenu_item 
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->filter || \IPS\Widget\Request::i()->filter == 'all' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="all">
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_everything', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
						
IPSCONTENT;

foreach ( $table->filters as $key => $class ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->filter == $key ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $key ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$class::$title}_pl"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</li>
				<li>
				    <a class="ipsJS_show ipsDataFilters__button" href="#elCheck_menu" id="elCheck_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$val = "{$table->langPrefix}select_rows_tooltip"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsAutoCheck data-ipsAutoCheck-context="#elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active">
				        <span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span> <i class="fa-solid fa-caret-down"></i>
				        <span class='ipsNotification' data-role='autoCheckCount'>0</span>
				    </a>
				    <ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="elCheck_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
				        <li class="ipsMenu_title">
IPSCONTENT;

$val = "{$table->langPrefix}select_rows"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
				        <li class="ipsMenu_item" data-ipsMenuValue="all"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				        <li class="ipsMenu_item" data-ipsMenuValue="none"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				    </ul>
                </li>
			</ul>
		</div>
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    <form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-role='moderationTools' data-ipsPageAction>
        
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

            <i-data>
				<ol class="ipsData ipsData--table ipsData--hidden-table 
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

            <div class="ipsEmpty">
                <i class="fa-solid fa-check"></i>
                <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approval_queue_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
            </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


        <div class="ipsJS_hide ipsData__modBar" data-role="pageActionOptions">
			<select class="ipsInput ipsInput--select i-basis_300" name="modaction" data-role="moderationAction">
				<option value='delete' data-icon='trash'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
				<option value='unhide' data-icon='eye'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unhide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
			</select>
			<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
        </div>
    </form>

	<div class="ipsButtonBar ipsButtonBar--bottom 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="tablePagination">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function hiddenTableRow( $item, $ref, $container, $title, $row ) {
		$return = '';
		$return .= <<<IPSCONTENT

<article class="ipsEntry ipsEntry--simple">
	<div class="ipsEntry__header">
		<div class='ipsEntry__header-align'>
			<div class="ipsPhotoPanel">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userPhoto( $item->author() );
$return .= <<<IPSCONTENT

				<div class="ipsPhotoPanel__text">
					<div class="ipsPhotoPanel__primary i-color_hard">
						
IPSCONTENT;

if ( $item->author()->member_id ):
$return .= <<<IPSCONTENT

							<a href="#user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsmenu="">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <i class="ipsMenuCaret"></i></a>
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
" data-ipsDialog-remoteSubmit data-ipsDialog-destructOnClose data-ipsDialog-flashMessage="
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

if ( $item->author()->member_id != \IPS\Member::loggedIn()->member_id AND \IPS\member::loggedIn()->modPermission('can_flag_as_spammer') ):
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

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class="ipsPhotoPanel__secondary i-font-size_-1">
						<ul class='ipsList ipsList--sep'>
							<li><span class='i-color_hard i-font-weight_500'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' hidden></i>
IPSCONTENT;

$val = "{$item::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></span></li>
							<li>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $item->mapped('date') )->html(FALSE)); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'date_replied', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</li>
							
IPSCONTENT;

if ( $container ):
$return .= <<<IPSCONTENT
<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posted_in_container', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-color_inherit">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</div>
			</div>
			<div>
				<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['index_class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['index_object_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $row['index_mod_actions']), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='' class="ipsInput ipsInput--toggle">
			</div>
		</div>
	</div>
	<div class="ipsEntry__content">
		<div class='ipsEntry__post'>
			<h2 class="i-font-size_3 i-font-weight_600 i-margin-bottom_2"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
			<div class="ipsRichText" data-controller='core.front.core.lightboxedImages'>{$item->content()}</div>
		</div>
	</div>
</article>
IPSCONTENT;

		return $return;
}

	function hiddenTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $rows ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

<li class='ipsData__item i-padding_0'>
    {$row['index_content']}
</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

<div class="ipsEmpty">
	<i class="fa-solid fa-check"></i>
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_hidden_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function hiddenTableWrapper( $table, $total ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$table}

IPSCONTENT;

if ( $total > 250 ):
$return .= <<<IPSCONTENT

	<div class="i-padding_4 something-small-but-dont-know-new-classes">
		
IPSCONTENT;

$sprintf = array($total, 250); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_hidden_showing_x_of_y', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function ipMemberRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty( $rows ) ):
$return .= <<<IPSCONTENT

	<li class='ipsData__item'>
		<div class='ipsEmptyMessage'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>
	</li>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $r ):
$return .= <<<IPSCONTENT

		<li class='ipsData__item'>
			<div class='ipsData__content'>
				
IPSCONTENT;

foreach ( $r as $k => $v ):
$return .= <<<IPSCONTENT

					<div class='i-basis_180'>
						
IPSCONTENT;

if ( $k === '_buttons' ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( $v );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							{$v}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endforeach;
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

		return $return;
}

	function ipMemberTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller="core.global.core.table">
	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class='ipsButtonBar ipsButtonBar--top'>
			<div data-role="tablePagination" class='ipsButtonBar__pagination'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="">
		<h2 class='ipsTitle ipsTitle--h4 ipsTitle--padding'>
IPSCONTENT;

$sprintf = array($table->extra->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ips_used_member', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			<ol class="ipsData ipsData--table ipsData--compact ipsData--zebra 
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
">
				<li class='ipsData__item i-font-weight_600 i-color_soft'>
					<div class='ipsData__content'>
						
IPSCONTENT;

foreach ( $headers as $k => $header ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $header !== '_buttons' ):
$return .= <<<IPSCONTENT

								<div class='i-basis_180'>
IPSCONTENT;

$val = "{$table->langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
							
IPSCONTENT;

elseif ( $header === '_buttons' ):
$return .= <<<IPSCONTENT

								<div class='i-basis_180'></div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</div>
				</li>
				<li data-role="tableRows">
					
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

				</li>
			</ol>
		</i-data>
		<div class='ipsButtonBar ipsButtonBar--bottom'>
			<div data-role="tablePagination" class='ipsButtonBar__pagination'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function iptools( $form, $members ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox__padding i-border-bottom_1 ipsFieldRow ipsFieldRow--primary' id='elModCPIPTools'>
	{$form}
</div>
<div class='ipsBox__padding ipsFieldRow ipsFieldRow--primary' id='elModCPIPMemberTools'>
	{$members}
</div>
IPSCONTENT;

		return $return;
}

	function memberManagementRow( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li class='ipsData__item' id='elUserRow_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $row['name'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<div class='ipsData__icon'>{$row['photo']}</div>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<h4 class='ipsData__title'>{$row['name']}</h4>
				<ul class='ipsList ipsList--inline i-align-items_center i-color_soft i-link-color_inherit'>
					
IPSCONTENT;

foreach ( $row['_buttons'] as $button ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $button['title'] == 'modcp_view_warnings' ):
$return .= <<<IPSCONTENT

							<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					<li>
						<a href="#elUserMod
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="elUserMod
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-font-size_2" data-ipsMenu>
							<i class="fa-solid fa-gear"></i> <i class="fa-solid fa-caret-down"></i>
						</a>
					</li>
				</ul>
				<ul class='ipsMenu ipsHide' id='elUserMod
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
					
IPSCONTENT;

foreach ( $row['_buttons'] as $button ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</a>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function members( $content, $tabs, $activeTab, $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox__padding i-border-bottom_1 ipsFieldRow ipsFieldRow--primary' id='elModCPMemberSearch'>
	{$form}
</div>
<i-tabs class='ipsTabs' id='elmodCPTabs' data-ipsTabBar data-ipsTabBar-contentArea='#elmodCPTabs_content'>
	<div role='tablist'>
		
IPSCONTENT;

foreach ( $tabs as $key => $tab ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&tab=members&area=$key", null, "modcp_members", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' id='elmodCPTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" aria-controls="elmodCPTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( $activeTab == $tab ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

$val = "modcp_members_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</a>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

</i-tabs>
<section id='elmodCPTabs_content' class='ipsTabs__panels ipsTabs__panels--padded'>
	<div id="elmodCPTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class="ipsTabs__panel" role="tabpanel" aria-labelledby="elmodCPTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		{$content}
	</div>
</section>
IPSCONTENT;

		return $return;
}

	function promoteTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT
 
	
IPSCONTENT;

foreach ( $rows as $item ):
$return .= <<<IPSCONTENT

		<!--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-->
		<li class='ipsData__item 
IPSCONTENT;

if ( $item->failed ):
$return .= <<<IPSCONTENT
ipsData__item--warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' itemprop="itemListElement" 
IPSCONTENT;

if ( $item->scheduled > time() ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<div class='ipsData__icon'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $item->author(), 'tiny' );
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsData__content'>
				<div class='ipsData__main'>
					<div class='ipsData__title'>
						<h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->object()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='
IPSCONTENT;

if ( $item->failed and $item->failed > 3 ):
$return .= <<<IPSCONTENT
i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->objectTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
						
IPSCONTENT;

$photoCount = ( $item->imageObjects() !== NULL ) ? \count( $item->imageObjects() ) : 0;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $photoCount ):
$return .= <<<IPSCONTENT

							<span class="i-color_soft"><i class="fa-regular fa-image"></i> 
IPSCONTENT;

$pluralize = array( $photoCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_photo_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsData__desc'>
                        
IPSCONTENT;

$object = $item->object();
$return .= <<<IPSCONTENT

                        <i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $object::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
                        
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->objectMetaDescription(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                    </div>
				</div>
				
IPSCONTENT;

$internalText = $item->getText();
$return .= <<<IPSCONTENT

				<div class='ipsButtons 
IPSCONTENT;

if ( $internalText ):
$return .= <<<IPSCONTENT
cPromoteModCP_edit
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->object()->url()->csrf()->setQueryString( array( 'do' => 'unfeature' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="delete" class="ipsButton ipsButton--small ipsButton--soft ipsButton--icon" data-ipsToolTip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirm><i class="fa-solid fa-circle-xmark"></i></a>
					
IPSCONTENT;

if ( $internalText ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->object()->url()->setQueryString( array( 'do' => 'editFeatured' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-action="edit" class="ipsButton ipsButton--small ipsButton--soft ipsButton--icon" data-ipsToolTip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-pencil"></i></a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
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

	function recentWarningsRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty( $rows ) ):
$return .= <<<IPSCONTENT

	<li class='ipsData__item'>
		<div class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</li>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $warning ):
$return .= <<<IPSCONTENT

		<li class='ipsData__item'>
			<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $warning->member ), 'fluid' );
$return .= <<<IPSCONTENT
</div>
			<div class='ipsData__content'>
				<div class='ipsData__main'>
					<div class='ipsData__title'>
						<span class="ipsWarningPoints ipsWarningPoints--small">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						<h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'wan_action_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "core_warn_reason_{$warning->reason}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></h4>
					</div>
					<div class='ipsData__meta'>
						<ul class='ipsList ipsList--sep'>
							<li class='i-font-weight_500'>
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $warning->member )->link(), \IPS\Member::load( $warning->moderator )->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_warned_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</li>
							<li>
IPSCONTENT;

$val = ( $warning->date instanceof \IPS\DateTime ) ? $warning->date : \IPS\DateTime::ts( $warning->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
						</ul>
					</div>
					
IPSCONTENT;

if ( \IPS\Settings::i()->warnings_acknowledge ):
$return .= <<<IPSCONTENT

						<div class='i-margin-top_2'>
							
IPSCONTENT;

if ( $warning->acknowledged ):
$return .= <<<IPSCONTENT

								<strong class='i-color_positive'><i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_acknowledged', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_not_acknowledged', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $warning->note_mods ):
$return .= <<<IPSCONTENT

						<div class='i-margin-top_2 ipsRichText ipsTruncate_4'>
							
IPSCONTENT;

$return .= \IPS\Text\Parser::truncate( $warning->note_mods );
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
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

	function recentWarningsTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class=''>
	<h2 class='ipsTitle ipsTitle--h4 ipsTitle--padding'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_recent_warnings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar">
			<div class="ipsButtonBar__pagination">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT
</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<i-data>
		<ul class='ipsData ipsData--table ipsData--recent-warnings'>
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

		</ul>
	</i-data>

	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar">
			<div class="ipsButtonBar__pagination">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
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

	function report( $report,$comment,$item,$ref,$prevReport,$prevItem,$nextReport,$nextItem,$delLog=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $comment ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$idField = $comment::$databaseColumnId;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$quoteData = json_encode( array( 'userid' => $comment->author()->member_id, 'username' => $comment->author()->name, 'timestamp' => $comment->mapped('date'), 'contentapp' => $item::$application, 'contenttype' => $item::$module, 'contentclass' => str_replace( '\\', '_', mb_substr( $comment::$itemClass, 4 ) ) ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $item ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$class = \get_class( $item );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$quoteData = json_encode( array( 'userid' => $item->author()->member_id, 'username' => $item->author()->name, 'timestamp' => $item->mapped('date'), 'contentapp' => $item::$application, 'contenttype' => $item::$module, 'contentclass' => str_replace( '\\', '_', mb_substr( \get_class( $item ), 4 ) ) ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$quoteData = json_encode( array() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$pointCount = $report->getReportTypeCounts( true );
$return .= <<<IPSCONTENT


IPSCONTENT;

$filterByType = isset( \IPS\Widget\Request::i()->report_type ) ? \IPS\Widget\Request::i()->report_type : NULL;
$return .= <<<IPSCONTENT

<div class=''>
	<div class="ipsModCpanel__stickyHeader i-flex i-justify-content_space-between i-align-items_center" id='elReportSidebar_toggle' data-controller='core.front.modcp.reportToggle'>
		<div class='i-flex_11 i-flex i-align-items_center i-gap_2 i-font-weight_500 
IPSCONTENT;

if ( $report->status == 1 ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

elseif ( $report->status == 2 or $report->status == 4 ):
$return .= <<<IPSCONTENT
i-color_warning
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-color_positive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
			<i class='i-font-size_4 fa-solid 
IPSCONTENT;

if ( $report->status == 4 ):
$return .= <<<IPSCONTENT
fa-archive
IPSCONTENT;

elseif ( $report->status == 1 ):
$return .= <<<IPSCONTENT
fa-flag
IPSCONTENT;

elseif ( $report->status == 2 ):
$return .= <<<IPSCONTENT
fa-triangle-exclamation
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-check-circle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='reportIcon'></i>
			<div>
				<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'status', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</strong> <span data-role="reportStatus">
IPSCONTENT;

$val = "report_status_{$report->status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</div>
		</div>
		<ul class="ipsButtons ipsButtons--end">
			<li>
				<a href='#elReportItem
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' id='elReportItem
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' data-ipsMenu data-ipsMenu-closeOnClick data-ipsMenu-appendTo='#elReportSidebar_toggle' data-ipsMenu-selectable>
					<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_report_as', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></span>
				</a>
			</li>
		</ul>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "modcp", \IPS\Request::i()->app )->reportToggle( $report, '', FALSE );
$return .= <<<IPSCONTENT

	</div>

	<article class='ipsColumns i-gap_0' data-controller="core.front.modcp.report">
		<div class='ipsColumns__primary' data-controller='core.front.core.comment' data-feedid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment?->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-quoteData='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $quoteData, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>

			<div class='i-background_2 i-padding_2 i-flex i-align-items_center i-flex-wrap_wrap' data-role="authorPanel">
				
IPSCONTENT;

if ( $report->author ):
$return .= <<<IPSCONTENT

					<div class="ipsPhotoPanel i-flex_11">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $report->author ), 'fluid' );
$return .= <<<IPSCONTENT

						<div class="ipsPhotoPanel__text">
							<div class="ipsPhotoPanel__primary"><a href="#user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->author, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->author, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMenu>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $report->author )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<i class="fa-solid fa-caret-down i-margin-start_icon"></i></a></div>
							<div class="ipsPhotoPanel__secondary">
								
IPSCONTENT;

if ( \IPS\Member::load( $report->author )->mod_posts ):
$return .= <<<IPSCONTENT

									<span class="ipsBadge ipsBadge--warning" data-ipsTooltip title="
IPSCONTENT;

if ( \IPS\Member::load( $report->author )->mod_posts == -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_modq_perm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( \IPS\Member::load( $report->author )->mod_posts )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_modq_temp', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_modq', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									</span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\Member::load( $report->author )->restrict_post ):
$return .= <<<IPSCONTENT

									<span class="ipsBadge ipsBadge--warning" data-ipsTooltip title="
IPSCONTENT;

if ( \IPS\Member::load( $report->author )->restrict_post == -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_nopost_perm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( \IPS\Member::load( $report->author )->restrict_post )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_nopost_temp', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_nopost', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									</span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\Member::load( $report->author )->temp_ban ):
$return .= <<<IPSCONTENT

									<span class="ipsBadge ipsBadge--warning" data-ipsTooltip title="
IPSCONTENT;

if ( \IPS\Member::load( $report->author )->temp_ban == -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_banned_perm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( \IPS\Member::load( $report->author )->temp_ban )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_banned_temp', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_banned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									</span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</div>
					</div>
					<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide' id='user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->author, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canWarn( \IPS\Member::load( $report->author ) ) ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=warn&id={$report->author}&ref={$ref}", null, "warn_add", array( \IPS\Member::load( $report->author )->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $report->author )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_member', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
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

if ( $report->author != \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \IPS\Member::load( $report->author )->members_bitoptions['bw_is_spammer'] ):
$return .= <<<IPSCONTENT

								<li class='ipsMenu_item' data-ipsMenuValue='spamUnFlagButton'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$report->author}&s=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( \IPS\Member::load( $report->author )->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<li class='ipsMenu_item' data-ipsMenuValue='spamFlagButton'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$report->author}&s=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( \IPS\Member::load( $report->author )->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
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

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$report->author}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
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

if ( \IPS\Member::loggedIn()->modPermission('mod_see_warn') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !\count(\IPS\Member::load( $report->author )->warnings( 1 )) ):
$return .= <<<IPSCONTENT

							<div class='i-color_soft i-font-weight_500'>
								<i class="fa-regular fa-circle-check i-margin-end_icon"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_previous_warnings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
	
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class="ipsPhotoPanel">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( 0 ), 'fluid' );
$return .= <<<IPSCONTENT

						<div class="ipsPhotoPanel__text">
							<div class="ipsPhotoPanel__primary">
								<span data-ipsMenu>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( 0 )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							</div>
						</div>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>

			
IPSCONTENT;

if ( $comment AND !( $item::$firstCommentRequired and $comment->isFirst() ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $comment->hidden() === -2 ):
$return .= <<<IPSCONTENT

					<div class="ipsEmpty">
						<i class='fa-regular fa-trash-can'></i>
						<p>
IPSCONTENT;

if ( $delLog ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($delLog->deletion_date); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_delete_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>
					</div>
					
IPSCONTENT;

if ( $delLog ):
$return .= <<<IPSCONTENT

						<hr class='ipsHr'>
						<div class="ipsEntry ipsEntry--modCpanel i-padding_3" id='elReportComment'>
							<div class='' data-role='commentContent'>
								<div>
									<div class='ipsRichText ipsTruncate_x' style='--line-clamp: 50' data-controller='core.front.core.lightboxedImages'>
										{$comment->content()}
									</div>
								</div>
								<hr class='ipsHr'>
								<ul class='ipsList ipsList--inline' data-role="commentControls">
									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_manage_deleted_content') ):
$return .= <<<IPSCONTENT

										<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('restore')->csrf()->setQueryString( '_report', $report->id ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_visible_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_visible', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('restore')->csrf()->setQueryString( array( 'restoreAsHidden' => 1, '_report' => $report->id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_hidden_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('delete')->csrf()->setQueryString( array( 'immediately' => 1, '_report' => $report->id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_immediately_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_immediately', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
							</div>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class="ipsEntry ipsEntry--modCpanel i-padding_3" id='elReportComment'>
						<h2 class="ipsTitle ipsTitle--h2">
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url()->setQueryString( array( 'action' => 'find', 'parent' => 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						</h2>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url()->setQueryString( array( 'action' => 'find' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_soft'>{$report->tableDescription()}</a>
						
IPSCONTENT;

if ( $pointCount ):
$return .= <<<IPSCONTENT
<span class="ipsBadge ipsBadge--positive">
IPSCONTENT;

$pluralize = array( $pointCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'automoderation_report_points_flag', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
						<div class='i-margin-top_3 ipsPost' data-role='commentContent'>
							<div>
								<div class='ipsRichText ipsTruncate_x' style='--line-clamp: 50' data-controller='core.front.core.lightboxedImages'>
									{$comment->content()}
								</div>
							</div>
						</div>
						<hr class='ipsHr'>
						<ul class='ipsList ipsList--inline' data-role="commentControls">
							
IPSCONTENT;

if ( $comment->canEdit( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

								<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('edit'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&_report=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='editComment'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $comment->canHide( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

								<li><a href='
IPSCONTENT;

if ( $comment::$hideLogKey ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('hide'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('hide')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
&_report=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='hideComment' 
IPSCONTENT;

if ( isset( $comment::$databaseColumnMap['edit_reason'] ) ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $comment->canDelete( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

								<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('delete')->csrf()->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&_report=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( !\IPS\Settings::i()->dellog_retention_period ):
$return .= <<<IPSCONTENT
data-action='deleteComment' data-showOnDelete="#elReportCommentDeleted" data-hideOnDelete="#elReportComment"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-confirm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $comment->item()->canDelete( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

								<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('moderate')->setQueryString( 'action', 'delete' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&_report=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( !\IPS\Settings::i()->dellog_retention_period ):
$return .= <<<IPSCONTENT
data-action='deleteComment' data-showOnDelete="#elReportCommentDeleted" data-hideOnDelete="#elReportComment"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-confirm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $item::$title )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_thing', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( $item ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $item->hidden() === -2 ):
$return .= <<<IPSCONTENT

					<div class="ipsEmpty">
						<i class='fa-regular fa-trash-can'></i>
						<p>
IPSCONTENT;

if ( $delLog ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($delLog->deletion_date); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_delete_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>
					</div>

					
IPSCONTENT;

if ( $delLog ):
$return .= <<<IPSCONTENT

						<hr class='ipsHr'>
						<div class='ipsPost' data-role='commentContent'>
							<div>
								<div class='ipsRichText ipsTruncate_x' style='--line-clamp: 50' data-controller='core.front.core.lightboxedImages'>
									{$item->content()}
								</div>
							</div>
							<hr class='ipsHr'>
							<ul class='ipsList ipsList--inline' data-role="commentControls">
								
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_manage_deleted_content') ):
$return .= <<<IPSCONTENT

									<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('restore')->csrf()->setQueryString( '_report', $report->id ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_visible_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_visible', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('restore')->csrf()->setQueryString( array( 'restoreAsHidden' => 1, '_report' => $report->id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_hidden_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('moderate')->csrf()->setQueryString( array( 'action' => 'delete', 'immediate' => 1, '_report' => $report->id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_immediately_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_immediately', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class="ipsEntry ipsEntry--modCpanel i-padding_3" id='elReportComment'>
						<div class='ipsEntry__post'>
							<h2 class="ipsTitle ipsTitle--h5"><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url()->setQueryString( 'action', 'find' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
							<div class='i-margin-top_1'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url()->setQueryString( array( 'action' => 'find' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
							</div>
							
IPSCONTENT;

if ( $pointCount ):
$return .= <<<IPSCONTENT
<span class="ipsBadge ipsBadge--positive">
IPSCONTENT;

$pluralize = array( $pointCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'automoderation_report_points_flag', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<div class='i-padding-block_3' data-role='commentContent'>
								<div>
									<div class='ipsRichText ipsTruncate_x' style='--line-clamp: 50' data-controller='core.front.core.lightboxedImages'>{$item->content()}</div>
								</div>
							</div>
						</div>
						<div class="ipsEntry__footer i-padding_0">
							<menu class='ipsEntry__controls' data-role="commentControls">
								
IPSCONTENT;

if ( $item->canEdit( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

									<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('edit'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&_report=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='editComment'><i class="fa-regular fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $item->canHide( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

									<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('moderate')->setQueryString( 'action', 'hide' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&_report=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='hideComment' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-eye-slash"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $item->canDelete( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

									<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('moderate')->setQueryString( 'action', 'delete' )->csrf()->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&_report=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( !\IPS\Settings::i()->dellog_retention_period ):
$return .= <<<IPSCONTENT
data-action='deleteComment' data-showOnDelete="#elReportCommentDeleted" data-hideOnDelete="#elReportComment"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-confirm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class="fa-regular fa-trash-can"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</menu>
						</div>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="ipsEmpty 
IPSCONTENT;

if ( $comment or $item ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" id='elReportCommentDeleted'>
				<i class="fa-regular fa-trash-can"></i>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			<div class="ipsEmpty ipsHide" id='elReportCommentDeletePending'>
				<i class='fa-regular fa-trash-can'></i>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_delete_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
		</div>
		
IPSCONTENT;

if ( $report->author ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "modcp", \IPS\Request::i()->app )->reportPanel( $report,$comment,$ref );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</article>
</div>

IPSCONTENT;

if ( $prevReport or $nextReport ):
$return .= <<<IPSCONTENT

	<nav class='ipsPager i-margin-top_2 i-padding-inline_3'>
		<div class='ipsPager_prev'>
			
IPSCONTENT;

if ( $prevReport ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=reports&action=view&id={$prevReport['id']}", null, "modcp_report", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'previous_report', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
					<span class='ipsPager_type'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'previous_report', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

if ( $prevItem ):
$return .= <<<IPSCONTENT

						<span class='ipsPager_title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $prevItem->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span class='ipsPager_title'><em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsPager_next'>
			
IPSCONTENT;

if ( $nextReport ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=reports&action=view&id={$nextReport['id']}", null, "modcp_report", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_report', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
					<span class='ipsPager_type'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_report', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

if ( $nextItem ):
$return .= <<<IPSCONTENT

						<span class='ipsPager_title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextItem->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span class='ipsPager_title'><em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</nav>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( count(\IPS\core\Reports\Types::roots() )>0 ):
$return .= <<<IPSCONTENT

<section class='i-margin-top_3'>
	<div class="ipsButtonBar ipsButtonBar--top">
		<h2 class="ipsTitle i-font-size_1 i-flex_91 i-align-self_center i-padding-start_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'responses_to_report', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<ul class="ipsDataFilters">
			<li>
				<a class="ipsJS_show ipsDataFilters__button" href="#elFilter_menu" id="elFilter" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" ipsMenu-selectable="radio">
					<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'automoderation_report_type_filter', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i>
				</a>
				<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elFilter_menu">
					<li class="ipsMenu_item 
IPSCONTENT;

if ( ! isset( \IPS\Widget\Request::i()->report_type ) ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					<li class="ipsMenu_sep"><hr></li>
					
IPSCONTENT;

foreach ( \IPS\core\Reports\Types::roots() as $type ):
$return .= <<<IPSCONTENT

						<li class="ipsMenu_item 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->report_type ) and \IPS\Widget\Request::i()->report_type == $type->id ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url()->setQueryString( array( 'report_type' => $type->id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</li>
		</ul>	
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div data-controller="core.front.core.commentsWrapper" data-tabsId='elTabsReport'>
		<i-tabs class="ipsTabs" id="ipsTabs_report" data-ipsTabBar data-ipstabbar-contentarea="#ipsTabs_report_content">
			<div role="tablist">
				<button type="button" id="ipsTabs_report_reports" class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_report_reports_panel" aria-selected="
IPSCONTENT;

if ( ! isset( \IPS\Widget\Request::i()->activeTab ) or \IPS\Widget\Request::i()->activeTab != 'comments' ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

$pluralize = array( \count( $report->reports( $filterByType ) ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_user_reports', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

				</button>
				<button type="button" id="ipsTabs_report_comments" class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_report_comments_panel" aria-selected="
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->activeTab ) and \IPS\Widget\Request::i()->activeTab == 'comments' ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

$pluralize = array( $report->num_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_mod_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

				</button>
			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

		</i-tabs>
		<div id="ipsTabs_report_content" class="ipsTabs__panels">
			<div id="ipsTabs_report_reports_panel" class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_report_reports" 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->activeTab ) and \IPS\Widget\Request::i()->activeTab == 'comments' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<div data-role="commentFeed">
					
IPSCONTENT;

foreach ( $report->reports( $filterByType ) as $r ):
$return .= <<<IPSCONTENT

					    <a name="report
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></a>
						<article id="elCommentMod_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['rid'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsEntry js-ipsEntry ipsEntry--simple ipsEntry--report">
							<header class='ipsEntry__header'>
								<div class='ipsEntry__header-align'>
									<div class='ipsPhotoPanel'>
										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $r['report_by'] ), 'mini' );
$return .= <<<IPSCONTENT

										<div class='ipsPhotoPanel__text'>
											<h3 class='ipsPhotoPanel__primary'>
                                                
IPSCONTENT;

if ( ! $r['report_by'] and isset( $r['guest_name'] ) ):
$return .= <<<IPSCONTENT

                                                    <span class='ipsType_light ipsType_smaller'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['guest_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
                                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                    
IPSCONTENT;

$return .= \IPS\Member::load( $r['report_by'] )->link();
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

if ( isset( $r['guest_email'] ) ):
$return .= <<<IPSCONTENT

                                                   <a href="mailto:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['guest_email'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton_verySmall ipsButton_light'><i class="fa fa-envelope"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['guest_email'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
                                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            </h3>
                                            <p class="ipsPhotoPanel__secondary">
												
IPSCONTENT;

if ( $r['report_type'] ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$type = ''; try{ $type = \IPS\core\Reports\Types::load( $r['report_type'] )->_title; } catch( \Exception $e ) { } 
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$sprintf = array($type); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_type_byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_date_submitted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $r['date_reported'] instanceof \IPS\DateTime ) ? $r['date_reported'] : \IPS\DateTime::ts( $r['date_reported'] );$return .= $val->html();
$return .= <<<IPSCONTENT

											</p>
										</div>
									</div>
									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_use_ip_tools') and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'modcp' ) ) ):
$return .= <<<IPSCONTENT

										<p class='i-color_soft i-link-color_inherit'>
											<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=ip_tools&ip={$r['ip_address']}", null, "modcp_ip_tools", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($r['ip_address']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
										</p>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									<a href='#elReport
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' id='elReport
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--small ipsButton--text i-margin-start_2" data-ipsMenu>
										<i class='fa-solid fa-cog'></i> <i class='fa-solid fa-caret-down'></i>
									</a>
									<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide' id='elReport
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
										<li class='ipsMenu_item'>
											<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=reports&action=changeType&id={$r['id']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_modcp_change_submission_reason', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-modal='true' data-ipsDialog-destructOnClose='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_modcp_change_submission_reason', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
												<span data-role="ipsMenu_selectedText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_modcp_change_submission_reason', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
											</a>
										</li>
									</ul>
								</div>
							</header>
							<div class='ipsEntry__content js-ipsEntry__content'>
								<div class='ipsEntry__post'>
									<div class="ipsRichText " data-controller='core.front.core.lightboxedImages'>
										
IPSCONTENT;

if ( $r['report'] ):
$return .= <<<IPSCONTENT

											{$r['report']}
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<p class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_no_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
								</div>
							</div>
						</article>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			</div>
			<div id="ipsTabs_report_comments_panel" class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_report_comments" 
IPSCONTENT;

if ( ! isset( \IPS\Widget\Request::i()->activeTab ) or \IPS\Widget\Request::i()->activeTab != 'comments' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<div data-controller='core.front.core.commentFeed' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-commentsType='mod_comments' data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $report->isLastPage() ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

if ( $report->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

						{$report->commentPagination()}
						<br><br>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div data-role='commentFeed' data-controller='core.front.core.moderation'>
						<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url()->csrf()->setQueryString( 'action', 'multimodComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
							
IPSCONTENT;

foreach ( $report->comments() as $modcomment ):
$return .= <<<IPSCONTENT

								{$modcomment->html()}
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $report );
$return .= <<<IPSCONTENT

						</form>
					</div>
					
IPSCONTENT;

if ( $report->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

						<hr class='ipsHr'>
						{$report->commentPagination()}
						<br><br>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div data-role='replyArea' class='i-background_3 i-padding_3'>
						{$report->commentForm()}
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
IPSCONTENT;

		return $return;
}

	function reportList( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsModCpanel__reportList' data-controller='core.front.modcp.reportList'>
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function reportListOverview( $table, $headers, $rows, $quickSearch, $advancedSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count($rows) ):
$return .= <<<IPSCONTENT

	<i-data>
		<ol class="ipsData ipsData--table ipsData--compact ipsData--report-list-dropdown">
			
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item" 
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
>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

if ( $row->mapped('title') ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
					<div class='ipsData__icon'>
						
IPSCONTENT;

if ( $lastComment = $row->comments( 1, 0, 'date', 'desc' ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $lastComment->author(), 'tiny' );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->author(), 'tiny' );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class="ipsData__main">
						<h4 class='ipsData__title'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
</a></h4>
						<ul class='ipsData__meta ipsList ipsList--sep'>
							<li>
IPSCONTENT;

if ( $row->last_updated ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $row->last_updated instanceof \IPS\DateTime ) ? $row->last_updated : \IPS\DateTime::ts( $row->last_updated );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $row->first_report_date instanceof \IPS\DateTime ) ? $row->first_report_date : \IPS\DateTime::ts( $row->first_report_date );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</li>
							
IPSCONTENT;

foreach ( $row->stats() as $k => $v ):
$return .= <<<IPSCONTENT

								<li>
									<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
</span>
									<span>
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
					<div class='i-align-self_center'>
						<i class='
IPSCONTENT;

if ( $row->status == 1 ):
$return .= <<<IPSCONTENT
fa-solid fa-flag i-color_negative
IPSCONTENT;

elseif ( $row->status == 2 ):
$return .= <<<IPSCONTENT
fa-solid fa-triangle-exclamation i-color_warning
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-solid fa-check-circle i-color_positive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="ipsMenu_selectedIcon" title="
IPSCONTENT;

if ( $row->status == 1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_status_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $row->status == 2 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_status_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_status_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsTooltip></i>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	</i-data>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results_reports', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function reportPanel( $report,$comment,$ref ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $report->author AND \IPS\Member::loggedIn()->modPermission('mod_see_warn') AND \count(\IPS\Member::load( $report->author )->warnings( 1 )) ):
$return .= <<<IPSCONTENT

	<aside class='ipsColumns__secondary i-basis_340'>
		<div id="elReportSidebar">
			<div id='elReportPanel'>
				<div class='i-padding_2'><strong class='ipsTitle ipsTitle--h4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'previous_warnings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></div>	
				<i-data>
					<ol class="ipsData ipsData--table ipsData--compact">
						
IPSCONTENT;

foreach ( \IPS\Member::load( $report->author )->warnings( 2 ) as $warning ):
$return .= <<<IPSCONTENT

							<li class="ipsData__item">
								<div class=''>
									<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=view&id={$report->author}&w={$warning->id}", null, "warn_view", array( \IPS\Member::load( $report->author )->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsHover class="i-color_inherit">
										<span class="ipsWarningPoints">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									</a>
								</div>
								<div class='ipsData__main'>
									<h4 class='ipsData__title'>
										<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=view&id={$report->author}&w={$warning->id}", null, "warn_view", array( \IPS\Member::load( $report->author )->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsHover>
IPSCONTENT;

$val = "core_warn_reason_{$warning->reason}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</h4>
									<p class='ipsData__meta'>
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $warning->moderator )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $warning->date instanceof \IPS\DateTime ) ? $warning->date : \IPS\DateTime::ts( $warning->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
								</div>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ol>
				</i-data>
				<div class='i-padding_1'>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&id={$report->author}", null, "warn_list", array( \IPS\Member::load( $report->author )->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit i-width_100p'><i class='fa-solid fa-bars'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_all_c', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</div>
			</div>
		</div>
	</aside>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reportTableDescription( $class, $report, $container=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsMargin_top:half">
<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url()->setQueryString( array( 'action' => 'find' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
    
IPSCONTENT;

if ( $container === NULL ):
$return .= <<<IPSCONTENT

	    
IPSCONTENT;

$val = "{$class::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $class::$title ), $container->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'icon_blurb_in_containers', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</a>

IPSCONTENT;

if ( $report->status == \IPS\core\Reports\Report::STATUS_REJECTED ):
$return .= <<<IPSCONTENT

    <span class="ipsBadge ipsBadge_negative">
IPSCONTENT;

$val = "report_status_{$report->status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

elseif ( $report->status == \IPS\core\Reports\Report::STATUS_OPEN ):
$return .= <<<IPSCONTENT

    <span class="ipsBadge ipsBadge_recommended">
IPSCONTENT;

$val = "report_status_{$report->status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <span class="ipsBadge ipsBadge_positive">
IPSCONTENT;

$val = "report_status_{$report->status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $report->getReportedTypes() as $name ):
$return .= <<<IPSCONTENT

<span class="ipsBadge ipsBadge_style1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function reportToggle( $report, $ref='list', $showIcon=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$hasAuthorNotifications = (int) count( $report::getAuthorNotifications() );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $showIcon ):
$return .= <<<IPSCONTENT

<a href='#elReportItem
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' id='elReportItem
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action="changeStatus" class='' data-ipsMenu data-ipsMenu-closeOnClick>
	<span class="i-font-size_3"><i class='
IPSCONTENT;

if ( $report->status == 4 ):
$return .= <<<IPSCONTENT
fa-archive
IPSCONTENT;

elseif ( $report->status == 1 ):
$return .= <<<IPSCONTENT
fa-solid fa-flag
IPSCONTENT;

elseif ( $report->status == 2 ):
$return .= <<<IPSCONTENT
fa-solid fa-triangle-exclamation
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-solid fa-check-circle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="ipsMenu_selectedIcon"></i></span> <i class='fa-solid fa-caret-down'></i>
</a>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide' id='elReportItem
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' data-reportId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<li class='ipsMenu_title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_as', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	<li class='ipsMenu_item 
IPSCONTENT;

if ( $report->status == 3 ):
$return .= <<<IPSCONTENT
ipsMenu_itemDisabled ipsType_light
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='3'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&tab=reports&id={$report->id}&action=view&setStatus=3&ref={$ref}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "modcp_report", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action='_ipsMenu_ping' data-hasNotifications='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $hasAuthorNotifications, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-showconfirm>
			<i class='fa-solid fa-check-circle' data-role="ipsMenu_selectedIcon"></i><span data-role="ipsMenu_selectedText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'menu_report_status_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</a>
	</li>
	<li class='ipsMenu_item 
IPSCONTENT;

if ( $report->status == 4 ):
$return .= <<<IPSCONTENT
ipsMenu_itemDisabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='4'>
        <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&tab=reports&id={$report->id}&action=view&setStatus=4&ref={$ref}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "modcp_report", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action='_ipsMenu_ping' data-hasNotifications='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $hasAuthorNotifications, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-showconfirm>
            <i class='fa-solid fa-archive' data-role="ipsMenu_selectedIcon"></i> &nbsp;<span data-role="ipsMenu_selectedText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'menu_report_status_4', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
        </a>
    </li>
	<li class='ipsMenu_item 
IPSCONTENT;

if ( $report->status == 2 ):
$return .= <<<IPSCONTENT
ipsMenu_itemDisabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='2'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&tab=reports&id={$report->id}&action=view&setStatus=2&ref={$ref}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "modcp_report", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action='_ipsMenu_ping'>
			<i class='fa-solid fa-triangle-exclamation' data-role="ipsMenu_selectedIcon"></i><span data-role="ipsMenu_selectedText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'menu_report_status_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</a>
	</li>
	<li class='ipsMenu_item 
IPSCONTENT;

if ( $report->status == 1 ):
$return .= <<<IPSCONTENT
ipsMenu_itemDisabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='1'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&tab=reports&id={$report->id}&action=view&setStatus=1&ref={$ref}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "modcp_report", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action='_ipsMenu_ping'>
			<i class='fa-solid fa-flag' data-role="ipsMenu_selectedIcon"></i><span data-role="ipsMenu_selectedText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'menu_report_status_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</a>
	</li>
	
IPSCONTENT;

if ( !$showIcon and $report->canDelete( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

		<li class='ipsMenu_sep'></li>
		<li class='ipsMenu_item'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report->url()->csrf()->setQueryString('_action', 'delete'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='ipsMenu_ping'>
				<i class='fa-solid fa-xmark'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</a>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function tableWrapper( $table, $title='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsModCpanel__tableWrapper' data-controller='core.front.modcp.reportList'>
	
IPSCONTENT;

if ( $title ):
$return .= <<<IPSCONTENT

		<h2 class='ipsTitle ipsTitle--padding ipsTitle--h4'>
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function template( $content, $tabs, $activeTab, $approvalCount ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsPull ipsBox ipsModCpanel">
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/modcp/template", "modcp_header:before", [ $content,$tabs,$activeTab,$approvalCount ] );
$return .= <<<IPSCONTENT
<div class="cModCP_header i-padding-inline_3 i-padding-block_3 i-flex i-align-items_center i-flex-wrap_wrap i-gap_2 i-border-bottom_3" data-ips-hook="modcp_header">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/modcp/template", "modcp_header:inside-start", [ $content,$tabs,$activeTab,$approvalCount ] );
$return .= <<<IPSCONTENT

		<h1 class="ipsTitle ipsTitle--h2 i-flex_11 i-flex i-align-items_center i-gap_2"><i class="fa-solid fa-user-lock i-color_soft i-opacity_5"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessReportCenter() ):
$return .= <<<IPSCONTENT

			<ul class="ipsList ipsList--inline i-gap_4 i-row-gap_1 i-font-weight_500">
				<li 
IPSCONTENT;

if ( !$approvalCount ):
$return .= <<<IPSCONTENT
class="i-color_soft" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><span id="elModCPApprovalCount" class="ipsBadge 
IPSCONTENT;

if ( !$approvalCount ):
$return .= <<<IPSCONTENT
ipsBadge--positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 ipsBadge--warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-margin-end_icon">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $approvalCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

$reportCount = \IPS\Member::loggedIn()->reportCount( TRUE );
$return .= <<<IPSCONTENT

				<li 
IPSCONTENT;

if ( !$reportCount ):
$return .= <<<IPSCONTENT
class="i-color_soft" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><span class="ipsBadge 
IPSCONTENT;

if ( !$reportCount ):
$return .= <<<IPSCONTENT
ipsBadge--positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsBadge--warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-margin-end_icon">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reportCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'active_reports', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/modcp/template", "modcp_header:inside-end", [ $content,$tabs,$activeTab,$approvalCount ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/modcp/template", "modcp_header:after", [ $content,$tabs,$activeTab,$approvalCount ] );
$return .= <<<IPSCONTENT

	<section class="ipsColumns ipsColumns--modcpanel i-gap_0">
		<div class="ipsColumns__secondary i-basis_340">
			<div class="ipsSideMenu ipsSideMenu--modcp-menu" id="modcp_menu" data-ipssidemenu>
				<h3 class="ipsSideMenu__view">
					<a href="#modcp_menu" data-action="openSideMenu"><i class="fa-solid fa-bars"></i> <span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_sections', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</h3>
				<div class="ipsSideMenu__menu">
					
IPSCONTENT;

if ( isset( $tabs['content'] ) and count( $tabs['content'] ) ):
$return .= <<<IPSCONTENT

						<h4 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_content_tools', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
						<ul class="ipsSideMenu__list">
						
IPSCONTENT;

foreach ( $tabs['content'] as $key => $extensions ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=$key", null, "modcp_tab", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsSideMenu_item 
IPSCONTENT;

if ( $activeTab == $key ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "modcp_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $tabs['members'] ) and count( $tabs['members'] ) ):
$return .= <<<IPSCONTENT

						<h4 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_member_tools', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
						<ul class="ipsSideMenu__list">
						
IPSCONTENT;

foreach ( $tabs['members'] as $key => $extensions ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=$key", null, "modcp_tab", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsSideMenu_item 
IPSCONTENT;

if ( $activeTab == $key ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "modcp_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $tabs['other'] ) and count( $tabs['other'] ) ):
$return .= <<<IPSCONTENT

						<h4 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modcp_tools', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
						<ul class="ipsSideMenu__list">
						
IPSCONTENT;

foreach ( $tabs['other'] as $key => $extensions ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=$key", null, "modcp_tab", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsSideMenu_item 
IPSCONTENT;

if ( $activeTab == $key ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "modcp_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
		<div class="ipsColumns__primary" id="elModCPContent">
			<div class="">
				{$content}
			</div>
		</div>
	</section>
</div>
IPSCONTENT;

		return $return;
}

	function unapprovedContent( $content, $tabs, $activeTab ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-tabs class='ipsTabs' id='ipsTabs_unapproved' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_unapproved_content'>
	<div role='tablist'>
		
IPSCONTENT;

foreach ( $tabs as $key => $tab ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&tab=approval&area=$key", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' id='ipsTabs_unapproved_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" aria-controls="ipsTabs_unapproved_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( $activeTab == $tab ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

$val = "{$tab}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</a>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

</i-tabs>
<section id='ipsTabs_unapproved_content' class='ipsTabs__panels ipsTabs__panels--padded'>
	<div id="ipsTabs_unapproved_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_unapproved_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		{$content}
	</div>
</section>
	
IPSCONTENT;

		return $return;
}

	function warnActions( $actions, $member, $min ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- v5 todo: Not sure where this page is used -->
<style>*{color:#ca0!important;}</style>
<div class="i-background_3 i-padding_3">
	<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_point_levels', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_points_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<i-data>
		<ol class="ipsData ipsData--table ipsData--compact">
			
IPSCONTENT;

if ( $min ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<div class=''>
						<span class="ipsWarningPoints"><i class='fa-solid fa-angle-left'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $min, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</div>
					<div class='ipsData__main'>
						<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_punishment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $actions as $action ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<div class=''>
						<span class="ipsWarningPoints">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action['wa_points'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</div>
					<div class='ipsData__main'>
						
IPSCONTENT;

if ( $action['wa_mq'] or $action['wa_rpa'] or $action['wa_suspend'] ):
$return .= <<<IPSCONTENT

							<ul>
								
IPSCONTENT;

if ( $action['wa_mq'] ):
$return .= <<<IPSCONTENT

									<li>
										<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_mq', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
										<br>
										<span class="i-color_soft">
											<i class='fa-regular fa-clock'></i>
											
IPSCONTENT;

if ( $action['wa_mq'] != -1 ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( $action['wa_mq_unit'] == 'h' ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$pluralize = array( $action['wa_mq'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'f_hours', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$pluralize = array( $action['wa_mq'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'f_days', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'indefinitely', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</span>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $action['wa_rpa'] ):
$return .= <<<IPSCONTENT

									<li>
										<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_rpa', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
										<br>
										<span class="i-color_soft">
											<i class='fa-regular fa-clock'></i>
											
IPSCONTENT;

if ( $action['wa_rpa'] != -1 ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( $action['wa_rpa_unit'] == 'h' ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$pluralize = array( $action['wa_rpa'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'f_hours', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$pluralize = array( $action['wa_rpa'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'f_days', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'indefinitely', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</span>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $action['wa_suspend'] ):
$return .= <<<IPSCONTENT

									<li>
										<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_suspend', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
										<br>
										<span class="i-color_soft">
											<i class='fa-regular fa-clock'></i>
											
IPSCONTENT;

if ( $action['wa_suspend'] != -1 ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( $action['wa_suspend_unit'] == 'h' ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$pluralize = array( $action['wa_suspend'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'f_hours', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$pluralize = array( $action['wa_suspend'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'f_days', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'indefinitely', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</span>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_punishment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

		</ol>
	</i-data>
</div>
IPSCONTENT;

		return $return;
}

	function warnHovercard( $warning ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsBox--warnHoverCard" id="warnhovercard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-controller="core.front.modcp.warnPopup">	
	<h2 class="ipsBox__header">
		
IPSCONTENT;

if ( $warning->canViewDetails() ):
$return .= <<<IPSCONTENT

			<span class='ipsWarningPoints 
IPSCONTENT;

if ( $warning->expire_date == 0 ):
$return .= <<<IPSCONTENT
ipsWarningPoints--removed
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

$val = "core_warn_reason_{$warning->reason}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h2>
	<div class="ipsBox__padding i-grid i-gap_3">
		
IPSCONTENT;

if ( \IPS\Settings::i()->warnings_acknowledge OR \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

			<p>
				
IPSCONTENT;

if ( $warning->acknowledged ):
$return .= <<<IPSCONTENT

					<strong class='i-color_positive'><i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_acknowledged', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $warning->canAcknowledge() ):
$return .= <<<IPSCONTENT

						<div class='i-background_2 i-padding_3 i-text-align_center'>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url('acknowledge')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--wide"><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acknowledge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							<p class='i-color_soft i-font-size_-2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acknowledge_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						</div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<strong class='i-color_soft'><i class='fa-regular fa-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_not_acknowledged', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $content = $warning->contentObject() and $content->canView() ):
$return .= <<<IPSCONTENT

			<div>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url()->setQueryString( '_warn', $warning->id ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_soft' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_go_to_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> &nbsp;&nbsp;
IPSCONTENT;

if ( $content instanceof \IPS\Content\Comment ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $warning->canViewDetails() ):
$return .= <<<IPSCONTENT

			<div class='ipsPhotoPanel'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $warning->moderator ), 'fluid' );
$return .= <<<IPSCONTENT

				<div class='ipsPhotoPanel__text'>
					<div class='ipsPhotoPanel__primary'>
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $warning->moderator )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
					<div class='ipsPhotoPanel__secondary'>
						<p>
IPSCONTENT;

$val = ( $warning->date instanceof \IPS\DateTime ) ? $warning->date : \IPS\DateTime::ts( $warning->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
						<p>
							
IPSCONTENT;

if ( $warning->expire_date > 0 ):
$return .= <<<IPSCONTENT

								<em><strong>(
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $warning->expire_date )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_expires', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
)</em></strong>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $warning->expire_date == 0 ):
$return .= <<<IPSCONTENT

								<em><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_no_longer_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $warning->removed_on ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $warning->removed_on )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_expired_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</em></strong>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</p>
					</div>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $warning->canViewDetails() or $warning->mq or $warning->rpa or $warning->suspend ):
$return .= <<<IPSCONTENT

			<div>
				<h3 class='ipsTitle ipsTitle--h4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_punishment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<ul class='ipsList ipsList--bullets i-margin-top_1'>
					
IPSCONTENT;

if ( $warning->canViewDetails() ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

if ( $warning->expire_date ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $warning->expire_date < time() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $warning->expire_date == -1 ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_action_points_never_expire', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$sprintf = array( \IPS\DateTime::ts( $warning->expire_date )); $pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_action_points_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $warning->expire_date )); $pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_action_points_expire', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_action_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $warning->cheev_point_reduction ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

$pluralize = array( $warning->cheev_point_reduction ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_deduct_cheev_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $warning->mq ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_modq', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 -
							
IPSCONTENT;

if ( $warning->mq == -1 ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'indefinitely', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

elseif ( $mq = $warning->mq_interval ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::formatInterval( $mq, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $warning->rpa ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_nopost', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 -
							
IPSCONTENT;

if ( $warning->rpa == -1 ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'indefinitely', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

elseif ( $rpa = $warning->rpa_interval ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::formatInterval( $rpa, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $warning->suspend ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_banned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 -
							
IPSCONTENT;

if ( $warning->suspend == -1 ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'indefinitely', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

elseif ( $suspend = $warning->suspend_interval ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::formatInterval( $suspend, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $warning->note_member ):
$return .= <<<IPSCONTENT

			<div>
				<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_member_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<div class='ipsRichText'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $warning->note_member );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $warning->note_mods and \IPS\Member::loggedIn()->modPermission('mod_see_warn') ):
$return .= <<<IPSCONTENT

			<div>
				<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_mod_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<div class='ipsRichText'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $warning->note_mods );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( $warning->canDelete() ):
$return .= <<<IPSCONTENT

		<div class="ipsSubmitRow">
			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url('delete')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke_this_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action="revoke" class='ipsButton ipsButton--primary'><i class="fa-solid fa-rotate-left"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function warningRevoke( $warning ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3'>
	<p class='i-font-size_2'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_revoke_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>
</div>
<ul class='ipsButtons ipsSubmitRow'>
	<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url('delete')->setQueryString('undo', 0)->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
	<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url('delete')->setQueryString('undo', 1)->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_revoke_undo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
</ul>
IPSCONTENT;

		return $return;
}

	function warningRowPoints( $points ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="i-font-size_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
<br>

IPSCONTENT;

$pluralize = array( $points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'wan_action_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}