<?php
namespace IPS\Theme;
class class_core_front_global extends \IPS\Theme\Template
{	function acknowledgeWarning( $warnings=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $warnings as $idx => $warning ):
$return .= <<<IPSCONTENT

	<div class='i-grid i-gap_1'>
		
IPSCONTENT;

if ( $idx === 0 ):
$return .= <<<IPSCONTENT

			<div class='ipsMessage ipsMessage--error'>
				<h4 class='ipsMessage__title'>
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $warning->moderator )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_have_been_warned', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h4>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->isBanned() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $warning->note_member ):
$return .= <<<IPSCONTENT

						<p>{$warning->note_member}</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'must_acknowledge_msg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					<br>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=view&id={$warning->member}&w={$warning->id}", null, "warn_view", array( \IPS\Member::loggedIn()->members_seo_name ), 0 )->addRef((string) \IPS\Request::i()->url()), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--soft' data-ipsDialog data-ipsDialog-size='narrow'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_warning_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function analytics( $item, $lastCommenter, $members, $busy, $reacted, $images, $commentCount ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$commentClass = $item::$commentClass;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $item, 'IPS\Content\Statistics' ) ):
$return .= <<<IPSCONTENT

<div class="">
    <div class='ipsFluid ipsFluid--padding i-gap_lines'>

        <div class='i-basis_100p i-flex i-flex-wrap_wrap i-gap_1'>
            <h4 class='ipsTitle ipsTitle--h5 i-margin-end_auto'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
            <div class='i-flex i-flex-wrap_wrap i-gap_3 i-row-gap_0 i-color_soft'>
                <span><i class="fa-regular fa-comments i-margin-end_icon" aria-hidden="true"></i> 
IPSCONTENT;

$pluralize = array( $commentCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
                <span><i class="fa-regular fa-eye i-margin-end_icon" aria-hidden="true"></i> 
IPSCONTENT;

$pluralize = array( $item->mapped('views') ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
            </div>
        </div>

        
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "analytics", "cloud" )->analyticsItem( $item );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


        <div class=''>
            <h4 class='ipsTitle ipsTitle--h4 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'active_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
            
IPSCONTENT;

if ( count( $members ) ):
$return .= <<<IPSCONTENT

                <i-data>
                    <ul class='ipsData ipsData--table ipsData--active-members'>
                        
IPSCONTENT;

foreach ( $members AS $member ):
$return .= <<<IPSCONTENT

                            <li class='ipsData__item'>
                                <div class='ipsData__icon'>
                                    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member['member'], 'fluid' );
$return .= <<<IPSCONTENT

                                </div>
                                <div class='ipsData__main'>
                                    <div>{$member['member']->link()}</div>
                                    <div>
IPSCONTENT;

$pluralize = array( $member['count'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_content_to_show', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>
        <div class=''>
            <h4 class='ipsTitle ipsTitle--h4 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'popular_days', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
            
IPSCONTENT;

if ( count( $busy ) ):
$return .= <<<IPSCONTENT

                <i-data>
                    <ul class='ipsData ipsData--table ipsData--popular-days'>
                        
IPSCONTENT;

foreach ( $busy AS $date => $day ):
$return .= <<<IPSCONTENT

                            <li class='ipsData__item'>
                                <div class='ipsData__main'>
                                    <div class='i-flex i-align-items_center i-justify-content_space-between'>
                                        <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->setQueryString( array( 'do' => 'findComment', 'comment' => $day['commentId'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-font-weight_600'>
IPSCONTENT;

$val = ( $day['date'] instanceof \IPS\DateTime ) ? $day['date'] : \IPS\DateTime::ts( $day['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
                                        <span class='i-color_soft'>
IPSCONTENT;

$pluralize = array( $day['count'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_content_to_show', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>
        <div class=''>
            <h4 class='ipsTitle ipsTitle--h4 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
            
IPSCONTENT;

if ( count( $reacted ) ):
$return .= <<<IPSCONTENT

                <i-data>
                    <ul class='ipsData ipsData--table ipsData--top-reacted'>
                        
IPSCONTENT;

foreach ( $reacted AS $react ):
$return .= <<<IPSCONTENT

                            <li class='ipsData__item'>
                                <div class='ipsData__icon'>
                                    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $react['comment']->author(), 'tiny' );
$return .= <<<IPSCONTENT

                                </div>
                                <div class='ipsData__main'>
                                    <div class='i-flex i-justify-content_space-between i-flex-wrap_wrap'>
                                        <div>
                                            <div class='i-font-weight_600'>{$react['comment']->author()->link()}</div>
                                            <div class='i-color_soft'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $react['comment']->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">{$react['comment']->dateLine()}</a></div>
                                        </div>
                                        <div class='i-color_soft'>
IPSCONTENT;

$pluralize = array( $react['count'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'react_total', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
                                    </div>
                                    <div class='i-margin-top_2 ipsTruncate_4'>{$react['comment']->truncated()}</div>
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_content_to_show', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>

        
IPSCONTENT;

if ( count( $images ) ):
$return .= <<<IPSCONTENT

            <div class='i-basis_100p'>
                <h4 class='ipsTitle ipsTitle--h4 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'images', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
                <div class='ipsImageGrid'>
                    
IPSCONTENT;

foreach ( $images AS $image ):
$return .= <<<IPSCONTENT

                        <div><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['commentUrl'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Attachment", $image['attach_location'] )->url;
$return .= <<<IPSCONTENT
' loading='lazy' alt=''></a></div>
                    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                </div>
            </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
    </div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function announcementContentTop(  ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $announcements = \IPS\core\Announcements\Announcement::loadAllByLocation('content') ):
$return .= <<<IPSCONTENT

	<div class='ipsAnnouncements ipsAnnouncements--content' data-controller="core.front.core.announcementBanner">
		
IPSCONTENT;

foreach ( $announcements as $announcement ):
$return .= <<<IPSCONTENT

			<div class='ipsAnnouncement ipsAnnouncement--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-announcementId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

if ( $announcement->type == \IPS\core\Announcements\Announcement::TYPE_CONTENT ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsAnnouncement__link' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-bullhorn ipsAnnouncement__icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

elseif ( $announcement->type == \IPS\core\Announcements\Announcement::TYPE_URL ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsAnnouncement__link' target="_blank" rel='noopener'><i class="fa-solid fa-arrow-up-right-from-square ipsAnnouncement__icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class='ipsAnnouncement__text'><i class="fa-solid fa-bullhorn ipsAnnouncement__icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<button type="button" data-role="dismissAnnouncement"><i class="fa-solid fa-xmark"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
			</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function announcementSidebar( $announcements ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-margin-bottom_3' id="cAnnouncementSidebar">
	<h3 class='ipsInvisible'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announcements', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	<ul class='ipsAnnouncements ipsAnnouncements--sidebar' data-controller="core.front.core.announcementBanner">
		
IPSCONTENT;

foreach ( $announcements as $announcement ):
$return .= <<<IPSCONTENT

			<div class='ipsAnnouncement ipsAnnouncement--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-announcementId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

if ( $announcement->type == \IPS\core\Announcements\Announcement::TYPE_CONTENT ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsAnnouncement__link' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-bullhorn ipsAnnouncement__icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

elseif ( $announcement->type == \IPS\core\Announcements\Announcement::TYPE_URL ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsAnnouncement__link' target="_blank" rel='noopener'><i class="fa-solid fa-arrow-up-right-from-square ipsAnnouncement__icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class='ipsAnnouncement__text'><i class="fa-solid fa-bullhorn ipsAnnouncement__icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<button type="button" data-role="dismissAnnouncement"><i class="fa-solid fa-xmark"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
			</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function announcementTop(  ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $announcements = \IPS\core\Announcements\Announcement::loadAllByLocation('top') ):
$return .= <<<IPSCONTENT

	<div class='ipsAnnouncements ipsAnnouncements--top ipsAnnouncements--sticky' data-controller="core.front.core.announcementBanner" >
		
IPSCONTENT;

foreach ( $announcements as $announcement ):
$return .= <<<IPSCONTENT

			<div class='ipsAnnouncement ipsAnnouncement--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-announcementId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

if ( $announcement->type == \IPS\core\Announcements\Announcement::TYPE_CONTENT ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsAnnouncement__link' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-bullhorn ipsAnnouncement__icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

elseif ( $announcement->type == \IPS\core\Announcements\Announcement::TYPE_URL ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsAnnouncement__link' target="_blank" rel='noopener'><i class="fa-solid fa-arrow-up-right-from-square ipsAnnouncement__icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class='ipsAnnouncement__text'><i class="fa-solid fa-bullhorn ipsAnnouncement__icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<button type="button" data-role="dismissAnnouncement"><i class="fa-solid fa-xmark"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
			</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function assignmentBadge( $item, $classes = '' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $assignment = $item->assignment and !$assignment->closed ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$assignedTo = $assignment->assignedTo();
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( $item->isAssignedToMember() ):
$return .= <<<IPSCONTENT

        <span class="ipsBadge ipsBadge--soft ipsBadge--assigned 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $assignedTo instanceof \IPS\Member\Team ):
$return .= <<<IPSCONTENT
title='
IPSCONTENT;

$sprintf = array($assignedTo->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_to_you_team', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_to_you', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsTooltip>
            <i class="fa-solid fa-paper-plane"></i>
            <span>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $assignedTo->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
        </span>
    
IPSCONTENT;

elseif ( $item->canAssign() ):
$return .= <<<IPSCONTENT

        <span class="ipsBadge ipsBadge--soft ipsBadge--assigned 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $assignedTo instanceof \IPS\Member\Team ):
$return .= <<<IPSCONTENT
title='
IPSCONTENT;

$sprintf = array($assignedTo->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_to_other_team', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
title='
IPSCONTENT;

$sprintf = array($assignedTo->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_to_other', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsTooltip>
            <i class="fa-solid fa-paper-plane"></i>
            <span>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $assignedTo->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
        </span>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function assignmentHeader( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $assignment = $item->assignment and !$assignment->closed ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$assignedTo = $assignment->assignedTo();
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( $item->isAssignedToMember() ):
$return .= <<<IPSCONTENT

        <div class="ipsMessage ipsMessage--assigned ipsMessage--general">
            <div class='i-flex i-justify-content_space-between i-align-items_center i-flex-wrap_wrap i-gap_1'>
				<div class='i-flex_91 i-font-weight_500'>
					<i class="fa-regular fa-paper-plane ipsMessage__icon"></i>
					
IPSCONTENT;

if ( $assignedTo instanceof \IPS\Member\Team ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($assignedTo->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_to_you_team', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_to_you', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<ul class='ipsButtons'>
					<li>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cloud&module=assignments&controller=assignments&do=assign&assignment={$assignment->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--small ipsButton--inherit" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reassign_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reassign_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-paper-plane"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reassign_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					
IPSCONTENT;

if ( !$assignment->closed ):
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cloud&module=assignments&controller=assignments&do=unassign&id={$assignment->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--small ipsButton--inherit" data-confirm title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unassign', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unassign', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
    
IPSCONTENT;

elseif ( $item->canAssign() ):
$return .= <<<IPSCONTENT

        <div class="ipsMessage ipsMessage--warning i-font-weight_600">
            <div class='i-flex i-justify-content_space-between i-align-items_center i-flex-wrap_wrap i-gap_1'>
            	<div class='i-flex_91'>
					<i class="fa-solid fa-paper-plane ipsMessage__icon"></i>
					
IPSCONTENT;

if ( $assignedTo instanceof \IPS\Member\Team ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($assignedTo->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_to_other_team', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($assignedTo->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assigned_to_other', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<ul class='ipsButtons'>
					<li>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cloud&module=assignments&controller=assignments&do=assign&assignment={$assignment->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--small ipsButton--inherit" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reassign_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reassign_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-paper-plane"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reassign_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					
IPSCONTENT;

if ( !$assignment->closed ):
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cloud&module=assignments&controller=assignments&do=unassign&id={$assignment->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--small ipsButton--inherit" data-confirm title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unassign', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unassign', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					
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

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function badge( $badgeType, $name, $size='', $icon=null, $additionalClasses=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBadge 
IPSCONTENT;

if ( $size ):
$return .= <<<IPSCONTENT
ipsBadge--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badgeType, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $additionalClasses as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
    
IPSCONTENT;

if ( $icon ):
$return .= <<<IPSCONTENT
<i class="fa 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function badgeIcon( $badgeType, $icon, $size='', $name='', $additionalClasses=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class='ipsBadge ipsBadge--icon 
IPSCONTENT;

if ( $size ):
$return .= <<<IPSCONTENT
ipsBadge--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badgeType, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $additionalClasses as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $name ):
$return .= <<<IPSCONTENT
title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <i class="fa 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i>
</span>
IPSCONTENT;

		return $return;
}

	function blankTemplate( $html, $title=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->htmlDataAttributes(  );
$return .= <<<IPSCONTENT
>
	<head>
		<title>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->getTitle( $title ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</title>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->prefersColorSchemeLoad(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->loadGuestColorScheme(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeMeta(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT

	</head>
	<body class='ipsApp ipsApp_front ipsLayout_noBackground 
IPSCONTENT;

foreach ( \IPS\Output::i()->bodyClasses as $class ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Output::i()->globalControllers ):
$return .= <<<IPSCONTENT
data-controller='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', \IPS\Output::i()->globalControllers ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( \IPS\Output::i()->inlineMessage ) ):
$return .= <<<IPSCONTENT
data-message="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->inlineMessage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		{$html}
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Output::i()->endBodyCode;
$return .= <<<IPSCONTENT

	</body>
</html>
IPSCONTENT;

		return $return;
}

	function blockEditorButton(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsMenu_item' data-menuItem='blockEditor'><button type='button' id='elWidgetControls' data-action='openSidebar'><i class="fa-solid fa-table-cells-large"></i> <span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'manage_blocks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button></li>
IPSCONTENT;

		return $return;
}

	function box( $content=NULL, $classes=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox 
IPSCONTENT;

if ( \count( $classes) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $classes ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	{$content}
</div>
IPSCONTENT;

		return $return;
}

	function breadcrumb( $position='top', $markRead=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<nav class='ipsBreadcrumb ipsBreadcrumb--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $position, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $position === "mobile" ):
$return .= <<<IPSCONTENT
ipsResponsive_header--mobile
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' aria-label="Breadcrumbs" 
IPSCONTENT;

if ( !\count( \IPS\Output::i()->breadcrumb ) and $position === "mobile" ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<ol itemscope itemtype="https://schema.org/BreadcrumbList">
		<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
			<a title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'home', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' href='
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
' itemprop="item">
				<i class="fa-solid fa-house-chimney"></i> <span itemprop="name">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'home', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</a>
			<meta itemprop="position" content="1" />
		</li>
		
IPSCONTENT;

$last = end(\IPS\Output::i()->breadcrumb);
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$index = 2;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( \IPS\Output::i()->breadcrumb as $k => $b ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $b[0] === NULL ):
$return .= <<<IPSCONTENT

				<li aria-current='location' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
					<span itemprop="name">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $b[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<meta itemprop="position" content="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $index, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" />
				</li>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $b[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' itemprop="item">
						<span itemprop="name">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $b[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $b != $last ):
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
					</a>
					<meta itemprop="position" content="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $index, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" />
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$index++;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ol>
	
IPSCONTENT;

if ( $position == 'bottom' ):
$return .= <<<IPSCONTENT

		<ul>
			
IPSCONTENT;

$defaultStream = \IPS\core\Stream::defaultStream();
$return .= <<<IPSCONTENT

			<li 
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'discover' ) )  ):
$return .= <<<IPSCONTENT
 class='ipsHide'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<a data-action="defaultStream" href='
IPSCONTENT;

if ( $defaultStream ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $defaultStream->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( ! $defaultStream ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class="fa-regular fa-file-lines"></i> <span data-role="defaultStreamName">
IPSCONTENT;

if ( $defaultStream ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $defaultStream->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
			</li>
		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</nav>
IPSCONTENT;

		return $return;
}

	function buttons( $buttons ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsButtons ipsButtons--main' data-template='buttons'>
	
IPSCONTENT;

foreach ( $buttons as $button ):
$return .= <<<IPSCONTENT

		<li class='
IPSCONTENT;

if ( isset( $button['hidden'] ) and $button['hidden'] ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( isset( $button['id'] ) ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<a
				
IPSCONTENT;

if ( isset( $button['link'] ) ):
$return .= <<<IPSCONTENT
href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				title='
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
				class='ipsButton ipsButton--inherit 
IPSCONTENT;

if ( isset( $button['class'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
				role="button"
				
IPSCONTENT;

if ( isset( $button['id'] ) ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_button"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['target'] ) ):
$return .= <<<IPSCONTENT
target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['target'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

if ( $button['target'] == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['data'] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $button['data'] as $k => $v ):
$return .= <<<IPSCONTENT

						data-
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

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['hotkey'] ) ):
$return .= <<<IPSCONTENT

					data-keyAction='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['hotkey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			>
				
IPSCONTENT;

if ( $button['icon'] ):
$return .= <<<IPSCONTENT

					<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>&nbsp;
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( isset($button['dropdown']) ):
$return .= <<<IPSCONTENT

					&nbsp;<i class='fa-solid fa-caret-down'></i>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</a>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function cachingLog( $log ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elCachingLog">
	
IPSCONTENT;

foreach ( $log as $i => $log ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$i = \str_replace( '.', '_', $i);
$return .= <<<IPSCONTENT

		<div class="cCachingLog" data-ipsDialog data-ipsDialog-content="#elCachingLog
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
			
IPSCONTENT;

if ( $log[0] === 'get' ):
$return .= <<<IPSCONTENT

				<span class="cCachingLogMethod cCachingLogMethod_get">get</span>
				<span class="cCachingLogKey">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

elseif ( $log[0] === 'set' ):
$return .= <<<IPSCONTENT

				<span class="cCachingLogMethod cCachingLogMethod_set">set</span>
				<span class="cCachingLogKey">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

elseif ( $log[0] === 'check' ):
$return .= <<<IPSCONTENT

				<span class="cCachingLogMethod cCachingLogMethod_check">check</span>
				<span class="cCachingLogKey">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

elseif ( $log[0] === 'delete' ):
$return .= <<<IPSCONTENT

				<span class="cCachingLogMethod cCachingLogMethod_delete">delete</span>
				<span class="cCachingLogKey">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="cCachingLogMethod">Redis</span>
				<span class="cCachingLogKey">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div id='elCachingLog
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='i-padding_3 ipsHide'>
			
IPSCONTENT;

if ( ! empty( $log[2] ) ):
$return .= <<<IPSCONTENT

				<pre class="prettyprint lang-php">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log[2], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
				<hr class="ipsHr">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log[3] ) ):
$return .= <<<IPSCONTENT
<pre class="prettyprint lang-php">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log[3], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<hr class="ipsHr">
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function carouselNavigation( $id, $classes='', $hiddenByDefault = TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsCarouselNav 
IPSCONTENT;

if ( $classes ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipscarousel='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $hiddenByDefault ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<button class='ipsCarouselNav__button' data-carousel-arrow='prev'><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'carousel_prev', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-ips' aria-hidden='true'></i></button>
	<button class='ipsCarouselNav__button' data-carousel-arrow='next'><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'carousel_next', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-ips' aria-hidden='true'></i></button>
</div>
IPSCONTENT;

		return $return;
}

	function comment( $item, $comment, $editorName, $app, $type, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $comment::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentWrap:before", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="commentWrap" id="comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap" data-controller="core.front.core.comment" data-feedid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commentapp="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commenttype="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commentid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-quotedata="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( array('userid' => $comment->author()->member_id, 'username' => $comment->author()->name, 'timestamp' => $comment->mapped('date'), 'contentapp' => $app, 'contenttype' => $type, 'contentclass' => $class, 'contentid' => $item->id, 'contentcommentid' => $comment->$idField) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsEntry__content js-ipsEntry__content" 
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\IntersectionViewTracking' ) AND $hash=$comment->getViewTrackingHash() ):
$return .= <<<IPSCONTENT
 data-view-hash="{$hash}" data-view-tracking-data="
IPSCONTENT;

$return .= base64_encode(json_encode( $comment->getViewTrackingData() ));
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentWrap:inside-start", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

	<header class="ipsEntry__header">
		<div class="ipsEntry__header-align">
			<div class="ipsPhotoPanel">
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentUserPhoto:before", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
<div class="ipsAvatarStack" data-ips-hook="commentUserPhoto">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentUserPhoto:inside-start", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $comment->author(), 'fluid', $comment->warningRef() );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Anonymous' ) AND !$comment->isAnonymous() ) and $comment->author()->canHaveAchievements() and \IPS\core\Achievements\Rank::show() and $rank = $comment->author()->rank() ):
$return .= <<<IPSCONTENT

						{$rank->html( 'ipsAvatarStack__rank' )}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentUserPhoto:inside-end", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentUserPhoto:after", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

				<div class="ipsPhotoPanel__text">
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentUsername:before", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="commentUsername" class="ipsEntry__username">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentUsername:inside-start", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

						<h3>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $comment->author(), $comment->warningRef(), NULL, \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Anonymous' ) ? $comment->isAnonymous() : FALSE );
$return .= <<<IPSCONTENT
</h3>
						
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Anonymous' ) AND !$comment->isAnonymous() ):
$return .= <<<IPSCONTENT

							<span class="ipsEntry__group">
IPSCONTENT;

$return .= \IPS\Member\Group::load( $comment->author()->member_group_id )->formattedName;
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Anonymous' ) and $comment->isAnonymous() and \IPS\Member::loggedIn()->modPermission('can_view_anonymous_posters') ):
$return .= <<<IPSCONTENT

							<a data-ipshover data-ipshover-width="370" data-ipshover-onclick href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url( 'reveal' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><span class="ipsAnonymousIcon" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_anonymously_reveal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span></a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentUsername:inside-end", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentUsername:after", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

					<p class="ipsPhotoPanel__secondary">
						
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->shareableUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
{$comment->dateLine()}
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\EditHistory' )  and $comment->editLine() ):
$return .= <<<IPSCONTENT

							(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edited_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
			
IPSCONTENT;

if ( $comment->author()->hasHighlightedReplies() || ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and $comment->isHighlighted() ) OR ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\MetaData' ) and $comment->isFeatured() )  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentBadges:before", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="commentBadges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentBadges:inside-start", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->author()->hasHighlightedReplies() ):
$return .= <<<IPSCONTENT

						<li><span class="ipsBadge ipsBadge--highlightedGroup">
IPSCONTENT;

$return .= \IPS\Member\Group::load( $comment->author()->member_group_id )->name;
$return .= <<<IPSCONTENT
</span></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\MetaData' ) and $comment->isFeatured()  ):
$return .= <<<IPSCONTENT

					<li><span class="ipsBadge ipsBadge--positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_featured_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and $comment->isHighlighted() ):
$return .= <<<IPSCONTENT

						<li><span class="ipsBadge ipsBadge--popular">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_popular_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentBadges:inside-end", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentBadges:after", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $item->commentMultimodActions() ) ):
$return .= <<<IPSCONTENT

				<input type="checkbox" name="multimod[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" data-role="moderation" data-actions="
IPSCONTENT;

if ( $comment->canSplit() ):
$return .= <<<IPSCONTENT
split merge
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) and $comment->hidden() === -1 AND $comment->canUnhide() ):
$return .= <<<IPSCONTENT
unhide
IPSCONTENT;

elseif ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) and $comment->hidden() === 1 AND $comment->canUnhide() ):
$return .= <<<IPSCONTENT
approve
IPSCONTENT;

elseif ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) and $comment->canHide() ):
$return .= <<<IPSCONTENT
hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $comment->canDelete() ):
$return .= <<<IPSCONTENT
delete
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-state="
IPSCONTENT;

if ( $comment->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			{$comment->menu()}
			
IPSCONTENT;

if ( $comment->author()->member_id ):
$return .= <<<IPSCONTENT

				<!-- Expand mini profile -->
				<button class="ipsEntry__topButton ipsEntry__topButton--profile" type="button" aria-controls="mini-profile-comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-expanded="false" data-ipscontrols aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'author_stats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->miniProfile( $comment, $comment->$idField, 'comment' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</header>
	<div class="ipsEntry__post">
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('mod_see_warn') and $comment->warning ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentWarned( $comment );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND  $comment->hidden() AND $comment->hidden() != -2 ):
$return .= <<<IPSCONTENT

			<div class="ipsEntry__hiddenMessage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

elseif ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND  $comment->hidden() == -2 ):
$return .= <<<IPSCONTENT

			<div class="ipsEntry__hiddenMessage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->deletedBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND  $comment->hidden() === 1 && $comment->author()->member_id == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<div class="i-margin-bottom_3"><strong class="i-color_warning"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'comment_awaiting_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "comment:before", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="comment" class="ipsRichText ipsRichText--user" data-role="commentContent" data-controller="core.front.core.lightboxedImages">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "comment:inside-start", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

			{$comment->content()}
			
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\EditHistory' ) and $comment->editLine() ):
$return .= <<<IPSCONTENT

				{$comment->editLine()}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "comment:inside-end", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "comment:after", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

	</div>

	
IPSCONTENT;

if ( ( ( ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND $comment->hidden() !== 1 ) || ! \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) ) && \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and $comment->hasReactionBar() ) || ( ( ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND $comment->hidden() === 1 and ( $comment->canUnhide() || $comment->canDelete() ) ) || ! \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) ) && ( $comment->canDelete() ) ) || ( ( ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND $comment->hidden() === 0 )  || ! \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) ) and $item->canComment() and $editorName ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentFooter:before", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="commentFooter" class="ipsEntry__footer">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentFooter:inside-start", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentControls:before", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
<menu class="ipsEntry__controls" data-role="commentControls" data-ips-hook="commentControls">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentControls:inside-start", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND $comment->hidden() === 1 && ( $comment->canUnhide() || $comment->canDelete() ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->canUnhide() ):
$return .= <<<IPSCONTENT

						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('unhide')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--positive" data-action="approveComment"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->canDelete() ):
$return .= <<<IPSCONTENT

						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('delete')->csrf()->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="deleteComment" data-updateondelete="#commentCount"><i class="fa-solid fa-xmark"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->canEdit() || $comment->canSplit() || $comment->canHide() ):
$return .= <<<IPSCONTENT

						<li>
							<a href="#elControlsCommentsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="elControlsCommentsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-appendto="#comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderator_tools', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
							<ul id="elControlsCommentsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="ipsMenu ipsMenu_auto ipsHide">
								
IPSCONTENT;

if ( $comment->canEdit() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $comment->mapped('first') and $comment->item()->canEdit() ):
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->url()->setQueryString( 'do', 'edit' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('edit'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="editComment">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $comment->canSplit() ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('split'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="splitComment" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $item::$title )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split_to_new', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) and $comment->canHide() ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('hide')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( ( ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND $comment->hidden() === 0 ) || ! \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) ) and $item->canComment() and $editorName ):
$return .= <<<IPSCONTENT

						<li data-ipsquote-editor="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editorName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsquote-target="#comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsJS_show">
							<button class="cMultiQuote ipsHide" data-action="multiQuoteComment" data-ipstooltip data-ipsquote-multiquote data-mqid="mq
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'multiquote', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-plus"></i></button>
						</li>
						<li data-ipsquote-editor="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editorName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsquote-target="#comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsJS_show">
							<a href="#" data-action="quoteComment" data-ipsquote-singlequote><i class="fa-solid fa-quote-left" aria-hidden="true"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'quote', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						</li>
						
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

							<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $comment, FALSE );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li class="ipsHide" data-role="commentLoading">
					<span class="ipsLoading ipsLoading--tiny"></span>
				</li>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentControls:inside-end", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
</menu>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentControls:after", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ( ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND $comment->hidden() !== 1 ) || ! \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) ) && \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and $comment->hasReactionBar() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $comment );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentFooter:inside-end", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentFooter:after", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->sharemenu( $comment );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentWrap:inside-end", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/comment", "commentWrap:after", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function commentContainer( $item, $comment ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $comment::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

$itemClassSafe = str_replace( '\\', '_', mb_substr( $comment::$itemClass, 4 ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $comment->isIgnored() ):
$return .= <<<IPSCONTENT

	<div class='ipsEntry ipsEntry--ignored' id='elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ignoreCommentID='elComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ignoreUserID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<i class="fa-solid fa-user-slash"></i> 
IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ignoring_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 <a href='#elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' data-ipsMenu data-ipsMenu-menuID='elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' data-ipsMenu-appendTo='#elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action="ignoreOptions" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_post_ignore_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
		<ul class='ipsMenu ipsHide' id='elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
			<li class='ipsMenu_item' data-ipsMenuValue='showPost'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_this_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li class='ipsMenu_sep'><hr></li>
			<li class='ipsMenu_item' data-ipsMenuValue='stopIgnoring'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore&do=remove&id={$comment->author()->member_id}", null, "ignore", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stop_ignoring_posts_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
			<li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore", null, "ignore", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_ignore_preferences', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		</ul>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<a id='findComment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></a>
<a id='comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></a>
<article id='elComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsEntry js-ipsEntry ipsEntry--simple 
IPSCONTENT;

if ( ( \IPS\IPS::classUsesTrait( $comment, "IPS\Content\Reactable" ) and $comment->isHighlighted() ) ):
$return .= <<<IPSCONTENT
ipsEntry--popular
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $comment->isIgnored() ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $comment->author()->hasHighlightedReplies() ):
$return .= <<<IPSCONTENT
ipsEntry--highlighted
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) AND ( $comment->hidden() OR $item->hidden() == -2 ) ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $comment->author()->hasHighlightedReplies() ):
$return .= <<<IPSCONTENT
data-memberGroup="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->author()->member_group_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->comment( $item, $comment, $item::$formLangPrefix . 'comment', $item::$application, $item::$module, $itemClassSafe );
$return .= <<<IPSCONTENT

</article>
IPSCONTENT;

		return $return;
}

	function commentEditHistory( $editHistory, $comment ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_history_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->edit_log_prune > 0 ):
$return .= <<<IPSCONTENT

	<div class='ipsMessage ipsMessage--info
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
 i-margin-top_3
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->edit_log_prune ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_log_prune_notice', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class="i-padding_3" data-role="commentFeed">
    {$editHistory}
</div>
IPSCONTENT;

		return $return;
}

	function commentEditHistoryRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $rows ) ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

foreach ( $rows as $edit ):
$return .= <<<IPSCONTENT

    <article class='ipsEntry ipsEntry--simple ipsEntry--edit-history'>
        <header class='ipsEntry__header'>
            <div class='ipsEntry__header-align'>
				<div class='ipsPhotoPanel'>
                    <div>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $edit['member'] ), 'mini' );
$return .= <<<IPSCONTENT
</div>
                    <div class='ipsPhotoPanel__text'>
                        <h3 class='ipsPhotoPanel__primary'>
                            
IPSCONTENT;

$return .= \IPS\Member::load( $edit['member'] )->link();
$return .= <<<IPSCONTENT

                        </h3>
                        <p class='ipsPhotoPanel__secondary'>
                            
IPSCONTENT;

$val = ( $edit['time'] instanceof \IPS\DateTime ) ? $edit['time'] : \IPS\DateTime::ts( $edit['time'] );$return .= $val->html();
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

if ( $edit['reason'] ):
$return .= <<<IPSCONTENT

                            <br>
                            
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $edit['reason'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        </p>
                    </div>
                </div>
            </div>
        </header>
        <div class='ipsEntry__post'>
            <div class='ipsRichText'>
                {$edit['new']}
            </div>
        </div>
    </article>
    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


    
IPSCONTENT;

if ( $table->page == $table->pages AND $table->extra instanceof \IPS\Content ):
$return .= <<<IPSCONTENT

    <article class='ipsEntry ipsEntry--simple ipsEntry--edit-history'>
        <header class='ipsEntry__header'>
            <div class='ipsEntry__header-align'>
				<div class='ipsPhotoPanel'>
                    <div>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $table->extra->author(), 'mini' );
$return .= <<<IPSCONTENT
</div>
                    <div class='ipsPhotoPanel__text'>
                        <h3 class='ipsPhotoPanel__primary>
                            {$table->extra->author()->link(  NULL, NULL, \IPS\IPS::classUsesTrait( $table->extra, 'IPS\Content\Anonymous' ) ? $table->extra->isAnonymous() : FALSE )}
                        </h3>
                        <p class='ipsPhotoPanel__secondary'>
                            
IPSCONTENT;

$val = ( $table->extra->mapped('date') instanceof \IPS\DateTime ) ? $table->extra->mapped('date') : \IPS\DateTime::ts( $table->extra->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

                        </p>
                    </div>
                </div>
            </div>
        </header>
        <div class='ipsEntry__post'>
            <div class='ipsRichText'>
                {$edit['old']}
            </div>
        </div>
    </article>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_edit_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function commentEditHistoryTable( $table, $headers, $rows, $quickSearch ) {
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
' data-controller='core.global.core.table' 
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

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

    <div data-role="tableRows" id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
        
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

    </div>
    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_edit_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

	function commentEditLine( $comment, $supportsReason=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<p class='i-color_soft i-font-size_-1 ipsEdited' data-excludequote data-el='edited'>
	<i class="fa-regular fa-pen-to-square i-margin-end_icon"></i> <strong class='i-font-weight_600'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $comment->mapped('edit_time') )->html(FALSE), ( $comment->mapped('edit_member_name') ) ? htmlspecialchars( $comment->mapped('edit_member_name'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) : \IPS\Member::loggedIn()->language()->addToStack('guest')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'date_edited', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</strong>
	
IPSCONTENT;

if ( $supportsReason && $comment->mapped('edit_reason') ):
$return .= <<<IPSCONTENT

		<br>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->mapped('edit_reason'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->edit_log == 2 and ( \IPS\Settings::i()->edit_log_public or \IPS\Member::loggedIn()->modPermission('can_view_editlog') )  ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('editlog'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_history_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</a>
		
IPSCONTENT;

if ( !$comment->mapped('edit_show') AND \IPS\Member::loggedIn()->modPermission('can_view_editlog') ):
$return .= <<<IPSCONTENT

			<br>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'comment_edit_show_anyways', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</p>
IPSCONTENT;

		return $return;
}

	function commentMultimod( $item, $type='comment' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" />

IPSCONTENT;

$method = $type . 'MultimodActions';
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $actions = $item->$method() and \count( $actions ) ):
$return .= <<<IPSCONTENT

	<div class="ipsData__modBar ipsJS_hide" data-role="pageActionOptions">
		<select class="ipsInput ipsInput--select i-basis_300" name="modaction" data-role="moderationAction">
			
IPSCONTENT;

if ( \in_array( 'approve', $actions ) ):
$return .= <<<IPSCONTENT

				<option value='approve' data-icon='check-circle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( 'split_merge', $actions ) ):
$return .= <<<IPSCONTENT

				<option value='split' data-icon='arrows-split-up-and-left'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
				<option value='merge' data-icon='arrows-to-circle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'merge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( 'hide', $actions ) or \in_array( 'unhide', $actions ) ):
$return .= <<<IPSCONTENT

				<optgroup label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-icon='eye' data-action='hide'>
					
IPSCONTENT;

if ( \in_array( 'hide', $actions ) ):
$return .= <<<IPSCONTENT

						<option value='hide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \in_array( 'unhide', $actions ) ):
$return .= <<<IPSCONTENT

						<option value='unhide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unhide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</optgroup>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( 'delete', $actions ) ):
$return .= <<<IPSCONTENT

				<option value='delete' data-icon='trash-can'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function commentMultimodHeader( $item, $container, $type='comment' ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$method = $type . 'MultimodActions';
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $actions = $item->$method() and \count( $actions ) ):
$return .= <<<IPSCONTENT

	<div class="ipsButtonBar__mod">
		<ul class="ipsDataFilters">
			<li>
				<a class="ipsJS_show ipsDataFilters__button" href="#elCheck_menu" id="elCheck" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsAutoCheck data-ipsAutoCheck-context="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active">
					<span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span><i class="fa-solid fa-caret-down"></i>
					<span class='ipsNotification' data-role='autoCheckCount'>0</span>
				</a>
			</li>
		</ul>
		<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="elCheck_menu">
			<li class="ipsMenu_title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
			<li class="ipsMenu_sep"><hr></li>
			<li class="ipsMenu_item" data-ipsMenuValue="hidden"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li class="ipsMenu_item" data-ipsMenuValue="unhidden"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unhidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li class="ipsMenu_item" data-ipsMenuValue="unapproved"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unapproved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		</ul>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function commentRecognized( $comment ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$blurb = $comment->recognizedBlurb();
$return .= <<<IPSCONTENT

<!--Content Recognized -->
<div class="i-background_2 i-padding_2 i-margin-bottom_2 i-border-radius_box">
	<div class="ipsPhotoPanel ipsPhotoPanel--start">
	    
IPSCONTENT;

if ( $badge = $comment->recognized->badge() ):
$return .= <<<IPSCONTENT

	        <span class='ipsUserPhoto ipsUserPhoto--small'>
	            
IPSCONTENT;

if ( $badge->badge_use_image ):
$return .= <<<IPSCONTENT

		            <img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Badges", $badge->image )->url;
$return .= <<<IPSCONTENT
">
		        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		            <img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge->_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		    </span>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<span class='ipsBadge ipsBadge--icon ipsBadge--soft'><i class='fas fa-trophy'></i></span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsPhotoPanel__text">
			<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blurb['main'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
			<p class='i-color_soft i-font-weight_500'>{$blurb['awards']}</p>
			
IPSCONTENT;

if ( $blurb['message'] ):
$return .= <<<IPSCONTENT
<p class='i-margin-top_1 i-font-weight_500'>&quot;<em>{$blurb['message']}</em>&quot;</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function commentsAndReviewsTabs( $content, $id ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox ipsBox--reviews-comments ipsPull' data-controller='core.front.core.commentsWrapper' data-tabsId='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	{$content}
</div>
IPSCONTENT;

		return $return;
}

	function commentTableHeader( $comment, $status ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-flex i-align-items_center i-gap_2'>
	<div class='i-flex_00'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $status->author() );
$return .= <<<IPSCONTENT

	</div>
	<div class='i-flex_11'>
		<p class='i-color_soft i-link-color_inherit'>
			
IPSCONTENT;

$htmlsprintf = array($status->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'status_updated_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

		</p>
		<div class='ipsRichText ipsTruncate_5'>
			{$status->truncated()}
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function commentWarned( $comment ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!-- Moderator warning -->
<div class="ipsModerated i-padding_2 i-margin-bottom_2 i-border-radius_box">
	<div class="ipsPhotoPanel ipsPhotoPanel--start">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $comment->warning->moderator ), 'fluid' );
$return .= <<<IPSCONTENT

		<div class="ipsPhotoPanel__text">
			<strong>
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $comment->warning->moderator )->name, \IPS\Member::load( $comment->warning->member )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_given_post_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong>
			<br>
			<span class='i-color_soft'>
				<strong class='i-font-weight_500'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_reason_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;

$val = "core_warn_reason_{$comment->warning->reason}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &middot; <strong class='i-font-weight_500'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_points_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->warning->points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &middot; <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=view&id={$comment->warning->member}&w={$comment->warning->id}", null, "warn_view", array( $comment->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_warning_details_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class="i-text-decoration_underline" data-ipsDialog data-ipsDialog-size='narrow'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_warning_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</span>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function confirmDelete( $message, $form, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT


<section class='i-text-align_center ipsBox i-padding_3'>
    <br><br>
    <i class='i-font-size_6 fa-solid fa-triangle-exclamation'></i>

    <p class='i-font-size_6'>
        
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </p>

    <p class='i-font-size_2'>
        
IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </p>
    <hr class='ipsHr'>
    {$form}
</section>
IPSCONTENT;

		return $return;
}

	function contentEditLine( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


<p class='i-color_soft i-font-size_-1 ipsEdited' data-excludequote data-el='edited'>
	<i class="fa-regular fa-pen-to-square i-margin-end_icon"></i> <strong class='i-font-weight_600'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $item->mapped('edit_time') )->html(FALSE), htmlspecialchars( $item->mapped('edit_member_name'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'date_edited', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</strong>
	
IPSCONTENT;

if ( $item->mapped('edit_reason') ):
$return .= <<<IPSCONTENT

		<br>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('edit_reason'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->edit_log == 2 and ( \IPS\Settings::i()->edit_log_public or \IPS\Member::loggedIn()->modPermission('can_view_editlog') ) ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('editlog'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</a>
		
IPSCONTENT;

if ( !$item->mapped('edit_show') AND \IPS\Member::loggedIn()->modPermission('can_view_editlog') ):
$return .= <<<IPSCONTENT

			<br>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'comment_edit_show_anyways', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</p>
IPSCONTENT;

		return $return;
}

	function contentItemMessage( $message, $item, $id ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$member = \IPS\Member::load( $message['added_by'] );
$return .= <<<IPSCONTENT


IPSCONTENT;

$class = \get_class( $item );
$return .= <<<IPSCONTENT

<div class="ipsMessage 
IPSCONTENT;

if ( isset( $message['color'] ) ):
$return .= <<<IPSCONTENT
ipsMessage--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $message['color'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsPull">
	
IPSCONTENT;

if ( isset( $message['is_public'] ) AND $message['is_public']  ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'fluid' );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<i class="fa-solid fa-user-shield ipsMessage__icon"></i>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='i-flex i-justify-content_space-between i-flex-wrap_wrap i-gap_2 i-margin-bottom_1'>
		<div>
			
IPSCONTENT;

if ( $member->member_id ):
$return .= <<<IPSCONTENT

				<strong class="i-font-weight_600">
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_item_message', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong>
IPSCONTENT;

if ( isset( $message['date'] )  ):
$return .= <<<IPSCONTENT
<span class='i-color_soft'>, 
IPSCONTENT;

$val = ( $message['date'] instanceof \IPS\DateTime ) ? $message['date'] : \IPS\DateTime::ts( $message['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $message['date'] )  ):
$return .= <<<IPSCONTENT
<span class='i-color_soft'>
IPSCONTENT;

$val = ( $message['date'] instanceof \IPS\DateTime ) ? $message['date'] : \IPS\DateTime::ts( $message['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<ul class='ipsList ipsList--inline i-font-size_-2'>
			
IPSCONTENT;

if ( isset( $message['is_public'] ) AND !$message['is_public']  ):
$return .= <<<IPSCONTENT

				<li>
					<span class='ipsBadge'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_staff_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $item->canOnMessage( 'edit' ) ):
$return .= <<<IPSCONTENT

				<li>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->setQueryString( array( 'do' => 'messageForm', 'meta_id' => $id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class="i-text-decoration_none i-font-weight_600"><i class="fa-regular fa-pen-to-square"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $item->canOnMessage( 'delete' ) ):
$return .= <<<IPSCONTENT

				<li>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->csrf()->setQueryString( array( 'do' => 'messageDelete', 'meta_id' => $id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm class="i-text-decoration_none i-font-weight_600"><i class="fa-regular fa-trash-can"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>		
	</div>
	<div class='ipsRichText'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $message['message'], array('') );
$return .= <<<IPSCONTENT
</div>
</div>
IPSCONTENT;

		return $return;
}

	function contentItemMessages( $messages, $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $messages AS $id => $message ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->contentItemMessage( $message, $item, $id );
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function controlStrip( $buttons ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsControlStrip' data-ipsControlStrip>
	
IPSCONTENT;

foreach ( $buttons as $button ):
$return .= <<<IPSCONTENT

		<li class='ipsControlStrip_button 
IPSCONTENT;

if ( isset( $button['hidden'] ) and $button['hidden'] ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( isset( $button['id'] ) ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<a
				
IPSCONTENT;

if ( isset( $button['link'] ) ):
$return .= <<<IPSCONTENT
href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				title='
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
				data-ipsTooltip
				aria-label="
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
				
				
IPSCONTENT;

if ( isset( $button['class'] ) ):
$return .= <<<IPSCONTENT
class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['data'] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $button['data'] as $k => $v ):
$return .= <<<IPSCONTENT

						data-
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

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['hotkey'] ) ):
$return .= <<<IPSCONTENT

					data-keyAction='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['hotkey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $button['target'] ) ):
$return .= <<<IPSCONTENT

					target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['target'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
					
IPSCONTENT;

if ( $button['target'] == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			>
				<i class='ipsControlStrip_icon fa-solid fa-
IPSCONTENT;

if ( ! stristr( $button['icon'], 'regular' ) ):
$return .= <<<IPSCONTENT
fa-solid 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( mb_substr( $button['icon'], 0, 3 ) !== 'fa-' ):
$return .= <<<IPSCONTENT
fa-
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
				<span class='ipsControlStrip_item'>
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</a>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function coverPhoto( $url, $coverPhoto ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$id = mt_rand();
$return .= <<<IPSCONTENT

<div class='ipsCoverPhoto' data-controller='core.global.core.coverPhoto' data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-coverOffset='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style='--offset:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elCoverPhoto_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

$cfObject = $coverPhoto->object;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

		<div class='ipsCoverPhoto__container'>
			<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCoverPhoto__image' data-action="toggleCoverPhoto" alt='' loading='lazy'>
		</div>
	
IPSCONTENT;

elseif ( ! empty( $cfObject::$coverPhotoDefault ) ):
$return .= <<<IPSCONTENT

		<div class='ipsCoverPhoto__container ipsCoverPhoto__container--default'>
			<div class="ipsFallbackImage" style="--i-empty-image-random--ba-co: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->object->coverPhotoBackgroundColor(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"></div>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='ipsCoverPhoto__container ipsCoverPhoto__container--default'>
			<div class="ipsFallbackImage"></div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $coverPhoto->editable ):
$return .= <<<IPSCONTENT

		<ul class="ipsCoverPhoto__overlay-buttons" data-hideOnCoverEdit>
			<li>
				<a href='#elEditPhoto
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsButton ipsButton--overlay' data-ipsMenu data-ipsMenu-appendTo='#elCoverPhoto_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elEditPhoto
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='coverPhotoOptions'><i class="fa-regular fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_photo_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='ipsMenuCaret'></i></a>
				<ul class='ipsMenu ipsMenu--coverPhoto ipsMenu_auto ipsHide' id='elEditPhoto
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
					
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item ipsHide' data-role='photoEditOption'>
							<a href='#' data-action='positionCoverPhoto'><i class="fa-solid fa-arrows-up-down-left-right"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_photo_reposition', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						</li>
						<li class='ipsMenu_item' data-role='photoEditOption'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'coverPhotoRemove' ) )->csrf()->addRef( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='removeCoverPhoto'><i class="fa-solid fa-trash-can"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_photo_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li class='ipsMenu_item'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'coverPhotoUpload' ) )->addRef( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_photo_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-upload"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_photo_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					</li>
				</ul>
			</li>
		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
{$coverPhoto->overlay}
IPSCONTENT;

		return $return;
}

	function customFieldsDisplay( $author ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $author->contentProfileFields() as $group => $fields ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $fields as $field => $value ):
$return .= <<<IPSCONTENT

		<li data-el='{$field}' data-role='custom-field'>
			{$value}
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function defaultThumb(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsFallbackImage" aria-hidden="true"></div>
IPSCONTENT;

		return $return;
}

	function designersModeBuilding( $html, $title=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<head>
		<title>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->getTitle( $title ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</title>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeMeta(  );
$return .= <<<IPSCONTENT

	<style type="text/css">
		/* ======================================================== */
/* PROGRESS BAR */
@keyframes progress-bar-stripes {
	from { background-position: 40px 0; }
	to { background-position: 0 0; }
}

.ipsProgress {
	width: 50%;
	margin: auto;
	height: 26px;
	overflow: hidden;
	background: rgb(156,156,156);
	background: linear-gradient(to bottom, rgba(156,156,156,1) 0%,rgba(180,180,180,1) 100%);
	border-radius: min(var(--i-design-radius, 4px));
	box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}
	.ipsProgress.ipsProgress--animated .ipsProgress__progress {
		background-color: #5490c0;
		background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
		background-size: 40px 40px;
	}

.ipsProgress__progress {
	float: left;
	width: 0;
	height: 100%;
	font-size: 12px;
	font-weight: bold;
	color: #ffffff;
	text-align: center;
	text-shadow: 1px 1px 0 rgba(0, 0, 0, 0.25);
	background: #5490c0;
	position: relative;
	white-space: nowrap;
	line-height: 26px;
}
	
	.ipsProgress--warning .ipsProgress__progress {
		background: #8c3737;
	}

	.ipsProgress > span:first-child {
		padding-left: 7px;
	}

	.ipsProgress__progress[data-progress]:after {
		position: absolute;
		right: 5px;
		top: 0;
		line-height: 32px;
		color: #fff;
		content: attr(data-progress);
		display: block;
		font-weight: bold;
	}
	
	span[data-role=message] {
		text-align: center;
		display: block;
		margin: 8px;
		font-family: Helvetica;
	}

	</style>
	</head>
	<body class="ipsApp ipsApp_front ipsJS_none ipsLayout_noBackground">
		{$html}
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT

	</body>
</html>
IPSCONTENT;

		return $return;
}

	function embedComment( $item, $comment, $url, $image=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$useImage = NULL;
$return .= <<<IPSCONTENT

<div class='ipsRichEmbed ipsRichEmbed--comment'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			<div>
				
IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$useImage = $image;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $contentImage = $item->contentImages(1) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$attachType = key( $contentImage[0] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$useImage = \IPS\File::get( $attachType, $contentImage[0][ $attachType ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( $useImage ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
						<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $useImage->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				<div class='i-padding_3'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item );
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>

		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \count( $comment->reactions() ) ):
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

	function embedExternal( $output, $js ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->htmlDataAttributes(  );
$return .= <<<IPSCONTENT

	data-ips-embed
    style="color-scheme: light dark; background-color: var(--i-background_1);"
>
	<head>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->prefersColorSchemeLoad(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT


		<script>
			var ipsDebug = 
IPSCONTENT;

if ( ( \IPS\IN_DEV and \IPS\DEV_DEBUG_JS ) or \IPS\DEBUG_JS ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
;
		</script>

		
IPSCONTENT;

if ( \is_array( $js ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $js as $jsInclude ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$filename = \IPS\Http\Url::external( $jsInclude[0] );
$return .= <<<IPSCONTENT

				<script src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $filename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></script>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</head>
	<body class='unloaded' data-role='externalEmbed'>
		<div id='ipsEmbed'>
			{$output}
		</div>
		<div id='ipsEmbedLoading'>
			<span></span>
		</div>
	</body>
</html>

IPSCONTENT;

		return $return;
}

	function embedInternal( $html, $js ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->htmlDataAttributes(  );
$return .= <<<IPSCONTENT

	data-ips-embed
>
	<head>
		<title>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</title>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->prefersColorSchemeLoad(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeMeta(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT


		<script>
			var ipsDebug = 
IPSCONTENT;

if ( ( \IPS\IN_DEV and \IPS\DEV_DEBUG_JS ) or \IPS\DEBUG_JS ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
;
		</script>

		
IPSCONTENT;

if ( \is_array( $js ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $js as $jsInclude ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$filename = \IPS\Http\Url::external( $jsInclude[0] );
$return .= <<<IPSCONTENT

				<script src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $filename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></script>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</head>
	<body class='unloaded ipsApp ipsApp_front 
IPSCONTENT;

foreach ( \IPS\Output::i()->bodyClasses as $class ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' data-role='internalEmbed' 
IPSCONTENT;

if ( \IPS\Dispatcher::i()->application ):
$return .= <<<IPSCONTENT
data-pageapp='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Dispatcher::i()->application->directory, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Dispatcher::i()->module ):
$return .= <<<IPSCONTENT
data-pagemodule='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Dispatcher::i()->module->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-pagecontroller='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Dispatcher::i()->controller, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<div id='ipsEmbed'>
			{$html}
		</div>
		<div id='ipsEmbedLoading'>
			<span></span>
		</div>
		
IPSCONTENT;

$return .= \IPS\Output::i()->endBodyCode;
$return .= <<<IPSCONTENT

	</body>
</html>

IPSCONTENT;

		return $return;
}

	function embedItem( $item, $url, $image=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--item'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $item, $item->mapped('title'), $item->mapped('date'), $url );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$useImage = $image;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( $contentImage = $item->contentImages(1) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$attachType = key( $contentImage[0] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$useImage = \IPS\File::get( $attachType, $contentImage[0][ $attachType ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $useImage ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
			<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $useImage->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
		</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		
IPSCONTENT;

if ( $desc = $item->truncated(TRUE) ):
$return .= <<<IPSCONTENT

			<div class='ipsRichEmbed__snippet'>
				{$desc}
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

	function embedReview( $item, $review, $url, $image=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$useImage = NULL;
$return .= <<<IPSCONTENT

<div class='ipsRichEmbed ipsRichEmbed--review'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $review, $item->mapped('title'), $review->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			<div>
				
IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$useImage = $image;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $contentImage = $item->contentImages(1) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$attachType = key( $contentImage[0] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$useImage = \IPS\File::get( $attachType, $contentImage[0][ $attachType ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( $useImage ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
						<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $useImage->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				<div class='i-padding_3'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item );
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'veryLarge', $review->mapped('rating') );
$return .= <<<IPSCONTENT
 
		
IPSCONTENT;

if ( $review->mapped('votes_total') ):
$return .= <<<IPSCONTENT

			<p>{$review->helpfulLine()}</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		<div class='ipsRichEmbed__snippet'>
			{$review->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $review, 'IPS\Content\Reactable' ) and \count( $review->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline i-margin-top_2'>
				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $review, TRUE, 'small' );
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

	function error( $title, $message, $code, $extra, $member, $faultyPluginOrApp=NULL, $httpStatusCode=500 ) {
		$return = '';
		$return .= <<<IPSCONTENT


<section id='elError' class='ipsBox ipsBox--padding i-text-align_center'>
	
IPSCONTENT;

if ( ! \in_array( (int) $httpStatusCode, [404, 403] ) ):
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-circle-exclamation i-font-size_5 i-margin-bottom_2'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    <p class='i-color_soft i-font-size_3 i-font-weight_500'>
IPSCONTENT;

if ( \in_array( (int) $httpStatusCode, [404, 403] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "something_went_wrong_{$httpStatusCode}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'something_went_wrong', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>
	<div id='elErrorMessage' class='i-font-size_4 i-font-weight_600 i-margin-top_2'>
		{$message}
	</div>
	
IPSCONTENT;

if ( ( \IPS\IN_DEV or $member->isAdmin() ) and $extra ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $faultyPluginOrApp ):
$return .= <<<IPSCONTENT

		<p class="i-margin-top_3">
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $faultyPluginOrApp, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div class="i-margin-top_3">
			<h3 class="i-font-weight_600 i-color_soft i-font-size_-1 i-text-transform_uppercase">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_technical_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			<textarea class="ipsInput ipsInput--text" rows="13" style="font-family: monospace;">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $extra, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
			<p class="i-font-size_-1 i-color_soft">
				
IPSCONTENT;

if ( $member->isAdmin() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_technical_details_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $member->hasAcpRestriction( 'core', 'support', 'system_logs_view' ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_technical_details_logs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( \IPS\IN_DEV ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_technical_details_dev', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<p class='i-margin-top_3 i-color_soft'>
        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_page_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
    </p>

	
IPSCONTENT;

if ( ( \IPS\Member::loggedIn()->isAdmin() and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'support', 'get_support' ) ) || ( \IPS\Member::loggedIn()->canUseContactUs() and !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'contact' ) ) || !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

		<ul class='ipsButtons i-margin-top_3'>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->isAdmin() and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'support', 'get_support' ) ):
$return .= <<<IPSCONTENT

				<li>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_support', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
						<i class="fa-solid fa-lock"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_support', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</a>
				</li>
			
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->canUseContactUs() and !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'contact' ) ):
$return .= <<<IPSCONTENT

				<li>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=contact&controller=contact", null, "contact", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact_admin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact_admin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				<li>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</section>
IPSCONTENT;

		return $return;
}

	function favico(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->icons_favicon ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$file = \IPS\File::get( 'core_Icons', \IPS\Settings::i()->icons_favicon );
$return .= <<<IPSCONTENT

	<link rel='icon' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' type="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getMimeType( $file->originalFilename ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$favicon = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 100 100"><style>rect{fill:hsl(0 0% 0% / .4)}@media(prefers-color-scheme:dark){rect{fill:hsl(0 0% 100% / .2);} }</style><rect width="100" height="100" rx="20" fill="hsl(0 0% 0% / .4)"></rect><text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" fill="white" font-size="70" font-family="Arial" font-weight="500">'. substr(\IPS\Settings::i()->board_name, 0, 1) .'</text></svg>');
$return .= <<<IPSCONTENT

	<link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $favicon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function featuredComment( $comment, $id, $commentLang='__defart_comment' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $comment['comment'] ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$idField = $comment['comment']::$databaseColumnId;
$return .= <<<IPSCONTENT

	<div class='i-padding_3 ipsEntry ipsEntry--recommended' data-commentID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<div class='ipsColumns i-gap_3'>
			<div class='ipsColumns__primary'>
				<div class="ipsPhotoPanel i-margin-bottom_2">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $comment['comment']->author(), 'fluid', $comment['comment']->warningRef() );
$return .= <<<IPSCONTENT

					<div class="ipsPhotoPanel__text">
						<div class="ipsPhotoPanel__primary">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $comment['comment']->author(), $comment['comment']->warningRef() );
$return .= <<<IPSCONTENT
</div>
						<div class="ipsPhotoPanel__secondary"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment['comment']->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->get( $commentLang )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_this_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' rel="nofollow" data-action='goToComment'>{$comment['comment']->dateLine()}</a></div>
					</div>
				</div>
				<div class='ipsRichText ipsTruncate_2'>{$comment['comment']->truncated( TRUE )}</div>
				
IPSCONTENT;

if ( $comment['note'] ):
$return .= <<<IPSCONTENT

					<div class='ipsEntry__recommendedNote'>
						<p class='ipsRichText'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment['note'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

if ( isset( $comment['featured_by'] ) ):
$return .= <<<IPSCONTENT

							<p class='i-color_soft i-margin-top_2 i-link-color_inherit'>
IPSCONTENT;

$htmlsprintf = array($comment['featured_by']->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recommended_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

elseif ( isset( $comment['featured_by'] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$htmlsprintf = array($comment['featured_by']->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recommended_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='i-basis_auto i-align-self_center'>
				
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment['comment'], 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $comment['comment'] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function featuredComments( $comments, $url, $titleLang='recommended_replies', $commentLang='__defart_comment' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<section data-controller='core.front.core.recommendedComments' data-url='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsBox ipsRecommendedComments 
IPSCONTENT;

if ( !\count( $comments ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	<div data-role="recommendedComments">
		<header class='ipsBox__header'>
			<h2>
IPSCONTENT;

$val = "{$titleLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'topic-featured-posts' );
$return .= <<<IPSCONTENT

		</header>
		
IPSCONTENT;

if ( \count( $comments ) ):
$return .= <<<IPSCONTENT

			<div class="ipsCarousel" id="topic-featured-posts" tabindex="0">
				
IPSCONTENT;

foreach ( $comments AS $id => $comment ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->featuredComment( $comment, $id, $commentLang );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</section>
IPSCONTENT;

		return $return;
}

	function findComment( $header, $item, $comment ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $comment::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

$itemClassSafe = str_replace( '\\', '_', mb_substr( $comment::$itemClass, 4 ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ! \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	<h1 class='ipsTitle ipsTitle--h3'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->setQueryString( array( 'do' => 'findComment', 'comment' => $comment->$idField ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $header, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h1>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<article id='elComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsEntry js-ipsEntry ipsEntry--find-comment 
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and $comment->isHighlighted() ):
$return .= <<<IPSCONTENT
ipsEntry--popular
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $comment->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	<div id='comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap' data-controller='core.front.core.comment' data-feedid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commentApp='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment::$application, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-commentType='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item::$module, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-commentID="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-quoteData='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( array('userid' => $comment->author()->member_id, 'username' => $comment->author()->name, 'timestamp' => $comment->mapped('date'), 'contentapp' => $comment::$application, 'contenttype' => $item::$module, 'contentclass' => $itemClassSafe, 'contentid' => $item->id, 'contentcommentid' => $comment->$idField) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsEntry__content js-ipsEntry__content'

IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\IntersectionViewTracking' ) AND $hash=$comment->getViewTrackingHash() ):
$return .= <<<IPSCONTENT

data-view-hash="{$hash}"
data-view-tracking-data="
IPSCONTENT;

$return .= base64_encode(json_encode( $comment->getViewTrackingData() ));
$return .= <<<IPSCONTENT
"

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
		<header class='ipsEntry__header'>
			<div class='ipsEntry__header-align'>
				<div class='ipsPhotoPanel'>
					<div>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $comment->author(), 'mini', $comment->warningRef() );
$return .= <<<IPSCONTENT
</div>
					<div class='ipsPhotoPanel__text'>
						<h3 class='ipsPhotoPanel__primary'>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $comment->author(), $comment->warningRef() );
$return .= <<<IPSCONTENT

						</h3>
						<p class='ipsPhotoPanel__secondary'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->url()->setQueryString( array( 'do' => 'findComment', 'comment' => $comment->id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_inherit'>{$comment->dateLine()}</a>
							
IPSCONTENT;

if ( $comment->editLine() ):
$return .= <<<IPSCONTENT

								&middot; {$comment->editLine()}
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $comment->hidden() ):
$return .= <<<IPSCONTENT

								&middot; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</p>
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('mod_see_warn') and $comment->warning ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentWarned( $comment );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		</header>
		<div class='ipsEntry__post'>
			
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and $comment->isHighlighted() ):
$return .= <<<IPSCONTENT

				<strong class='ipsEntry__popularFlag' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_popular_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-heart'></i></strong>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div data-role='commentContent' class='ipsRichText' data-controller='core.front.core.lightboxedImages'>
				
IPSCONTENT;

if ( $comment->hidden() === 1 && $comment->author()->member_id == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

					<strong class='i-color_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'comment_awaiting_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				{$comment->content()}
			</div>
			
IPSCONTENT;

if ( $comment->hidden() !== 1 && \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

				<br>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $comment );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</article>
IPSCONTENT;

		return $return;
}

	function follow( $app, $area, $id, $count ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-followApp='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-followArea='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $area, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-followID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.front.core.followButton'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->followButton( $app, $area, $id, $count );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function followButton( $app, $area, $id, $count ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->following( $app, $area, $id ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "following:before", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="following" class="ipsButton ipsButton--follow ipsButton--follow-active" data-role="followButton" data-following="true">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "following:inside-start", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=follow&follow_app={$app}&follow_area={$area}&follow_id={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'following_this_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ipshover data-ipshover-cache="false" data-ipshover-onclick><i class="fa-solid fa-bell"></i><span class="ipsButton__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'following_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="ipsMenuCaret"></i></a>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_view_followers'] ):
$return .= <<<IPSCONTENT

			<a class="ipsButton__segment" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=followers&follow_app={$app}&follow_area={$area}&follow_id={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'followers_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'who_follows_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $count );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "following:inside-end", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "following:after", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
	
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "notfollowing:before", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="notfollowing" class="ipsButton ipsButton--follow" data-role="followButton" data-following="false">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "notfollowing:inside-start", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=follow&follow_app={$app}&follow_area={$area}&follow_id={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_this_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ipshover data-ipshover-cache="false" data-ipshover-onclick><i class="fa-regular fa-bell"></i><span class="ipsButton__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="ipsMenuCaret"></i></a>
			
IPSCONTENT;

if ( $count > 0 and \IPS\Member::loggedIn()->group['g_view_followers'] ):
$return .= <<<IPSCONTENT

				<a class="ipsButton__segment" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=followers&follow_app={$app}&follow_area={$area}&follow_id={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'followers_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'who_follows_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $count );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "notfollowing:inside-end", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "notfollowing:after", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "guest:before", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT
<a data-ips-hook="guest" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" class="ipsButton ipsButton--follow" data-role="followButton" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_sign_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "guest:inside-start", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT

		<span>
			<i class="fa-regular fa-bell"></i>
			<span class="ipsButton__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'followers', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</span>
		
IPSCONTENT;

if ( $count > 0 and \IPS\Member::loggedIn()->group['g_view_followers'] ):
$return .= <<<IPSCONTENT

			<span class="ipsButton__segment">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $count );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "guest:inside-end", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/followButton", "guest:after", [ $app,$area,$id,$count ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function footer(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<ul class="ipsColorSchemeChanger" data-controller="core.front.core.colorScheme">
	<li data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_light_mode', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
		<button data-ips-prefers-color-scheme="light">
			<i class="fa-regular fa-lightbulb"></i>
			<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_light_mode', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</button>
	</li>
	<li data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_dark_mode', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
		<button data-ips-prefers-color-scheme="dark">
			<i class="fa-regular fa-moon"></i>
			<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_dark_mode', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</button>
	</li>
	<li data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_system', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
		<button data-ips-prefers-color-scheme="system">
			<i class="fa-solid fa-circle-half-stroke"></i>
			<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_system', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</button>
	</li>
</ul>

IPSCONTENT;

if ( \IPS\Settings::i()->site_social_profiles AND $links = json_decode( \IPS\Settings::i()->site_social_profiles, TRUE ) AND \count( $links ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "socialProfiles:before", [  ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="socialProfiles" class="ipsSocialIcons">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "socialProfiles:inside-start", [  ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->siteSocialProfiles(  );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "socialProfiles:inside-end", [  ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "socialProfiles:after", [  ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<hr>

IPSCONTENT;

if ( ( \IPS\Settings::i()->site_online || \IPS\Member::loggedIn()->group['g_access_offline'] ) and ( \IPS\Dispatcher::i()->application instanceof \IPS\Application AND \IPS\Dispatcher::i()->application->canAccess() ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "links:before", [  ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="links" class="ipsFooterLinks">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "links:inside-start", [  ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$languages = \IPS\Lang::getEnabledLanguages();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $languages ) > 1 ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "languages:before", [  ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="languages">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "languages:inside-start", [  ] );
$return .= <<<IPSCONTENT

				<a href="#elNavLang_menu" id="elNavLang" data-ipsmenu data-ipsmenu-above>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'language', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-angle-down"></i></a>
				<form action="
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=language" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "language", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
" method="post" id="elChangeLanguage">
					<input type="hidden" name="ref" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( base64_encode( (string) \IPS\Widget\Request::i()->url() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				</form>
				<ul id="elNavLang_menu" class="ipsMenu ipsMenu_selectable ipsHide">
				
IPSCONTENT;

foreach ( $languages as $id => $lang  ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->id == $id || ( $lang->default && \IPS\Member::loggedIn()->language === 0 ) ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						<button type="submit" form="elChangeLanguage" name="id" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $lang->get__icon() ):
$return .= <<<IPSCONTENT
<i class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->get__icon(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $lang->default ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'default', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</button>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "languages:inside-end", [  ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "languages:after", [  ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !(\IPS\Member::loggedIn()->isEditingTheme() and \IPS\Theme::i()->edit_in_progress) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$themes = \IPS\Theme::getThemesWithAccessPermission();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $themes ) > 1  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "themes:before", [  ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="themes">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "themes:inside-start", [  ] );
$return .= <<<IPSCONTENT

					<form action="
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=theme" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "theme", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
" method="post" id="elChangeTheme">
						<input type="hidden" name="ref" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( base64_encode( (string) \IPS\Widget\Request::i()->url() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					</form>
					<a href="#elNavTheme_menu" id="elNavTheme" data-ipsmenu data-ipsmenu-above>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'skin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-angle-down"></i></a>
					<ul id="elNavTheme_menu" class="ipsMenu ipsMenu_selectable ipsHide">
					
IPSCONTENT;

foreach ( $themes as $id => $set  ):
$return .= <<<IPSCONTENT

						<li class="ipsMenu_item
IPSCONTENT;

if ( \IPS\Theme::i()->id == $id ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							<button type="submit" form="elChangeTheme" name="id" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$set->_title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $set->is_default ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'default', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</button>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "themes:inside-end", [  ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "themes:after", [  ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->privacy_type != "none" ):
$return .= <<<IPSCONTENT

			<li><a href="
IPSCONTENT;

if ( \IPS\Settings::i()->privacy_type == "internal" ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=privacy", null, "privacy", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Settings::i()->privacy_link;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'privacy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canUseContactUs() and !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'contact' ) ):
$return .= <<<IPSCONTENT

			<li><a rel="nofollow" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=contact&controller=contact", null, "contact", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \IPS\Settings::i()->contact_type != 'contact_redirect' and (\IPS\Member::loggedIn()->member_id or \IPS\Helpers\Form\Captcha::supportsModal()) ):
$return .= <<<IPSCONTENT
data-ipsdialog 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id OR !\IPS\Settings::i()->contact_email_verify ):
$return .= <<<IPSCONTENT
data-ipsdialog-remotesubmit data-ipsdialog-flashmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact_sent_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<li><a rel="nofollow" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=cookies", null, "cookies", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookies_about', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

if ( \count( \IPS\Output::i()->rssFeeds ) ):
$return .= <<<IPSCONTENT

			<li>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rssMenu(  );
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "links:inside-end", [  ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/footer", "links:after", [  ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<p class="ipsCopyright">
	<span class="ipsCopyright__user">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copyright_line_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
	
IPSCONTENT;

if ( !$licenseData = \IPS\IPS::licenseKey() or !isset($licenseData['products']['copyright']) or !$licenseData['products']['copyright'] ):
$return .= <<<IPSCONTENT
	<a rel="nofollow" title="Invision Community" href="#">Powered by Invision Community</a><br><a href='https://nullforums.net' style='display:none'>Invision Community Support Forums</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</p>
IPSCONTENT;

		return $return;
}

	function formattedInlineStyle( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \is_array($item) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $item['feature_color'] ):
$return .= <<<IPSCONTENT
style="--i-featured: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['feature_color'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
; --i-featured-text: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['text_color'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$column = $item::$featureColumnName;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $item->$column ):
$return .= <<<IPSCONTENT
style="--i-featured: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->$column, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
; --i-featured-text: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_featureTextColor, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function formattedTitle( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \is_array($item) ):
$return .= <<<IPSCONTENT

<span class="ipsContainerTitle" 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->formattedInlineStyle( $item );
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

<span class="ipsContainerTitle" 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->formattedInlineStyle( $item );
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function genericBlock( $content, $title='', $classes=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $title ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->pageHeader( $title );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='
IPSCONTENT;

if ( $classes ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	{$content}
</div>
IPSCONTENT;

		return $return;
}

	function globalTemplate( $title,$html,$location=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->htmlDataAttributes(  );
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "head:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<head data-ips-hook="head">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "head:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

		<meta charset="utf-8">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "title:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<title data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "title:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->getTitle( $title ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "title:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</title>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "title:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$theme = \IPS\Theme::i();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->isEditingTheme() and $theme->edit_in_progress ):
$return .= <<<IPSCONTENT

			<script>
				if (window.self === window.top) {
					window.location = '
IPSCONTENT;

$return .= \IPS\Http\Url::internal( "app=core&module=system&controller=themeeditor", null, "theme_editor", array(), 0 );
$return .= <<<IPSCONTENT
';
				}
			</script>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->core_datalayer_enabled ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->includeDataLayer(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->prefersColorSchemeLoad(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->loadGuestColorScheme(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->ga_enabled ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Settings::i()->ga_code;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->matomo_enabled ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Settings::i()->matomo_code;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->googletag_enabled AND !(\IPS\Settings::i()->core_datalayer_use_gtm AND \IPS\Settings::i()->core_datalayer_enabled) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Settings::i()->googletag_head_code;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->maxmind_key and \IPS\Settings::i()->maxmind_id and \IPS\Settings::i()->maxmind_tracking_code ):
$return .= <<<IPSCONTENT

			<script>
				(function() {
					let mmapiws = window.__mmapiws = window.__mmapiws || {};
					mmapiws.accountId = "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->maxmind_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
";
					let loadDeviceJs = function() {
						let element = document.createElement('script');
						element.async = true;
						element.src = 'https://device.maxmind.com/js/device.js';
						document.body.appendChild(element);
					};
					window.addEventListener('load', loadDeviceJs, false);
				})();
			</script>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

			<link rel="preload" as="font" type="font/woff2" href="
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "applications/core/interface/fontawesome/webfonts/fa-solid-900.woff2", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
" crossorigin="anonymous">
			
IPSCONTENT;

if ( \IPS\Theme::i()->getLayoutValue('global_view_mode') == 'default' ):
$return .= <<<IPSCONTENT

				<link rel="preload" as="script" media="(min-width:980px)" href="
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "applications/core/interface/theme/navigationMoreMenu.js", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeMeta(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->favico(  );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "head:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</head>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "head:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "body:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<body data-ips-hook="body" class="ipsApp ipsApp_front 
IPSCONTENT;

foreach ( \IPS\Output::i()->bodyClasses as $class ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \IPS\Output::i()->globalControllers ):
$return .= <<<IPSCONTENT
data-controller="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', \IPS\Output::i()->globalControllers ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( \IPS\Output::i()->inlineMessage ) ):
$return .= <<<IPSCONTENT
data-message="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->inlineMessage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-pageapp="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location['app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-pagelocation="front" data-pagemodule="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location['module'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-pagecontroller="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location['controller'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->id ) ):
$return .= <<<IPSCONTENT
data-pageid="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( (int) \IPS\Widget\Request::i()->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( \IPS\Dispatcher::i()->dispatcherController ) AND !\IPS\Dispatcher::i()->dispatcherController->isContentPage  ):
$return .= <<<IPSCONTENT
data-nocontent
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Output::i()->pageName ):
$return .= <<<IPSCONTENT
data-pagename="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->pageName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "body:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getHeaderAndFooter( 'header' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->googletag_enabled AND !(\IPS\Settings::i()->core_datalayer_use_gtm AND \IPS\Settings::i()->core_datalayer_enabled) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Settings::i()->googletag_noscript_code;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->core_datalayer_enabled ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->includeDataLayerBody(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "layout:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<div class="ipsLayout" data-ips-hook="layout">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "layout:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Theme::i()->getLayoutValue('global_view_mode') == 'side' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !\in_array( 'ipsLayout_minimalNoHome', \IPS\Output::i()->bodyClasses ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->navigationPanel(  );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="ipsLayout__app">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->announcementTop(  );
$return .= <<<IPSCONTENT

				<a href="#ipsLayout_main" class="ipsSkipToContent">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'jump_to_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "mobileHeader:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="mobileHeader" class="ipsMobileHeader ipsResponsive_header--mobile">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "mobileHeader:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->logo( 'mobile' );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->mobileNavHeader(  );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "mobileHeader:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "mobileHeader:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !\in_array( 'ipsLayout_minimal', \IPS\Output::i()->bodyClasses ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->breadcrumb( 'mobile' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Theme::i()->getLayoutValue('global_view_mode') == 'side' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !\in_array( 'ipsLayout_minimalNoHome', \IPS\Output::i()->bodyClasses ) ):
$return .= <<<IPSCONTENT

						<div class="ipsNavPanelBreadcrumbs ipsResponsive_header--desktop">
							<div class="ipsWidth ipsHeader__align">
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->breadcrumb( 'top' );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userBar(  );
$return .= <<<IPSCONTENT

							</div>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "header:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<header data-ips-hook="header" class="ipsHeader ipsResponsive_header--desktop">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "header:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

						<div class="ipsHeader__top" 
IPSCONTENT;

if ( !(array_intersect(["1","2","3"], $theme->theme_editor_data['header']))  ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
							<div class="ipsWidth ipsHeader__align">
								<div data-ips-header-position="1" class="ipsHeader__start">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 1 );
$return .= <<<IPSCONTENT
</div>
								<div data-ips-header-position="2" class="ipsHeader__center">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 2 );
$return .= <<<IPSCONTENT
</div>
								<div data-ips-header-position="3" class="ipsHeader__end">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 3 );
$return .= <<<IPSCONTENT
</div>
							</div>
						</div>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "primaryHeader:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="primaryHeader" class="ipsHeader__primary" 
IPSCONTENT;

if ( !(array_intersect(["4","5","6"], $theme->theme_editor_data['header']))  ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "primaryHeader:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

							<div class="ipsWidth ipsHeader__align">
								<div data-ips-header-position="4" class="ipsHeader__start">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 4 );
$return .= <<<IPSCONTENT
</div>
								<div data-ips-header-position="5" class="ipsHeader__center">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 5 );
$return .= <<<IPSCONTENT
</div>
								<div data-ips-header-position="6" class="ipsHeader__end">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 6 );
$return .= <<<IPSCONTENT
</div>
							</div>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "primaryHeader:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "primaryHeader:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "secondaryHeader:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="secondaryHeader" class="ipsHeader__secondary" 
IPSCONTENT;

if ( !(array_intersect(["7","8","9"], $theme->theme_editor_data['header']))  ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "secondaryHeader:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

							<div class="ipsWidth ipsHeader__align">
								<div data-ips-header-position="7" class="ipsHeader__start">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 7 );
$return .= <<<IPSCONTENT
</div>
								<div data-ips-header-position="8" class="ipsHeader__center">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 8 );
$return .= <<<IPSCONTENT
</div>
								<div data-ips-header-position="9" class="ipsHeader__end">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 9 );
$return .= <<<IPSCONTENT
</div>
							</div>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "secondaryHeader:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "secondaryHeader:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "header:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</header>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "header:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "main:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<main data-ips-hook="main" class="ipsLayout__main" id="ipsLayout__main" tabindex="-1">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "main:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

					<div class="ipsWidth ipsWidth--main-content">
						<div class="ipsContentWrap">
							
IPSCONTENT;

if ( \IPS\Theme::i()->getLayoutValue('global_view_mode') == 'default' ):
$return .= <<<IPSCONTENT

								<div class="ipsHeaderExtra ipsResponsive_header--desktop" 
IPSCONTENT;

if ( !(array_intersect(["10","11","12"], $theme->theme_editor_data['header']))  ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<div data-ips-header-position="10" class="ipsHeaderExtra__start">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 10 );
$return .= <<<IPSCONTENT
</div>
									<div data-ips-header-position="11" class="ipsHeaderExtra__center">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 11 );
$return .= <<<IPSCONTENT
</div>
									<div data-ips-header-position="12" class="ipsHeaderExtra__end">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->globalTemplateHeaderLogic( 12 );
$return .= <<<IPSCONTENT
</div>
								</div>
								<script src='
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "applications/core/interface/theme/navigationMoreMenu.js", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
'></script>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->updateWarning(  );
$return .= <<<IPSCONTENT

							<div class="ipsLayout__columns">
								
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "primaryColumn:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<section data-ips-hook="primaryColumn" class="ipsLayout__primary-column">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "primaryColumn:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\core\Advertisement::loadByLocation( 'ad_global_header' );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->acknowledgeWarning( \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( !\in_array('ipsLayout_minimal', \IPS\Output::i()->bodyClasses ) and !\IPS\Member::loggedIn()->members_bitoptions['profile_completion_dismissed'] and $nextStep = \IPS\Member::loggedIn()->nextProfileStep() ):
$return .= <<<IPSCONTENT

										<div class="ipsBox ipsBox--padding">
											
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->profileNextStep( $nextStep, true );
$return .= <<<IPSCONTENT

										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->widgetContainer( 'header', 'horizontal' );
$return .= <<<IPSCONTENT

									{$html}
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->widgetContainer( 'footer', 'horizontal' );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "primaryColumn:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</section>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "primaryColumn:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->sidebar(  );
$return .= <<<IPSCONTENT

							</div>
							
IPSCONTENT;

if ( !\in_array( 'ipsLayout_minimalNoHome', \IPS\Output::i()->bodyClasses ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->breadcrumb( 'bottom' );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->msg_show_notification and $message = \IPS\core\Messenger\Conversation::latestUnreadMessage() ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->inlineMessage( $message );
endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\core\Advertisement::loadByLocation( 'ad_global_footer' );
$return .= <<<IPSCONTENT

						</div>
					</div>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "main:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</main>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "main:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !\in_array( 'ipsLayout_minimalNoHome', \IPS\Output::i()->bodyClasses ) ):
$return .= <<<IPSCONTENT

					<div class="ipsFooter">
						<aside class="ipsFooter__widgets ipsWidth">
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->widgetContainer( 'globalfooter', 'vertical' );
$return .= <<<IPSCONTENT

						</aside>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "footer:before", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
<footer data-ips-hook="footer" class="ipsFooter__footer">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "footer:inside-start", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

							<div class="ipsWidth">
								<div class="ipsFooter__align">
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->footer(  );
$return .= <<<IPSCONTENT

								</div>
							</div>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "footer:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</footer>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "footer:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id and \IPS\Settings::i()->guest_terms_bar ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->guestTermsBar( base64_encode( \IPS\Request::i()->url() ) );
endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->mobileFooterBar(  );
$return .= <<<IPSCONTENT

			</div>	
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "layout:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "layout:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $alert = \IPS\Output::i()->alert ):
$return .= <<<IPSCONTENT

			{$alert}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->searchDialog(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $_SESSION['live_meta_tags'] ) and $_SESSION['live_meta_tags'] and \IPS\Member::loggedIn()->isAdmin() ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->metaTagEditor(  );
endif;
$return .= <<<IPSCONTENT

		<!--ipsQueryLog-->
		<!--ipsCachingLog-->
		
IPSCONTENT;

$return .= \IPS\Output::i()->endBodyCode;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->fb_pixel_enabled and \IPS\Settings::i()->fb_pixel_id and $noscript = \IPS\core\Facebook\Pixel::i()->noscript() ):
$return .= <<<IPSCONTENT

			<noscript>
			{$noscript}
			</noscript>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->custom_body_code ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Settings::i()->custom_body_code;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getHeaderAndFooter( 'footer' );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "body:inside-end", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT
</body>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/globalTemplate", "body:after", [ $title,$html,$location ] );
$return .= <<<IPSCONTENT

</html>
IPSCONTENT;

		return $return;
}

	function globalTemplateHeaderLogic( $position ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$theme = \IPS\Theme::i();
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $theme->theme_editor_data['header'] as $type => $pos ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $pos == $position ):
$return .= <<<IPSCONTENT

		<div data-ips-header-content='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<!-- 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 -->
			
IPSCONTENT;

if ( $type == 'logo' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->logo( 'desktop' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !\in_array( 'ipsLayout_minimalNoHome', \IPS\Output::i()->bodyClasses ) ):
$return .= <<<IPSCONTENT

			    
IPSCONTENT;

if ( $type == 'navigation' ):
$return .= <<<IPSCONTENT

				    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->navBar(  );
$return .= <<<IPSCONTENT

			    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			    
IPSCONTENT;

if ( $type == 'user' ):
$return .= <<<IPSCONTENT

				    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userBar(  );
$return .= <<<IPSCONTENT

			    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			    
IPSCONTENT;

if ( $type == 'breadcrumb' ):
$return .= <<<IPSCONTENT

				    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->breadcrumb( 'top' );
$return .= <<<IPSCONTENT

			    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			    
IPSCONTENT;

if ( $type == 'search' ):
$return .= <<<IPSCONTENT

				    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->searchDialogTrigger(  );
$return .= <<<IPSCONTENT

			    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function groupPostedBadges( $groups, $lang = '', $extraClasses = '' ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $groups as $group ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$groupNames[] = $group->name;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

$list = \IPS\Member::loggedIn()->language()->formatList( $groupNames );
$return .= <<<IPSCONTENT


<ul class="ipsData__groups 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $extraClasses, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $lang ):
$return .= <<<IPSCONTENT
data-ipsTooltip title='
IPSCONTENT;

$val = "{$lang}"; $htmlsprintf = array($list); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

foreach ( $groups as $group ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $group->g_icon  ):
$return .= <<<IPSCONTENT

			<li><img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $group->g_icon )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'></li>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li><span class='ipsBadge ipsBadge--neutral'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function guestCommentTeaser( $item, $isReview=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div>
	<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

if ( \IPS\Login::registrationType() != 'disabled' ):
$return .= <<<IPSCONTENT

		<div class='i-text-align_center i-padding_3 cGuestTeaser'>
			
IPSCONTENT;

if ( $isReview ):
$return .= <<<IPSCONTENT

				<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_review_title_reg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<p class='i-color_soft i-margin-top_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_review_desc_reg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_title_reg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<p class='i-color_soft i-margin-top_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_desc_reg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
			<div class='ipsBox i-padding_3 i-margin-top_3'>
				<div class='ipsFluid i-basis_400'>
					<div class='cGuestTeaser_left'>
						<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						<p class='i-color_soft i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_account_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'redirect' ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_reg_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small' target="_blank" rel="noopener">
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small' 
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'normal' ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_account_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
					<div class='cGuestTeaser_right'>
						<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_signin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						<p class='i-color_soft i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_signin_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 )->addRef((string) $item->url() . '#replyForm'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-remoteVerify="false" data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_signin_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_signin_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
				</div>
			</div>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='i-text-align_center i-padding_3'>
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_title_noreg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<p class='i-color_soft i-margin-top_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_desc_noreg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<br>
			<br>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 )->addRef((string) $item->url() . '#replyForm'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-remoteVerify="false" data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_signin_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--secondary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'teaser_signin_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function guestTermsBar( $currentUrl ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Widget\Request::i()->cookieConsentEnabled() OR ( !\IPS\Member::loggedIn()->member_id AND !\IPS\Widget\Request::i()->cookieConsentEnabled() )  ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$termsLang = \IPS\Member::loggedIn()->language()->addToStack( 'terms_of_use' );
$return .= <<<IPSCONTENT


IPSCONTENT;

$privacyLang = \IPS\Member::loggedIn()->language()->addToStack( 'terms_privacy' );
$return .= <<<IPSCONTENT


IPSCONTENT;

$glLang = \IPS\Member::loggedIn()->language()->addToStack( 'guidelines' );
$return .= <<<IPSCONTENT


IPSCONTENT;

$termsUrl = (string) \IPS\Http\Url::internal( 'app=core&module=system&controller=terms', 'front', 'terms' );
$return .= <<<IPSCONTENT


IPSCONTENT;

$terms = "<a href='$termsUrl'>$termsLang</a>";
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->privacy_type == 'internal' ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$privacyUrl = (string) \IPS\Http\Url::internal( 'app=core&module=system&controller=privacy', 'front', 'privacy' );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$privacyUrl = \IPS\Settings::i()->privacy_link;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$privacy = "<a href='$privacyUrl'>$privacyLang</a>";
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->gl_type == 'internal' ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$glUrl = (string) \IPS\Http\Url::internal( 'app=core&module=system&controller=guidelines', 'front', 'guidelines' );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$glUrl = \IPS\Settings::i()->gl_link;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$guidelines = "<a href='$glUrl'>$glLang</a>";
$return .= <<<IPSCONTENT


IPSCONTENT;

$cookiesUrl = (string) \IPS\Http\Url::internal( 'app=core&module=system&controller=cookies', 'front', 'cookies' );
$return .= <<<IPSCONTENT


IPSCONTENT;

$cookies = \IPS\Member::loggedIn()->language()->addToStack( 'cookies_message', FALSE, array( 'sprintf' => array( $cookiesUrl, $cookiesUrl ) ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

$status = (int) !\IPS\Member::loggedIn()->optionalCookiesAllowed;
$return .= <<<IPSCONTENT

<div id='elGuestTerms' class='i-padding_2 ipsJS_hide' data-role='
IPSCONTENT;

if ( \IPS\Widget\Request::i()->cookieConsentEnabled() ):
$return .= <<<IPSCONTENT
cookieConsentBar
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
guestTermsBar
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-controller='core.front.core.guestTerms'>
	<div class='ipsWidth cGuestTerms'>
		<div class='ipsColumns i-align-items_center i-gap_3'>
			<div class='ipsColumns__primary'>
				<h2 class='ipsTitle ipsTitle--h4 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_terms_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<p class='i-color_soft'>
IPSCONTENT;

$htmlsprintf = array($terms, $privacy, $guidelines, $cookies); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_terms_bar_text_value', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			<div class='ipsColumns__secondary'>
                
IPSCONTENT;

if ( \IPS\Widget\Request::i()->cookieConsentEnabled() ):
$return .= <<<IPSCONTENT

                <form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=cookies&do=cookieConsentToggle&ref={$currentUrl}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post">
                    <input type="hidden" name='ref' value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currentUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					<div class="ipsButtons">
						<button type="submit" name="status" value="1" class='ipsButton ipsButton--positive'><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'accept_cookies', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
						<button type="submit" name="status" value="0" class='ipsButton ipsButton--inherit'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookieconstent_reject', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					</div>
                </form>
                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class="ipsButtons">
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=terms&do=dismiss&ref={$currentUrl}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' rel='nofollow' class='ipsButton ipsButton--inherit' data-action="dismissTerms"><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_terms_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </div>
		</div>
	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function helpfulLog( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li class='ipsPhotoPanel ipsPhotoPanel_mini'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $row['member_given'] ), 'mini' );
$return .= <<<IPSCONTENT

		<div>
			<h3 class='ipsTruncate ipsTruncate_line'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( \IPS\Member::load( $row['member_given'] ) );
$return .= <<<IPSCONTENT
</h3>
		</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function helpfulLogTable( $table, $headers, $rows, $quickSearch ) {
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
' data-controller='core.global.core.table' 
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
>

	<div class="ipsButtonBar ipsButtonBar--top 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="tablePagination">
		<div class='ipsButtonBar__pagination'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

		</div>
	</div>

	
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

		<ol class='ipvGrid i-basis_300 i-padding_2 
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

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rows_in_table', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
	<div class="ipsButtonBar ipsButtonBar--bottom 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="tablePagination">
		<div class='ipsButtonBar__pagination'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function htmlDataAttributes(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


data-ips-path="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->url()->data['path'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
data-ips-scheme='
IPSCONTENT;

$return .= \IPS\Theme::i()->getCurrentCSSScheme();
$return .= <<<IPSCONTENT
'
data-ips-scheme-active='
IPSCONTENT;

$return .= \IPS\Theme::i()->getCurrentCSSScheme();
$return .= <<<IPSCONTENT
'
data-ips-scheme-default='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-default-scheme' );
$return .= <<<IPSCONTENT
'
data-ips-theme="
IPSCONTENT;

$return .= \IPS\Theme::i()->id;
$return .= <<<IPSCONTENT
"


IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

    data-ips-member='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
    data-ips-member-group='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->member_group_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
    
IPSCONTENT;

if ( \IPS\Member::loggedIn()->mgroup_others ):
$return .= <<<IPSCONTENT

        data-ips-member-secondary-groups='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace(',', ' ', \IPS\Member::loggedIn()->mgroup_others), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    data-ips-guest

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


data-ips-theme-setting-change-scheme='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-change-scheme' );
$return .= <<<IPSCONTENT
'
data-ips-theme-setting-link-panels='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-link-panels' );
$return .= <<<IPSCONTENT
'
data-ips-theme-setting-nav-bar-icons='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-nav-bar-icons' );
$return .= <<<IPSCONTENT
'
data-ips-theme-setting-mobile-icons-location='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-mobile-icons-location' );
$return .= <<<IPSCONTENT
'
data-ips-theme-setting-mobile-footer-labels='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-mobile-footer-labels' );
$return .= <<<IPSCONTENT
'
data-ips-theme-setting-sticky-sidebar='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-sticky-sidebar' );
$return .= <<<IPSCONTENT
'
data-ips-theme-setting-flip-sidebar='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-flip-sidebar' );
$return .= <<<IPSCONTENT
'

data-ips-layout='
IPSCONTENT;

$return .= \IPS\Theme::i()->getLayoutValue('global_view_mode');
$return .= <<<IPSCONTENT
'


IPSCONTENT;

if ( (int) \IPS\Settings::i()->editor_paragraph_padding === 0 ):
$return .= <<<IPSCONTENT

	data-ips-setting-compact-richtext

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Member::loggedIn()->isEditingTheme() ):
$return .= <<<IPSCONTENT

    data-theme-editor-active
    data-theme-editorurl="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=themeeditor", null, "theme_editor", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"
    data-layoutoptions='
IPSCONTENT;

$return .= json_encode( \IPS\Theme::i()->getAvailableLayoutOptionsForThemeEditor() );
$return .= <<<IPSCONTENT
'

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function includeDataLayer(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!-- IPS Data Layer Start -->
<script>
    /* IPS Configuration */
    
IPSCONTENT;

$return .= \IPS\core\DataLayer::i()->jsConfig;
$return .= <<<IPSCONTENT


    /* IPS Context */
    
IPSCONTENT;

$return .= \IPS\core\DataLayer::i()->jsContext;
$return .= <<<IPSCONTENT


    /* IPS Events */
    
IPSCONTENT;

$return .= \IPS\core\DataLayer::i()->jsEvents;
$return .= <<<IPSCONTENT

</script>

IPSCONTENT;

\IPS\core\DataLayer::i()->clearCache();/* Safe to clear data layer session cache here since we just loaded the events */
$return .= <<<IPSCONTENT


IPSCONTENT;

$handlerInserts = \IPS\core\DataLayer\Handler::loadForTemplates();
$return .= <<<IPSCONTENT

{$handlerInserts['headInserts']}

<!-- IPS Data Layer End -->
IPSCONTENT;

		return $return;
}

	function includeDataLayerBody(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!-- IPS Data Body Start -->

IPSCONTENT;

$handlerInserts = \IPS\core\DataLayer\Handler::loadForTemplates();
$return .= <<<IPSCONTENT

{$handlerInserts['bodyInserts']}

<!-- IPS Data Layer Body End -->
IPSCONTENT;

		return $return;
}

	function inlineMessage( $message ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elInlineMessage' class='i-padding_3' title='
IPSCONTENT;

$sprintf = array($message->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_inline_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
	<div class='ipsPhotoPanel'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $message->author(), 'medium' );
$return .= <<<IPSCONTENT

		<div>
			<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $message->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong><br>
			<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_inline_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $message->date instanceof \IPS\DateTime ) ? $message->date : \IPS\DateTime::ts( $message->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
			<br>
			<div class='ipsTruncate_3'>
				{$message->post}
			</div>
			<hr class='ipsHr'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $message->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_inline_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

if ( \IPS\Member::loggedIn()->msg_count_new > 1 ):
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger", null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_inline_view_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $message->canReportOrRevoke() === TRUE ):
$return .= <<<IPSCONTENT
 &nbsp;&nbsp; <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $message->url('report'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span class='ipsResponsive_showPhone'><i class='fa-solid fa-flag'></i></span><span class='ipsResponsive_hidePhone'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_reply', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function itemIcon( $iconInfo ) {
		$return = '';
		$return .= <<<IPSCONTENT


<span class="ipsIndicator 
IPSCONTENT;

if ( $iconInfo['size'] ):
$return .= <<<IPSCONTENT
ipsIndicator--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $iconInfo['size'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $iconInfo['type'] ):
$return .= <<<IPSCONTENT
data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $iconInfo['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
></span>
IPSCONTENT;

		return $return;
}

	function loadGuestColorScheme(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

/*
$return .= <<<IPSCONTENT
<!-- For guests: Apply either light, dark or system color scheme from cookie. We need to do this due to page caching for guests. -->
IPSCONTENT;

*/
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() and !\IPS\Member::loggedIn()->member_id and \IPS\Theme::i()->getCssVariableFromKey('set__i-change-scheme') == "1" ):
$return .= <<<IPSCONTENT

	<script>
		(() => {
			function getCookie(n) {
				let v = `; \${document.cookie}`, parts = v.split(`; \${n}=`);
				if (parts.length === 2) return parts.pop().split(';').shift();
			}
			
IPSCONTENT;

if ( \IPS\Widget\Request::i()->cookieConsentEnabled() ):
$return .= <<<IPSCONTENT

				const c = getCookie('
IPSCONTENT;

if ( \IPS\COOKIE_PREFIX !== NULL ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\COOKIE_PREFIX, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
cookie_consent');
				if(!c) return;
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			const s = getCookie('
IPSCONTENT;

if ( \IPS\COOKIE_PREFIX !== NULL ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\COOKIE_PREFIX, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
scheme_preference');
			if(!s || s === document.documentElement.getAttribute("data-ips-scheme-active")) return;
			if(s === "system"){
				document.documentElement.setAttribute('data-ips-scheme',(window.matchMedia('(prefers-color-scheme:dark)').matches)?'dark':'light');
			} else {
				document.documentElement.setAttribute("data-ips-scheme",s);
			}
			document.documentElement.setAttribute("data-ips-scheme-active",s);
		})();
	</script>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function loginPopup( $login ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elUserSignIn_menu' class='ipsMenu ipsMenu_auto ipsHide'>
	<form accept-charset='utf-8' method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<input type="hidden" name="ref" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( base64_encode( \IPS\Widget\Request::i()->url() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<div data-role="loginForm">
			
IPSCONTENT;

$usernamePasswordMethods = $login->usernamePasswordMethods();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $usernamePasswordMethods and $buttonMethods ):
$return .= <<<IPSCONTENT

				<div class='ipsColumns ipsColumns--lines'>
					<div class='ipsColumns__primary' id='elUserSignIn_internal'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->loginPopupForm( $login );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsColumns__secondary i-basis_280'>
						<div class='i-padding_3' id='elUserSignIn_external'>
							<div class=''>
								
IPSCONTENT;

if ( \count( $buttonMethods ) > 1 ):
$return .= <<<IPSCONTENT

									<p class='i-text-align_center'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_with_these', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></p>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<div class='i-grid i-gap_2'>
									
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

										<div>
											{$method->button()}
										</div>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</div>
							</div>
						</div>
					</div>
				</div>
			
IPSCONTENT;

elseif ( $usernamePasswordMethods ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->loginPopupForm( $login );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( $buttonMethods ):
$return .= <<<IPSCONTENT

				<div class="i-grid i-gap_2 cLogin_popupSingle">
					
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

						<div>
							{$method->button()}
						</div>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function loginPopupForm( $login ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="">
	<h4 class="ipsTitle ipsTitle--h3 i-padding_2 i-padding-bottom_0">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'login', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
	<ul class='ipsForm ipsForm--vertical ipsForm--login-popup'>
		<li class="ipsFieldRow ipsFieldRow--noLabel ipsFieldRow--fullWidth">
			<label class="ipsFieldRow__label" for="login_popup_email">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
			<div class="ipsFieldRow__content">
                <input type="email" class='ipsInput ipsInput--text' placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" name="auth" autocomplete="email" id='login_popup_email'>
			</div>
		</li>
		<li class="ipsFieldRow ipsFieldRow--noLabel ipsFieldRow--fullWidth">
			<label class="ipsFieldRow__label" for="login_popup_password">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
			<div class="ipsFieldRow__content">
				<input type="password" class='ipsInput ipsInput--text' placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" name="password" autocomplete="current-password" id='login_popup_password'>
			</div>
		</li>
		<li class="ipsFieldRow ipsFieldRow--checkbox">
			<input type="checkbox" name="remember_me" id="remember_me_checkbox_popup" value="1" checked class="ipsInput ipsInput--toggle">
			<div class="ipsFieldRow__content">
				<label class="ipsFieldRow__label" for="remember_me_checkbox_popup">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remember_me', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				<div class="ipsFieldRow__desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remember_me_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			</div>
		</li>
		<li class="ipsSubmitRow">
			<button type="submit" name="_processLogin" value="usernamepassword" class="ipsButton ipsButton--primary i-width_100p">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'login', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password != 'disabled' ):
$return .= <<<IPSCONTENT

				<p class="i-color_soft i-link-color_inherit i-font-weight_500 i-font-size_-1 i-margin-top_2">
					
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password == 'redirect' ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_forgot_password_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=lostpass", null, "lostpassword", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Helpers\Form\Captcha::supportsModal() ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function logo( $position ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/logo", "logo:before", [ $position ] );
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
" data-ips-hook="logo" class="ipsLogo ipsLogo--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $position, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" accesskey="1">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/logo", "logo:inside-start", [ $position ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $position === 'desktop' or $position === 'side' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->logoImg( 'front', 'light', 'logo-light', 'desktop' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->logoImg( 'front-dark', 'dark', 'logo-dark', 'desktop' );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->logoImg( 'mobile', 'light', 'mobile-logo-light', 'mobile' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->logoImg( 'mobile-dark', 'dark', 'mobile-logo-dark', 'mobile' );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

  	<div class="ipsLogo__text">
		<span class="ipsLogo__name" data-ips-theme-text="set__i-logo-text">
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-logo-text' );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

if ( \IPS\Theme::i()->getCssVariableFromKey('set__i-logo-slogan') != " " or \IPS\Member::loggedIn()->isEditingTheme() ):
$return .= <<<IPSCONTENT

			<span class="ipsLogo__slogan" data-ips-theme-text="set__i-logo-slogan">
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'set__i-logo-slogan' );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/logo", "logo:inside-end", [ $position ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/logo", "logo:after", [ $position ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function logoImg( $key='front', $mode='light', $settingName='logo-light', $size='desktop' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( \IPS\Theme::i()->logo[$key]['url'] ) AND \IPS\Theme::i()->logo[$key]['url'] !== null  ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$logo = \IPS\File::get( 'core_Theme', \IPS\Theme::i()->logo[$key]['url'] )->url;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$height = \IPS\Theme::i()->logo[$key]['img_height'] ?? '';
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$width = \IPS\Theme::i()->logo[$key]['img_width'] ?? '';
$return .= <<<IPSCONTENT

    <picture class='ipsLogo__image ipsLogo__image--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mode, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
        <source srcset="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=" 
IPSCONTENT;

if ( $size === 'desktop' ):
$return .= <<<IPSCONTENT
media="(max-width: 979px)"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
media="(min-width: 980px)"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
        <img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $logo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $width ):
$return .= <<<IPSCONTENT
width="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $height ):
$return .= <<<IPSCONTENT
height="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 alt='
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
' data-ips-theme-image='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $settingName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
    </picture>

IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->isEditingTheme() ):
$return .= <<<IPSCONTENT

    <picture class='ipsLogo__image ipsLogo__image--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mode, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' hidden>
        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=" alt='
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
' data-ips-theme-image='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $settingName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
    </picture>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function memberAssignment( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsPhotoPanel'>
    <span class='ipsUserPhoto ipsUserPhoto--tiny'><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'></span>
    <div>
        <strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong><br>
        <span class='i-color_soft'>{$member->groupName}</span>
    </div>
</div>
IPSCONTENT;

		return $return;
}

	function memberEmailBlockedMessage( $email ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div>
    <p class="i-margin-bottom_1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
    <span class="memberBlockedEmailWrap i-color_negative" ><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_email_blocked_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
</div>

IPSCONTENT;

		return $return;
}

	function metaTagEditor(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div id='elMetaTagEditor' class='ipsToolbox ipsScrollbar' data-controller="core.front.system.metaTagEditor" data-defaultPageTitle='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->defaultPageTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<form accept-charset='utf-8' method='post' action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=metatags&do=save", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsForm>
		<h3 class='ipsToolbox_sectionTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'live_meta_tag_editor', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>

		<input type='hidden' name='meta_url' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->metaTagsUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">

		
IPSCONTENT;

foreach ( \IPS\Output::i()->autoMetaTags as $name => $content ):
$return .= <<<IPSCONTENT

			<input type='hidden' name='defaultMetaTag[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


		<h4 class='ipsToolbox_sectionTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'metatags_page_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<ul class="ipsForm ipsForm--vertical">
			<li class='ipsFieldRow'><input name='meta_tag_title' type='text' class="ipsInput ipsInput--text" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->metaTagsTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></li>
		</ul>

		<h4 class='ipsToolbox_sectionTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_tags_custom_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<ul class='ipsToolbox__list' id='elMetaTagEditor_customTags'>
			<li class='i-text-align_center i-color_soft 
IPSCONTENT;

if ( \count( \IPS\Output::i()->customMetaTags ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='noCustomMetaTagsMessage'><em>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_tags_none_custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em></li>
			
IPSCONTENT;

if ( \count( \IPS\Output::i()->customMetaTags ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( \IPS\Output::i()->customMetaTags as $name => $content ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $content !== NULL ):
$return .= <<<IPSCONTENT

						<li data-role='metaTagRow'>
							<ul class='ipsForm ipsForm--vertical'>
								<li class='ipsFieldRow'>
									<div class='i-flex i-gap_2'>
										<select name='meta_tag_name[]' data-role='metaTagChooser' class="ipsInput ipsInput--select i-flex_11">
											<option value='keywords' 
IPSCONTENT;

if ( $name == 'keywords' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_keywords', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
											<option value='description' 
IPSCONTENT;

if ( $name == 'description' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_description', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
											<option value='robots' 
IPSCONTENT;

if ( $name == 'robots' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_robots', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
											<option value='other' 
IPSCONTENT;

if ( !\in_array( $name, array( 'keywords', 'description', 'robots' ) ) ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_other', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
										</select>
										<button type="button" class="i-flex_00 ipsButton ipsButton--small ipsButton--negative"  data-action='deleteMeta' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-trash'></i></button>
									</div>
								</li>
								<li class='ipsFieldRow ipsFieldRow--fullWidth
IPSCONTENT;

if ( \in_array( $name, array( 'keywords', 'description', 'robots' ) ) ):
$return .= <<<IPSCONTENT
 ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='metaTagName'>
									<input name='meta_tag_name_other[]' type='text' class="ipsInput ipsInput--text" value="
IPSCONTENT;

if ( !\in_array( $name, array( 'keywords', 'description', 'robots' ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'metatags_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								</li>
								<li class='ipsFieldRow ipsFieldRow--fullWidth'>
									<input name='meta_tag_content[]' type='text' class="ipsInput ipsInput--text" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'metatags_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								</li>
							</ul>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>

		<a href='#' class='ipsJS_show ipsButton ipsButton--secondary ipsButton--wide ipsButton--small i-margin-bottom_3' data-action='addMeta'><i class='fa-solid fa-plus'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_another_meta_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>

		<h4 class='ipsToolbox_sectionTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_tags_default_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<ul id='elMetaTagEditor_defaultTags'>
			
IPSCONTENT;

if ( \count( \IPS\Output::i()->metaTags ) !== \count( \IPS\Output::i()->customMetaTags ) ):
$return .= <<<IPSCONTENT

				<li class='i-padding_3 i-opacity_6'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_tags_automatic_notice', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</li>
				
IPSCONTENT;

if ( \count( \IPS\Output::i()->customMetaTags ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( \IPS\Output::i()->customMetaTags as $name => $content ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $content === NULL ):
$return .= <<<IPSCONTENT

							<li data-role='metaTagRow'>
								<ul class='ipsForm ipsForm--vertical'>
									<li class='ipsFieldRow'>
										<div class='i-flex i-gap_2'>
											<div class='i-flex_11 i-color_soft'>
												
IPSCONTENT;

$sprintf = array($name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_tag_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

											</div>
											<button type="button" class="i-flex_00 ipsButton ipsButton--small ipsButton--soft"  data-action='restoreMeta' data-tag='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_tag_restore', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-rotate-left'></i></button>
										</div>
									</li>
								</ul>
							</li>
						
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

foreach ( \IPS\Output::i()->metaTags as $name => $content ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !\array_key_exists( $name, \IPS\Output::i()->customMetaTags ) AND $name != 'title' ):
$return .= <<<IPSCONTENT

						<li data-role='metaTagRow'>
							<ul class='ipsForm ipsForm--vertical'>
								<li class='ipsFieldRow'>
									<div class='i-flex i-gap_2'>
										<select name='meta_tag_name[]' data-role='metaTagChooser' class="ipsInput ipsInput--select i-flex_11">
											<option value='keywords' 
IPSCONTENT;

if ( $name == 'keywords' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_keywords', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
											<option value='description' 
IPSCONTENT;

if ( $name == 'description' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_description', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
											<option value='robots' 
IPSCONTENT;

if ( $name == 'robots' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_robots', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
											<option value='other' 
IPSCONTENT;

if ( !\in_array( $name, array( 'keywords', 'description', 'robots' ) ) ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_other', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
										</select>
										<button type="button" class="i-flex_00 ipsButton ipsButton--small ipsButton--negative" data-action='deleteDefaultMeta' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-trash'></i></button>
									</div>
								</li>
								<li class='ipsFieldRow ipsFieldRow--fullWidth
IPSCONTENT;

if ( \in_array( $name, array( 'keywords', 'description', 'robots' ) ) ):
$return .= <<<IPSCONTENT
 ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='metaTagName'>
									<input name='meta_tag_name_other[]' type='text' class="ipsInput ipsInput--text" value="
IPSCONTENT;

if ( !\in_array( $name, array( 'keywords', 'description', 'robots' ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'metatags_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								</li>
								<li class='ipsFieldRow ipsFieldRow--fullWidth'>
									<input name='meta_tag_content[]' type='text' class="ipsInput ipsInput--text" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'metatags_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								</li>
							</ul>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>

		<div class='i-flex i-align-items_center i-justify-content_center' id='elMetaTagEditor_submit'>
			<div class="ipsButtons">
				<button class='ipsButton ipsButton--positive' role='button' type='submit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=metatags&do=end" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'end_metatags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</form>
	<ul class='ipsHide'>
		<li class='ipsHide' data-role='metaTemplate'>
			<ul class='ipsForm ipsForm--vertical'>
				<li class='ipsFieldRow'>
					<div class='i-flex i-gap_2'>
						<select name='meta_tag_name[]' class="ipsInput ipsInput--select i-flex_11" data-role='metaTagChooser'>
							<option value='keywords'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_keywords', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value='description'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_description', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value='robots'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_robots', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value='other'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_other', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
						</select>
						<button type="button" class="i-flex_00 ipsButton ipsButton--small ipsButton--negative" data-action='deleteMeta' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-trash'></i></button>
					</div>
				</li>
				<li class='ipsFieldRow ipsFieldRow--fullWidth ipsHide' data-role='metaTagName'>
					<input name='meta_tag_name_other[]' type='text' class="ipsInput ipsInput--text" value="" placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'metatags_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
				</li>
				<li class='ipsFieldRow ipsFieldRow--fullWidth'>
					<input name='meta_tag_content[]' type='text' class="ipsInput ipsInput--text" value="" placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'metatags_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
				</li>
			</ul>
		</li>
		<li class='ipsHide' data-role='metaDefaultDeletedTemplate'>
			<ul class='ipsForm ipsForm--vertical'>
				<li class='ipsFieldRow'>
					<div class='i-flex i-gap_2'>
						<div class='i-flex_11 i-color_soft' data-role='metaDeleteMessage'>
							
						</div>
						<button type="button" class="i-flex_00 ipsButton ipsButton--small ipsButton--soft"  data-action='restoreMeta' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'meta_tag_restore', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-rotate-left'></i></button>
					</div>
				</li>
			</ul>
		</li>
	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function miniProfile( $content, $id, $type = NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- Mini profile -->

IPSCONTENT;

if ( $content->author()->member_id ):
$return .= <<<IPSCONTENT

	<div id="mini-profile-
IPSCONTENT;

if ( $type ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ips-hidden-animation="slide-fade" hidden class="ipsEntry__profile-row">
		<ul class="ipsEntry__profile">
			<li>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserStats:before", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="postUserStats" class="ipsEntry__authorStats ipsEntry__authorStats--mini-profile">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserStats:inside-start", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $content, 'IPS\Content\Anonymous' ) AND !$content->isAnonymous() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\Member\Group::load( $content->author()->member_group_id )->g_icon  ):
$return .= <<<IPSCONTENT

							<li data-i-el="group-icon"><img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $content->author()->group['g_icon'] )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy"></li>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<li data-i-el="group" hidden>
IPSCONTENT;

$return .= \IPS\Member\Group::load( $content->author()->member_group_id )->formattedName;
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li data-i-el="posts">
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) )  ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$content->author()->member_id}&do=content", null, "profile_content", array( $content->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow">
								<i class="fa-regular fa-comments" aria-hidden="true"></i>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $content->author()->member_posts );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$pluralize = array( $content->author()->member_posts ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posts_no_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span>
								<i class="fa-regular fa-comments" aria-hidden="true"></i>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $content->author()->member_posts );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$pluralize = array( $content->author()->member_posts ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posts_no_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
					
IPSCONTENT;

if ( $content->author()->canHaveAchievements() and \IPS\core\Achievements\Rank::show() AND ( \count( \IPS\core\Achievements\Rank::getStore() ) && $content->author()->rank() ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\core\Achievements\Rank::getStore() && $rank = $content->author()->rank() ):
$return .= <<<IPSCONTENT

							<li data-i-el="rank">
								<span>
									<i class="fa-solid fa-ranking-star"></i>
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									<span class="i-font-weight_normal i-opacity_6">(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['pos'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['max'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
								</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $content->author_solved_count ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserSolutions:before", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="postUserSolutions" data-i-el="solutions">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserSolutions:inside-start", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) )  ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$content->author()->member_id}&do=solutions", null, "profile_solutions", array( $content->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow">
									<i class="fa-solid fa-circle-check" aria-hidden="true"></i>
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $content->author_solved_count );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span>
									<i class="fa-solid fa-circle-check" aria-hidden="true"></i>
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $content->author_solved_count );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserSolutions:inside-end", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserSolutions:after", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $content->author()->canHaveAchievements() and \IPS\core\Achievements\Badge::show() AND \IPS\core\Achievements\Badge::getStore() ):
$return .= <<<IPSCONTENT

						<li data-i-el="badges">
							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$content->author()->member_id}&do=badges", null, "profile_badges", array( $content->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="badgeLog" title="
IPSCONTENT;

$sprintf = array($content->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_badges', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
								<i class="fa-solid fa-award"></i>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $content->author()->badgeCount() );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badges', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_show_profile ):
$return .= <<<IPSCONTENT

						<li data-i-el="reputation">
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$content->author()->member_id}&do=reputation", null, "profile_reputation", array( $content->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="repLog" title="
IPSCONTENT;

$sprintf = array($content->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $content->author()->pp_reputation_points );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span>
									<i class="fa-solid fa-trophy"></i>
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $content->author()->pp_reputation_points );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Settings::i()->warn_on and !$content->author()->inGroup( explode( ',', \IPS\Settings::i()->warn_protected ) ) and ( \IPS\Member::loggedIn()->modPermission('mod_see_warn') or ( \IPS\Settings::i()->warn_show_own and \IPS\Member::loggedIn()->member_id == $content->author()->member_id ) ) ):
$return .= <<<IPSCONTENT

						<li data-i-el="warning">
							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&id={$content->author()->member_id}", null, "warn_list", array( $content->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_all_warnings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-remoteverify="false" data-ipsdialog-remotesubmit="false" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($content->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_warnings', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
								<i class="fa-regular fa-thumbs-down"></i>
								
IPSCONTENT;

$pluralize = array( $content->author()->warn_level ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_warn_level', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_see_emails') ):
$return .= <<<IPSCONTENT

						<li data-i-el="email">
							<a href="mailto:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->author()->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_this_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
								<i class="fa-regular fa-envelope"></i>
								<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->author()->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserStats:inside-end", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserStats:after", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT

			</li>
			<li>
				<!-- Custom profile fields -->
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserCustomFields:before", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="postUserCustomFields" class="ipsEntry__authorFields">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserCustomFields:inside-start", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->customFieldsDisplay( $content->author() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserCustomFields:inside-end", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/miniProfile", "postUserCustomFields:after", [ $content,$id,$type ] );
$return .= <<<IPSCONTENT

			</li>
		</ul>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function mobileFooterBar(   ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Theme::i()->getCssVariableFromKey('set__i-mobile-icons-location') == "footer" ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/mobileFooterBar", "mobileFooter:before", [  ] );
$return .= <<<IPSCONTENT
<nav data-ips-hook="mobileFooter" class="ipsMobileFooter" id="ipsMobileFooter">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/mobileFooterBar", "mobileFooter:inside-start", [  ] );
$return .= <<<IPSCONTENT

		<ul>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$defaultStream = \IPS\core\Stream::defaultStream();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'discover' ) )  ):
$return .= <<<IPSCONTENT

					<li data-el="discover" class="ipsMobileFooter__item">
						<a data-action="defaultStream" class="ipsMobileFooter__link" href="
IPSCONTENT;

if ( $defaultStream ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $defaultStream->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							<span class="ipsMobileFooter__icon">
								<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 384 512"><path d="M64 464c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16H224v80c0 17.7 14.3 32 32 32h80V448c0 8.8-7.2 16-16 16H64zM64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V154.5c0-17-6.7-33.3-18.7-45.3L274.7 18.7C262.7 6.7 246.5 0 229.5 0H64zm56 256c-13.3 0-24 10.7-24 24s10.7 24 24 24H264c13.3 0 24-10.7 24-24s-10.7-24-24-24H120zm0 96c-13.3 0-24 10.7-24 24s10.7 24 24 24H264c13.3 0 24-10.7 24-24s-10.7-24-24-24H120z"></path></svg>
							</span>
							<span class="ipsMobileFooter__text" data-role="defaultStreamName">
IPSCONTENT;

if ( $defaultStream ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $defaultStream->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
						</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li data-el="notifications" class="ipsMobileFooter__item">
					<button class="ipsMobileFooter__link" aria-controls="ipsOffCanvas--notifications" aria-expanded="false" data-ipscontrols data-ipsoffcanvascontent>
						<span class="ipsMobileFooter__icon">
							<svg xmlns="http://www.w3.org/2000/svg" height="16" width="14" viewbox="0 0 448 512"><path d="M224 0c-17.7 0-32 14.3-32 32V51.2C119 66 64 130.6 64 208v25.4c0 45.4-15.5 89.5-43.8 124.9L5.3 377c-5.8 7.2-6.9 17.1-2.9 25.4S14.8 416 24 416H424c9.2 0 17.6-5.3 21.6-13.6s2.9-18.2-2.9-25.4l-14.9-18.6C399.5 322.9 384 278.8 384 233.4V208c0-77.4-55-142-128-156.8V32c0-17.7-14.3-32-32-32zm0 96c61.9 0 112 50.1 112 112v25.4c0 47.9 13.9 94.6 39.7 134.6H72.3C98.1 328 112 281.3 112 233.4V208c0-61.9 50.1-112 112-112zm64 352H224 160c0 17 6.7 33.3 18.7 45.3s28.3 18.7 45.3 18.7s33.3-6.7 45.3-18.7s18.7-28.3 18.7-45.3z"></path></svg>
						</span>
						<span class="ipsNotification" 
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->notification_cnt ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-notificationtype="notify" data-currentcount="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->notification_cnt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->notification_cnt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						<span class="ipsMobileFooter__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</button>
				</li>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm != 2 and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

					<li data-el="messenger" class="ipsMobileFooter__item">
						<button class="ipsMobileFooter__link" aria-controls="ipsOffCanvas--messenger" aria-expanded="false" data-ipscontrols data-ipsoffcanvascontent>
							<span class="ipsMobileFooter__icon">
								
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm ):
$return .= <<<IPSCONTENT

									<svg xmlns="http://www.w3.org/2000/svg" height="16" width="20" viewbox="0 0 640 512" class="i-opacity_3"><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L472.1 344.7c15.2-26 23.9-56.3 23.9-88.7V216c0-13.3-10.7-24-24-24s-24 10.7-24 24v24 16c0 21.2-5.1 41.1-14.2 58.7L416 300.8V256H358.9l-34.5-27c2.9-3.1 7-5 11.6-5h80V192H336c-8.8 0-16-7.2-16-16s7.2-16 16-16h80V128H336c-8.8 0-16-7.2-16-16s7.2-16 16-16h80c0-53-43-96-96-96s-96 43-96 96v54.3L38.8 5.1zM358.2 378.2C346.1 382 333.3 384 320 384c-70.7 0-128-57.3-128-128v-8.7L144.7 210c-.5 1.9-.7 3.9-.7 6v40c0 89.1 66.2 162.7 152 174.4V464H248c-13.3 0-24 10.7-24 24s10.7 24 24 24h72 72c13.3 0 24-10.7 24-24s-10.7-24-24-24H344V430.4c20.4-2.8 39.7-9.1 57.3-18.2l-43.1-33.9z"></path></svg>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewbox="0 0 512 512"><path d="M64 112c-8.8 0-16 7.2-16 16v22.1L220.5 291.7c20.7 17 50.4 17 71.1 0L464 150.1V128c0-8.8-7.2-16-16-16H64zM48 212.2V384c0 8.8 7.2 16 16 16H448c8.8 0 16-7.2 16-16V212.2L322 328.8c-38.4 31.5-93.7 31.5-132 0L48 212.2zM0 128C0 92.7 28.7 64 64 64H448c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128z"></path></svg>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</span>
							<span class="ipsNotification" 
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->msg_count_new ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-notificationtype="inbox" data-currentcount="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->msg_count_new, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->msg_count_new, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							<span class="ipsMobileFooter__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_messages', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</button>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$login = new \IPS\Login( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$usernamePasswordMethods = $login->usernamePasswordMethods();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $usernamePasswordMethods or $buttonMethods ):
$return .= <<<IPSCONTENT

					<li data-el="sign-in" class="ipsMobileFooter__item">
						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsMobileFooter__link">
							<span class="ipsMobileFooter__icon">
								<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewbox="0 0 512 512"><path d="M399 384.2C376.9 345.8 335.4 320 288 320H224c-47.4 0-88.9 25.8-111 64.2c35.2 39.2 86.2 63.8 143 63.8s107.8-24.7 143-63.8zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm256 16a72 72 0 1 0 0-144 72 72 0 1 0 0 144z"></path></svg>
							</span>
							<span class="ipsMobileFooter__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Login::registrationType() != 'disabled' ):
$return .= <<<IPSCONTENT

					<li data-el="sign-up" class="ipsMobileFooter__item">
						
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'redirect' ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_reg_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsMobileFooter__link ipsMobileFooter__link--sign-up" target="_blank" rel="noopener">
								<span class="ipsMobileFooter__icon">
									<svg xmlns="http://www.w3.org/2000/svg" height="16" width="20" viewbox="0 0 640 512"><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312V248H440c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V136c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H552v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"></path></svg>
								</span>
								<span class="ipsMobileFooter__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsMobileFooter__link ipsMobileFooter__link--sign-up">
								<span class="ipsMobileFooter__icon">
									<svg xmlns="http://www.w3.org/2000/svg" height="16" width="20" viewbox="0 0 640 512"><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312V248H440c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V136c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H552v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"></path></svg>
								</span>
								<span class="ipsMobileFooter__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'search' ) ) ):
$return .= <<<IPSCONTENT

				<li data-el="search" class="ipsMobileFooter__item">
					<button class="ipsMobileFooter__link" aria-controls="ipsOffCanvas--search" aria-expanded="false" data-ipscontrols>
						<span class="ipsMobileFooter__icon">
							<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewbox="0 0 512 512"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"></path></svg>
						</span>
						<span class="ipsMobileFooter__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</button>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->mobileNav( 'footer' );
$return .= <<<IPSCONTENT

			<li data-el="more" class="ipsMobileFooter__item">
				<button class="ipsMobileFooter__link" aria-controls="ipsOffCanvas--navigation" aria-expanded="false" data-ipscontrols>
					<span class="ipsMobileFooter__icon">
						<svg xmlns="http://www.w3.org/2000/svg" height="16" width="14" viewbox="0 0 448 512"><path d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"></path></svg>
					</span>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'modcp' ) ) and \IPS\Member::loggedIn()->canAccessReportCenter() and \IPS\Member::loggedIn()->reportCount() ):
$return .= <<<IPSCONTENT

						<span class="ipsNotification">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->reportCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<span class="ipsMobileFooter__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mobile_footer_menu', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</button>
			</li>
		</ul>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/mobileFooterBar", "mobileFooter:inside-end", [  ] );
$return .= <<<IPSCONTENT
</nav>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/mobileFooterBar", "mobileFooter:after", [  ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

<section class="ipsOffCanvas" id="ipsOffCanvas--profile" data-ips-hidden-group="offcanvas" hidden data-ips-hidden-top-layer>
	<button class="ipsOffCanvas__overlay" aria-controls="ipsOffCanvas--profile" aria-expanded="false" data-ipscontrols><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
	<div class="ipsOffCanvas__panel">
		<header class="ipsOffCanvas__header">
			<h4>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			<button class="ipsOffCanvas__header-button" aria-controls="ipsOffCanvas--profile" aria-expanded="false" data-ipscontrols>
				<i class="fa-solid fa-xmark"></i>
				<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</button>
		</header>
		<div class="ipsOffCanvas__scroll">
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

				<div class="ipsOffCanvas__box">
					<ul class="ipsOffCanvas__nav ipsOffCanvas__nav--user">
						<li>
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) ) ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_my_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<div class="ipsOffCanvas__item">
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<span class="ipsPhotoPanel i-flex_11">
									<span class="ipsUserPhoto">
										<img src="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
									</span>
									<span class="ipsPhotoPanel__text">
										<span class="i-font-size_2 i-font-weight_600 i-color_hard">
IPSCONTENT;

if ( isset( $_SESSION['logged_in_as_key'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($_SESSION['logged_in_from']['name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'front_logged_in_as', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
										<small class="i-color_soft i-font-size_-1 i-word-break_break-all">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</small>
									</span>
								</span>
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) ) ):
$return .= <<<IPSCONTENT

								
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a></li>
					</ul>
				</div>
				<nav class="ipsOffCanvas__box">
					<ul class="ipsOffCanvas__nav ipsOffCanvas__nav--profile" id="mobile-nav__profile">
						
IPSCONTENT;

if ( \IPS\Theme::i()->getCssVariableFromKey('set__i-mobile-icons-location') == "header" ):
$return .= <<<IPSCONTENT

							<li><hr class="ipsHr"></li>
							<li>
								<button aria-controls="ipsOffCanvas--notifications" aria-expanded="false" data-ipscontrols data-ipsoffcanvascontent>
									<span class="ipsOffCanvas__icon">
										<svg xmlns="http://www.w3.org/2000/svg" height="16" width="14" viewbox="0 0 448 512"><path d="M224 0c-17.7 0-32 14.3-32 32V51.2C119 66 64 130.6 64 208v25.4c0 45.4-15.5 89.5-43.8 124.9L5.3 377c-5.8 7.2-6.9 17.1-2.9 25.4S14.8 416 24 416H424c9.2 0 17.6-5.3 21.6-13.6s2.9-18.2-2.9-25.4l-14.9-18.6C399.5 322.9 384 278.8 384 233.4V208c0-77.4-55-142-128-156.8V32c0-17.7-14.3-32-32-32zm0 96c61.9 0 112 50.1 112 112v25.4c0 47.9 13.9 94.6 39.7 134.6H72.3C98.1 328 112 281.3 112 233.4V208c0-61.9 50.1-112 112-112zm64 352H224 160c0 17 6.7 33.3 18.7 45.3s28.3 18.7 45.3 18.7s33.3-6.7 45.3-18.7s18.7-28.3 18.7-45.3z"></path></svg>
									</span>
									<span class="ipsOffCanvas__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
									<span class="ipsNotification" 
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->notification_cnt ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-notificationtype="notify" data-currentcount="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->notification_cnt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->notification_cnt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</button>
							</li>
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm != 2 and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

								<li>
									<button aria-controls="ipsOffCanvas--messenger" aria-expanded="false" data-ipscontrols data-ipsoffcanvascontent>
										<span class="ipsOffCanvas__icon">
											
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm ):
$return .= <<<IPSCONTENT

												<svg xmlns="http://www.w3.org/2000/svg" height="16" width="20" viewbox="0 0 640 512" class="i-opacity_3"><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L472.1 344.7c15.2-26 23.9-56.3 23.9-88.7V216c0-13.3-10.7-24-24-24s-24 10.7-24 24v24 16c0 21.2-5.1 41.1-14.2 58.7L416 300.8V256H358.9l-34.5-27c2.9-3.1 7-5 11.6-5h80V192H336c-8.8 0-16-7.2-16-16s7.2-16 16-16h80V128H336c-8.8 0-16-7.2-16-16s7.2-16 16-16h80c0-53-43-96-96-96s-96 43-96 96v54.3L38.8 5.1zM358.2 378.2C346.1 382 333.3 384 320 384c-70.7 0-128-57.3-128-128v-8.7L144.7 210c-.5 1.9-.7 3.9-.7 6v40c0 89.1 66.2 162.7 152 174.4V464H248c-13.3 0-24 10.7-24 24s10.7 24 24 24h72 72c13.3 0 24-10.7 24-24s-10.7-24-24-24H344V430.4c20.4-2.8 39.7-9.1 57.3-18.2l-43.1-33.9z"></path></svg>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewbox="0 0 512 512"><path d="M64 112c-8.8 0-16 7.2-16 16v22.1L220.5 291.7c20.7 17 50.4 17 71.1 0L464 150.1V128c0-8.8-7.2-16-16-16H64zM48 212.2V384c0 8.8 7.2 16 16 16H448c8.8 0 16-7.2 16-16V212.2L322 328.8c-38.4 31.5-93.7 31.5-132 0L48 212.2zM0 128C0 92.7 28.7 64 64 64H448c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128z"></path></svg>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</span>
										<span class="ipsOffCanvas__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_messages', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
										<span class="ipsNotification" 
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->msg_count_new ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-notificationtype="inbox" data-currentcount="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->msg_count_new, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->msg_count_new, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									</button>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<li><hr class="ipsHr"></li>
					    
IPSCONTENT;

foreach ( \IPS\Member::loggedIn()->menu( 'mobile' )->getLinks() as $link ):
$return .= <<<IPSCONTENT

					   		{$link}
					    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</nav>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canHaveAchievements() and \IPS\core\Achievements\Rank::show() and \IPS\core\Achievements\Rank::getStore() and $rank = \IPS\Member::loggedIn()->rank() ):
$return .= <<<IPSCONTENT

			<div class="ipsOffCanvas__box ipsOffCanvas__box--rank i-flex_00 i-border-top_2">
				<div class="ipsOffCanvas__nav">
					<div class="ipsOffCanvas__item">
						<div class="ipsPhotoPanel i-align-items_center i-flex_11">
							<div class="i-basis_40">{$rank->html('i-aspect-ratio_10')}</div>
							<div>
								<h4>
									<strong class="i-font-size_2 i-font-weight_600 i-color_hard">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
									<span class="i-margin-start_1 i-color_soft i-font-size_-1">(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['pos'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['max'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
								</h4>
								
IPSCONTENT;

if ( $nextRank = \IPS\Member::loggedIn()->nextRank() ):
$return .= <<<IPSCONTENT

									<progress class="ipsProgress i-margin-top_1" max="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextRank->points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->achievements_points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></progress>
									<small class="i-font-size_-1 i-color_soft">
IPSCONTENT;

$pluralize = array( $nextRank->points - \IPS\Member::loggedIn()->achievements_points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_next_rank', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</small>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</div>
					</div>
				</div>
			</div>
		
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->canHaveAchievements() and \IPS\Settings::i()->achievements_rebuilding ):
$return .= <<<IPSCONTENT

			<div class="ipsOffCanvas__box ipsOffCanvas__box--rank i-flex_00 i-border-top_2">
				<div class="elUserNav_achievements i-flex i-gap_4 i-row-gap_0 i-color_soft">
					<div class="elUserNav_achievements__icon i-flex_00"><i class="fa-solid fa-circle-info fa-fw i-font-size_2"></i></div>
					<div class="elUserNav_achievements__content i-flex_11">
						<p class="i-color_soft">
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ranks_are_being_recalculated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</p>
					</div>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</section>


IPSCONTENT;

else:
$return .= <<<IPSCONTENT


<section class="ipsOffCanvas" id="ipsOffCanvas--guest" data-ips-hidden-group="offcanvas" hidden data-ips-hidden-top-layer>
	<button class="ipsOffCanvas__overlay" aria-controls="ipsOffCanvas--guest" aria-expanded="false" data-ipscontrols><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
	<div class="ipsOffCanvas__panel">

		<header class="ipsOffCanvas__header">
			<h4>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			<button class="ipsOffCanvas__header-button" aria-controls="ipsOffCanvas--guest" aria-expanded="false" data-ipscontrols>
				<i class="fa-solid fa-xmark"></i>
				<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</button>
		</header>
		<div class="ipsOffCanvas__scroll">
			<div class="ipsOffCanvas__box">
				<ul class="ipsOffCanvas__nav ipsOffCanvas__nav--navigation">
					
IPSCONTENT;

$login = new \IPS\Login( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$usernamePasswordMethods = $login->usernamePasswordMethods();
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $usernamePasswordMethods or $buttonMethods ):
$return .= <<<IPSCONTENT

						<li data-el="sign-in">
							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
								<span class="ipsOffCanvas__icon">
									<i class="fa-solid fa-circle-user"></i>
								</span>
								<span class="ipsOffCanvas__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Login::registrationType() != 'disabled' ):
$return .= <<<IPSCONTENT

						<li data-el="sign-up">
							
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'redirect' ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_reg_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
									<span class="ipsOffCanvas__icon">
										<i class="fa-solid fa-user-plus"></i>
									</span>
									<span class="ipsOffCanvas__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
									<span class="ipsOffCanvas__icon">
										<i class="fa-solid fa-user-plus"></i>
									</span>
									<span class="ipsOffCanvas__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
	</div>
</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<section class="ipsOffCanvas" id="ipsOffCanvas--navigation" data-ips-hidden-group="offcanvas" hidden data-ips-hidden-top-layer>
	<button class="ipsOffCanvas__overlay" aria-controls="ipsOffCanvas--navigation" aria-expanded="false" data-ipscontrols><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
	<div class="ipsOffCanvas__panel">

		<header class="ipsOffCanvas__header">
			<h4>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_navigation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			<button class="ipsOffCanvas__header-button" aria-controls="ipsOffCanvas--navigation" aria-expanded="false" data-ipscontrols>
				<i class="fa-solid fa-xmark"></i>
				<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</button>
		</header>
		<div class="ipsOffCanvas__scroll">

			<!-- Navigation -->
			<nav aria-label="Mobile" class="ipsOffCanvas__box">
				<ul class="ipsOffCanvas__nav ipsOffCanvas__nav--navigation">
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'modcp' ) ) and \IPS\Member::loggedIn()->canAccessReportCenter() and \IPS\Member::loggedIn()->reportCount() ):
$return .= <<<IPSCONTENT

						<li data-el="reports">
							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=reports", null, "modcp_reports", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
								<span class="ipsOffCanvas__icon">
									<i class="fa-regular fa-flag"></i>
								</span>
								<span class="ipsOffCanvas__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_center_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								
IPSCONTENT;

if ( \IPS\Member::loggedIn()->reportCount() ):
$return .= <<<IPSCONTENT

									<span class="ipsNotification" data-notificationtype="reports">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->reportCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</a>
						</li>
						<li><hr></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

$primaryBars = \IPS\core\FrontNavigation::i()->roots();
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$subBars = \IPS\core\FrontNavigation::i()->subBars();
$return .= <<<IPSCONTENT

					
					
IPSCONTENT;

foreach ( $primaryBars as $id => $item ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $item->canView() and $item->isAvailableFor('smallscreen') ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$children = $item->children();
$return .= <<<IPSCONTENT


							
IPSCONTENT;

$active = $item->activeOrChildActive('smallscreen');
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

\IPS\core\FrontNavigation::i()->activePrimaryNavBar = $item->id;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


							
IPSCONTENT;

if ( ( $subBars && isset( $subBars[ $id ] ) && \count( $subBars[ $id ] ) ) || $children ):
$return .= <<<IPSCONTENT

								<li>
									<button aria-expanded="
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" aria-controls="mobile-nav__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipscontrols>
										<span class="ipsOffCanvas__icon">
											
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

												{$item->getIconData()}
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												<i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</span>
										<span class="ipsOffCanvas__label">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
										<i class="fa-solid fa-chevron-down"></i>
									</button>
									<ul class="ipsOffCanvas__nav-dropdown" id="mobile-nav__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !$active ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
										
IPSCONTENT;

$showSelfLink = true;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $subBars && isset( $subBars[ $id ] ) && \count( $subBars[ $id ] ) ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

// Determine whether we should show the parent link as a clickable sub item by comparing child links.
$return .= <<<IPSCONTENT

											
IPSCONTENT;

// If the *same* link exists as a child item, don't show it twice
$return .= <<<IPSCONTENT

											
IPSCONTENT;

foreach ( $subBars[ $id ] as $child ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( $child->canView() and $child->isAvailableFor('smallscreen') ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

if ( $subChildren = $child->children() ):
$return .= <<<IPSCONTENT

														
IPSCONTENT;

foreach ( $subChildren as $subChild ):
$return .= <<<IPSCONTENT

															
IPSCONTENT;

if ( method_exists( $subChild, 'link' ) && $subChild->link() && (string) $subChild->link() == (string) $item->link() ):
$return .= <<<IPSCONTENT

																
IPSCONTENT;

$showSelfLink = false;
$return .= <<<IPSCONTENT

																
IPSCONTENT;

break 2;
$return .= <<<IPSCONTENT

															
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

														
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

													
IPSCONTENT;

elseif ( method_exists( $child, 'link' ) && $child->link() && (string) $child->link() == (string) $item->link() ):
$return .= <<<IPSCONTENT

														
IPSCONTENT;

$showSelfLink = false;
$return .= <<<IPSCONTENT

														
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												
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

if ( $showSelfLink && method_exists( $item, 'link' ) and (string) $item->link() !== \IPS\Settings::i()->base_url && $item->link() ):
$return .= <<<IPSCONTENT

											<li>
												<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
													<span class="ipsOffCanvas__icon">
														
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

															{$item->getIconData()}
														
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

															<i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
														
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													</span>
													<span class="ipsOffCanvas__label">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
												</a>
											</li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->mobileNavigationChildren( $children );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $subBars && isset( $subBars[ $id ] ) && \count( $subBars[ $id ] ) ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->mobileNavigationChildren( $subBars[ $id ] );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</ul>
								</li>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( method_exists( $item, 'target' ) AND $item->target() ):
$return .= <<<IPSCONTENT
target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->target(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $item->target() == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT
aria-current="page" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<span class="ipsOffCanvas__icon">
										
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

											{$item->getIconData()}
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</span>
									<span class="ipsOffCanvas__label">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</a></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</nav>
		</div>
		
IPSCONTENT;

if ( \count( \IPS\Output::i()->breadcrumb ) ):
$return .= <<<IPSCONTENT

			<nav class="ipsOffCanvas__breadcrumb" aria-label="Breadcrumbs">
				<ol itemscope itemtype="https://schema.org/BreadcrumbList">
					<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
						<a title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'home', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
" itemprop="item">
							<i class="fa-solid fa-house-chimney"></i> <span itemprop="name">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'home', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</a>
						<meta itemprop="position" content="1">
					</li>
					
IPSCONTENT;

$last = end(\IPS\Output::i()->breadcrumb);
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$index = 2;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( \IPS\Output::i()->breadcrumb as $k => $b ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $b[0] === NULL ):
$return .= <<<IPSCONTENT

							<li aria-current="location" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
								<span itemprop="name">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $b[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								<meta itemprop="position" content="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $index, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							</li>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $b[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" itemprop="item">
									<span itemprop="name">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $b[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $b != $last ):
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
								</a>
								<meta itemprop="position" content="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $index, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$index++;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</nav>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</section>


<!-- Messenger -->

IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm != 2 and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

	<section class="ipsOffCanvas" id="ipsOffCanvas--messenger" data-ips-hidden-group="offcanvas" hidden data-ips-hidden-top-layer>
		<button class="ipsOffCanvas__overlay" aria-controls="ipsOffCanvas--messenger" aria-expanded="false" data-ipscontrols><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
		<div class="ipsOffCanvas__panel">
			<header class="ipsOffCanvas__header">
				<h4>
IPSCONTENT;

if ( ! \IPS\Member::loggedIn()->members_disable_pm ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_messages', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_messages_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h4>
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger", null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsOffCanvas__header-button">
					<i class="fa-regular fa-envelope"></i>
					<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_inbox', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</a>
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsOffCanvas__header-button">
					<i class="fa-regular fa-pen-to-square"></i>
					<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</a>
				<button class="ipsOffCanvas__header-button" aria-controls="ipsOffCanvas--messenger" aria-expanded="false" data-ipscontrols>
					<i class="fa-solid fa-xmark"></i>
					<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</button>
			</header>
			<div class="ipsOffCanvas__scroll">
				<div class="ipsOffCanvas__box">
					<i-data>
						<ul class="ipsData ipsData--table ipsData--compact ipsData--offcanvas-inbox" data-role="inboxList"></ul>
					</i-data>
				</div>
			</div>
		</div>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<!-- Notifications -->
<section class="ipsOffCanvas" id="ipsOffCanvas--notifications" data-ips-hidden-group="offcanvas" hidden data-ips-hidden-top-layer>
	<button class="ipsOffCanvas__overlay" aria-controls="ipsOffCanvas--notifications" aria-expanded="false" data-ipscontrols><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
	<div class="ipsOffCanvas__panel">
		<header class="ipsOffCanvas__header">
			<h4>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=options", null, "notifications_options", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsOffCanvas__header-button">
				<i class="fa-solid fa-gear"></i>
				<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notification_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</a>
			<button class="ipsOffCanvas__header-button" aria-controls="ipsOffCanvas--notifications" aria-expanded="false" data-ipscontrols>
				<i class="fa-solid fa-xmark"></i>
				<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</button>
		</header>
		<div class="ipsOffCanvas__scroll">
			<div class="ipsOffCanvas__box">
				<i-data>
					<ul class="ipsData ipsData--table ipsData--compact ipsData--offcanvas-notifications" data-role="notifyList"></ul>
				</i-data>
			</div>
		</div>
	</div>
</section>


IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'search' ) ) ):
$return .= <<<IPSCONTENT

	<!-- Search -->
	<section class="ipsOffCanvas" id="ipsOffCanvas--search" data-ips-hidden-animation data-ips-hidden-group="offcanvas" hidden data-ips-hidden-top-layer>
		<button class="ipsOffCanvas__overlay" aria-controls="ipsOffCanvas--search" aria-expanded="false" data-ipscontrols><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
		<div class="ipsOffCanvas__panel">
			<header class="ipsOffCanvas__header">
				<h4>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				<button class="ipsOffCanvas__header-button" aria-controls="ipsOffCanvas--search" aria-expanded="false" data-ipscontrols>
					<i class="fa-solid fa-xmark"></i>
					<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offcanvas_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</button>
			</header>
			<form class="ipsOffCanvas__scroll" accept-charset="utf-8" action="
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=search&controller=search&do=quicksearch", null, "search", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
" method="post">
				<div class="i-padding_2 i-position_sticky-top i-flex i-gap_1 i-margin-bottom_2">
					<input type="search" class="ipsInput ipsInput--text ipsOffCanvas__input" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_placeholder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" name="q" autocomplete="off" aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-autofocus>
					<button class="ipsButton ipsButton--primary i-flex_00 i-font-size_-2"><i class="fa-solid fa-magnifying-glass"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</div>
				<div class="">					
					<div class="ipsOffCanvas__search-filters i-font-size_-1">
						<div class="i-flex i-border-top_3">
							<label for="mobile-search__type" class="i-flex_11 i-flex i-align-items_center i-color_soft i-font-weight_500 i-padding-block_2 i-padding-start_2">
								<i class="fa-solid fa-window-restore i-basis_30"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_mobile_where', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:
							</label>
							<div class="ipsSimpleSelect ipsSimpleSelect--end i-font-weight_600">
								<select id="mobile-search__type" name="type">
									
IPSCONTENT;

$option = \IPS\Output::i()->defaultSearchOption;
$return .= <<<IPSCONTENT

									<option value="all" 
IPSCONTENT;

if ( \IPS\Output::i()->defaultSearchOption[0] == 'all' ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'everywhere', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									
IPSCONTENT;

if ( \count( \IPS\Output::i()->contextualSearchOptions ) ):
$return .= <<<IPSCONTENT

									    
IPSCONTENT;

$pos=1;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

foreach ( array_reverse( \IPS\Output::i()->contextualSearchOptions ) as $name => $data ):
$return .= <<<IPSCONTENT

											<option value="contextual_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $data ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \IPS\Output::i()->defaultSearchOption[0] == $data['type'] and $pos == \count( \IPS\Output::i()->contextualSearchOptions ) ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
											
IPSCONTENT;

$pos++;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

foreach ( \IPS\Output::i()->globalSearchMenuOptions() as $type => $name ):
$return .= <<<IPSCONTENT

										<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( empty(\IPS\Output::i()->contextualSearchOptions) and \IPS\Output::i()->defaultSearchOption[0] == $type ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</select>
							</div>
						</div>
						<div class="i-flex i-border-top_3">
							<label for="mobile-search__search_in" class="i-flex_11 i-flex i-align-items_center  i-color_soft i-font-weight_500 i-padding-block_2 i-padding-start_2">
								<i class="fa-regular fa-file-lines i-basis_30"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:
							</label>
							<div class="ipsSimpleSelect ipsSimpleSelect--end i-font-weight_600">
								<select id="mobile-search__search_in" name="search_in">
									<option value="all" selected>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'titles_and_body', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="titles">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'titles_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
								</select>
							</div>
						</div>
						<div class="i-flex i-border-top_3">
							<label for="mobile-search__startDate" class="i-flex_11 i-flex i-align-items_center  i-color_soft i-font-weight_500 i-padding-block_2 i-padding-start_2">
								<i class="fa-regular fa-pen-to-square i-basis_30"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'startDate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:
							</label>
							<div class="ipsSimpleSelect ipsSimpleSelect--end i-font-weight_600">
								<select id="mobile-search__startDate" name="startDate">
									<option value="any" selected>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_any', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="day">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_day', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="week">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="month">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="six_months">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_six_months', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="year">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
								</select>
							</div>
						</div>
						<div class="i-flex i-border-top_3">
							<label for="mobile-search__search_and_or" class="i-flex_11 i-flex i-align-items_center  i-color_soft i-font-weight_500 i-padding-block_2 i-padding-start_2">
								<i class="fa-solid fa-list-check i-basis_30"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_mobile_use', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:
							</label>
							<div class="ipsSimpleSelect ipsSimpleSelect--end i-font-weight_600">
								<select id="mobile-search__search_and_or" name="search_and_or">
									<option value="and" selected>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_mobile_and_option_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="or">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_mobile_or_option_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
								</select>
							</div>
						</div>
						<div class="i-flex i-border-top_3">
							<label for="mobile-search__updatedDate" class="i-flex_11 i-flex i-align-items_center  i-color_soft i-font-weight_500 i-padding-block_2 i-padding-start_2">
								<i class="fa-regular fa-calendar i-basis_30"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updatedDate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:
							</label>
							<div class="ipsSimpleSelect ipsSimpleSelect--end i-font-weight_600">
								<select id="mobile-search__updatedDate" name="updatedDate">
									<option value="any" selected>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_any', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="day">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_day', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="week">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="month">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="six_months">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_six_months', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
									<option value="year">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function mobileNavHeader(   ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/mobileNavHeader", "mobileNavHeader:before", [  ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="mobileNavHeader" class="ipsMobileNavIcons ipsResponsive_header--mobile">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/mobileNavHeader", "mobileNavHeader:inside-start", [  ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

		<li data-el="guest">
			<button class="ipsMobileNavIcons__button ipsMobileNavIcons__button--primary" aria-controls="ipsOffCanvas--guest" aria-expanded="false" data-ipscontrols>
				<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</button>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id and \IPS\Theme::i()->getCssVariableFromKey('set__i-mobile-icons-location') == "header" ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$defaultStream = \IPS\core\Stream::defaultStream();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'discover' ) )  ):
$return .= <<<IPSCONTENT

			<li data-el="discover">
				<a class="ipsMobileNavIcons__button" data-action="defaultStream" href="
IPSCONTENT;

if ( $defaultStream ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $defaultStream->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 384 512"><path d="M64 464c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16H224v80c0 17.7 14.3 32 32 32h80V448c0 8.8-7.2 16-16 16H64zM64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V154.5c0-17-6.7-33.3-18.7-45.3L274.7 18.7C262.7 6.7 246.5 0 229.5 0H64zm56 256c-13.3 0-24 10.7-24 24s10.7 24 24 24H264c13.3 0 24-10.7 24-24s-10.7-24-24-24H120zm0 96c-13.3 0-24 10.7-24 24s10.7 24 24 24H264c13.3 0 24-10.7 24-24s-10.7-24-24-24H120z"></path></svg>
					<span class="ipsInvisible" data-role="defaultStreamName">
IPSCONTENT;

if ( $defaultStream ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $defaultStream->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
				</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'search' ) ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Theme::i()->getCssVariableFromKey('set__i-mobile-icons-location') == "header" ):
$return .= <<<IPSCONTENT

			<li data-el="search">
				<button class="ipsMobileNavIcons__button" aria-controls="ipsOffCanvas--search" aria-expanded="false" data-ipscontrols>
					<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewbox="0 0 512 512"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"></path></svg>
					<span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</button>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->mobileNav( 'header' );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

		<li data-el="user">
			<button class="ipsMobileNavIcons__button" aria-controls="ipsOffCanvas--profile" aria-expanded="false" data-ipscontrols>
				<span class="ipsUserPhoto">
					<img src="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
				</span>
				
IPSCONTENT;

if ( \IPS\Theme::i()->getCssVariableFromKey('set__i-mobile-icons-location') == "header" ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$total = \IPS\Member::loggedIn()->notification_cnt;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->members_disable_pm and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$total += \IPS\Member::loggedIn()->msg_count_new;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $total ):
$return .= <<<IPSCONTENT

						<span class="ipsNotification">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $total, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<span class="ipsInvisible">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</button>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Theme::i()->getCssVariableFromKey('set__i-mobile-icons-location') == "header" ):
$return .= <<<IPSCONTENT

		<li data-el="more">
			<button class="ipsMobileNavIcons__button" aria-controls="ipsOffCanvas--navigation" aria-expanded="false" data-ipscontrols>
				<svg xmlns="http://www.w3.org/2000/svg" height="16" width="14" viewbox="0 0 448 512"><path d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"></path></svg>
				<span class="ipsInvisible">Menu</span>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'modcp' ) ) and \IPS\Member::loggedIn()->canAccessReportCenter() and \IPS\Member::loggedIn()->reportCount() ):
$return .= <<<IPSCONTENT

					<span class="ipsNotification">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->reportCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</button>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/mobileNavHeader", "mobileNavHeader:inside-end", [  ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/mobileNavHeader", "mobileNavHeader:after", [  ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function mobileNavigationChildren( $items ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $items as $child ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $child->canView() and $child->isAvailableFor('smallscreen') ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $children = $child->children() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$id = md5( mt_rand() );
$return .= <<<IPSCONTENT

			<li>
				<button aria-expanded="false" aria-controls="mobile-nav__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipscontrols>
					<span class="ipsOffCanvas__icon">
						
IPSCONTENT;

if ( $child->getIconData() ):
$return .= <<<IPSCONTENT

							{$child->getIconData()}
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class="fa-solid" style="--icon:'{$child->getDefaultIcon()}'"></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
					<span class="ipsOffCanvas__label">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<i class="fa-solid fa-chevron-down"></i>
				</button>
				<ul class='ipsOffCanvas__nav-dropdown' id='mobile-nav__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' hidden>
					
IPSCONTENT;

if ( $child->link() && $child->link() !== '#' ):
$return .= <<<IPSCONTENT

						<li>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->mobileNavigationChildren( $children );
$return .= <<<IPSCONTENT

				</ul>
			</li>

		
IPSCONTENT;

elseif ( $child instanceof \IPS\core\extensions\core\FrontNavigation\MenuHeader ):
$return .= <<<IPSCONTENT

			<li class='ipsOffCanvas__nav-title'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

elseif ( $child instanceof \IPS\core\extensions\core\FrontNavigation\MenuSeparator ):
$return .= <<<IPSCONTENT

			
		
IPSCONTENT;

elseif ( $child instanceof \IPS\core\extensions\core\FrontNavigation\MenuButton ):
$return .= <<<IPSCONTENT

			<li>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</a>
			</li>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li>
				
IPSCONTENT;

$active = $child->activeOrChildActive('smallscreen');
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( method_exists( $child, 'target' ) AND $child->target() ):
$return .= <<<IPSCONTENT
target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->target(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

if ( $child->target() == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT
aria-current="page"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<span class="ipsOffCanvas__icon">
						
IPSCONTENT;

if ( $child->getIconData() ):
$return .= <<<IPSCONTENT

							{$child->getIconData()}
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class="fa-solid" style="--icon:'{$child->getDefaultIcon()}'"></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
					<span class="ipsOffCanvas__label">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function modBadges( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function modLog( $item, $modlog ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$modlog}

IPSCONTENT;

		return $return;
}

	function navBar( $preview=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navBar", "navBar:before", [ $preview ] );
$return .= <<<IPSCONTENT
<nav data-ips-hook="navBar" class="ipsNav" aria-label="Primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navBar", "navBar:inside-start", [ $preview ] );
$return .= <<<IPSCONTENT

	<i-navigation-menu>
		<ul class="ipsNavBar">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->navBarItems( \IPS\core\FrontNavigation::i()->roots( $preview ), \IPS\core\FrontNavigation::i()->subBars( $preview ), 0, $preview );
$return .= <<<IPSCONTENT

			<li data-el="nav-more" hidden>
				<button aria-expanded="false" aria-controls="nav__more" data-ipscontrols type="button"><i class="fa-ips fa-ips--primary-nav"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-angle-down"></i></button>
				<ul class="ipsNav__dropdown" id="nav__more" data-ips-hidden-light-dismiss hidden></ul>
			</li>
		</ul>
	</i-navigation-menu>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navBar", "navBar:inside-end", [ $preview ] );
$return .= <<<IPSCONTENT
</nav>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navBar", "navBar:after", [ $preview ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function navBarChildren( $items, $preview=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $items as $item ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $preview or ( $item->canView() and $item->isAvailableFor( \IPS\Theme::i()->getLayoutValue('global_view_mode') === 'side' ? 'sidebar' : 'header' ) ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $children = $item->children() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$id = md5( mt_rand() );
$return .= <<<IPSCONTENT

			<li id='elNavigation_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsMenu_subItems'>
				<a href='
IPSCONTENT;

if ( $item->link() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
#
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</a>
				<ul id='elNavigation_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsMenu ipsMenu_auto ipsHide'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->navBarChildren( $children, $preview );
$return .= <<<IPSCONTENT

				</ul>
			</li>
		
IPSCONTENT;

elseif ( $item instanceof \IPS\core\extensions\core\FrontNavigation\MenuHeader ):
$return .= <<<IPSCONTENT

			<li class='ipsNav__dropdownTitle'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

elseif ( $item instanceof \IPS\core\extensions\core\FrontNavigation\MenuSeparator ):
$return .= <<<IPSCONTENT

			<li>
				<hr>
			</li>
		
IPSCONTENT;

elseif ( $item instanceof \IPS\core\extensions\core\FrontNavigation\MenuButton ):
$return .= <<<IPSCONTENT

			<li>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsNav__button'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</a>
			</li>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li {$item->attributes()}>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( method_exists( $item, 'target' ) AND $item->target() ):
$return .= <<<IPSCONTENT
target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->target(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

if ( $item->target() == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<i class='fa-ips fa-ips--primary-nav'></i><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function navBarItems( $roots, $subBars=NULL, $parent=0, $preview=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$first = TRUE;
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $roots as $id => $item ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $preview or ( $item->canView() and $item->isAvailableFor( \IPS\Theme::i()->getLayoutValue('global_view_mode') === 'side' ? 'sidebar' : 'header' ) ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$active = $preview ? $first : $item->activeOrChildActive();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

\IPS\core\FrontNavigation::i()->activePrimaryNavBar = $item->id;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<li 
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT
data-active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-navApp="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_substr( \get_class( $item ), 4, mb_strpos( \get_class( $item ), '\\', 4 ) - 4 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-navExt="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_substr( \get_class( $item ), mb_strrpos( \get_class( $item ), '\\' ) + 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" {$item->attributes()}>
			
IPSCONTENT;

$children = $item->children();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT

				<button aria-expanded="false" aria-controls="elNavSecondary_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipscontrols type="button">
					<span class="ipsNavBar__icon" aria-hidden="true">
						
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

							{$item->getIconData()}
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
					<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<i class="fa-solid fa-angle-down"></i>
				</button>
				<ul class='ipsNav__dropdown' id='elNavSecondary_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $parent === 0 ):
$return .= <<<IPSCONTENT
data-ips-hidden-light-dismiss
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 hidden>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->navBarChildren( $children, $preview );
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $subBars && isset( $subBars[ $id ] ) && \count( $subBars[ $id ] ) ):
$return .= <<<IPSCONTENT

					<button aria-expanded="false" aria-controls="elNavSecondary_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipscontrols type="button">
						<span class="ipsNavBar__icon" aria-hidden="true">
							
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

								{$item->getIconData()}
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</span>
						<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						<i class="fa-solid fa-angle-down"></i>
					</button>
					<ul class='ipsNav__dropdown' id='elNavSecondary_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $parent === 0 ):
$return .= <<<IPSCONTENT
data-ips-hidden-light-dismiss
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 hidden>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->navBarItems( $subBars[ $id ], NULL, $item->id, $preview );
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

if ( $item->link() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
#
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( method_exists( $item, 'target' ) AND $item->target() ):
$return .= <<<IPSCONTENT
target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->target(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

if ( $item->target() == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-navItem-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT
aria-current="page"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<span class="ipsNavBar__icon" aria-hidden="true">
							
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

								{$item->getIconData()}
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</span>
						<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$first = FALSE;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function navColumn( $preview=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navColumn", "navigationColumn:before", [ $preview ] );
$return .= <<<IPSCONTENT
<ul class="ipsNavPanel__nav" data-ips-hook="navigationColumn">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navColumn", "navigationColumn:inside-start", [ $preview ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ( \IPS\Settings::i()->site_online || \IPS\Member::loggedIn()->group['g_access_offline'] )  ):
$return .= <<<IPSCONTENT

		<li>
			
IPSCONTENT;

/*
$return .= <<<IPSCONTENT
<!-- This div is needed to ensure the search button doesn't inherit any of the "li > button" styles -->
IPSCONTENT;

*/
$return .= <<<IPSCONTENT

			<div>
				<button class="ipsNavPanel__search" aria-controls="ipsSearchDialog" aria-expanded="false" data-ipscontrols type="button"><span class="ipsNavPanel__icon" aria-hidden="true"><i class="fa-ips fa-ips--nav-panel"></i></span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->navColumnItems( \IPS\core\FrontNavigation::i()->roots( $preview ), \IPS\core\FrontNavigation::i()->subBars( $preview ), 0, $preview );
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navColumn", "navigationColumn:inside-end", [ $preview ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navColumn", "navigationColumn:after", [ $preview ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function navColumnChildren( $items, $preview=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $items as $item ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $preview or ( $item->canView() and $item->isAvailableFor( \IPS\Theme::i()->getLayoutValue('global_view_mode') === 'side' ? 'sidebar' : 'header' ) ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $children = $item->children() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$id = md5( mt_rand() );
$return .= <<<IPSCONTENT

			<li id='elNavigation_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsMenu_subItems'>
				<a href='
IPSCONTENT;

if ( $item->link() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
#
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</a>
				<ul id='elNavigation_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsMenu ipsMenu_auto ipsHide'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->navColumnChildren( $children, $preview );
$return .= <<<IPSCONTENT

				</ul>
			</li>
		
IPSCONTENT;

elseif ( $item instanceof \IPS\core\extensions\core\FrontNavigation\MenuHeader ):
$return .= <<<IPSCONTENT

			<li class='ipsNavPanel__title'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

elseif ( $item instanceof \IPS\core\extensions\core\FrontNavigation\MenuSeparator ):
$return .= <<<IPSCONTENT

			<li>
				<hr>
			</li>
		
IPSCONTENT;

elseif ( $item instanceof \IPS\core\extensions\core\FrontNavigation\MenuButton ):
$return .= <<<IPSCONTENT

			<li>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class=''>
					<span class="ipsNavPanel__icon" aria-hidden="true">
						<i class="fa-solid" style="--icon:'\\f061'"></i>
					</span> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</a>
			</li>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li {$item->attributes()}>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( method_exists( $item, 'target' ) AND $item->target() ):
$return .= <<<IPSCONTENT
target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->target(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

if ( $item->target() == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<span class="ipsNavPanel__icon" aria-hidden="true">
					    
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

					        {$item->getIconData()}
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						    <i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
					<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function navColumnItems( $roots, $subBars=NULL, $parent=0, $preview=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $roots as $id => $item ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $preview or ( $item->canView() and $item->isAvailableFor( \IPS\Theme::i()->getLayoutValue('global_view_mode') === 'side' ? 'sidebar' : 'header' )) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$active = $item->activeOrChildActive();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

\IPS\core\FrontNavigation::i()->activePrimaryNavBar = $item->id;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navColumnItems", "navColumnItem:before", [ $roots,$subBars,$parent,$preview ] );
$return .= <<<IPSCONTENT
<li 
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT
aria-current="page" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-navapp="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_substr( \get_class( $item ), 4, mb_strpos( \get_class( $item ), '\\', 4 ) - 4 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ips-hook="navColumnItem">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navColumnItems", "navColumnItem:inside-start", [ $roots,$subBars,$parent,$preview ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$children = $item->children();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT

				<button class="" 
IPSCONTENT;

if ( $item->isSideBarItemCollapsed() ):
$return .= <<<IPSCONTENT
aria-expanded="false" 
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
aria-expanded="true" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-controls="elNavSecondary_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipscontrols type="button" data-action="collapseLinks">
					<span class="ipsNavPanel__icon" aria-hidden="true">
						
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

							{$item->getIconData()}
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
					<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<i class="fa-solid fa-angle-down ipsNavPanel__toggle-list"></i>
				</button>
				<ul id="elNavSecondary_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ips-hidden-animation="slide-fade" data-ips-hidden-event="ips:toggleSidePanelNav" 
IPSCONTENT;

if ( $item->isSideBarItemCollapsed() ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->navColumnChildren( $children, $preview );
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $subBars && isset( $subBars[ $id ] ) && \count( $subBars[ $id ] ) ):
$return .= <<<IPSCONTENT

					<button class="" 
IPSCONTENT;

if ( $item->isSideBarItemCollapsed() ):
$return .= <<<IPSCONTENT
aria-expanded="false" 
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
aria-expanded="true" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-controls="elNavSecondary_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipscontrols type="button" data-action="collapseLinks">
						<span class="ipsNavPanel__icon" aria-hidden="true">
							
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

								{$item->getIconData()}
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</span>
						<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						<i class="fa-solid fa-angle-down ipsNavPanel__toggle-list"></i>
					</button>
					<ul id="elNavSecondary_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ips-hidden-animation="slide-fade" data-ips-hidden-event="ips:toggleSidePanelNav" 
IPSCONTENT;

if ( $item->isSideBarItemCollapsed() ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->navColumnItems( $subBars[ $id ], NULL, $item->id, $preview );
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

if ( $item->link() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
#
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( method_exists( $item, 'target' ) AND $item->target() ):
$return .= <<<IPSCONTENT
target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->target(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $item->target() == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-navitem-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						<span class="ipsNavPanel__icon" aria-hidden="true">
							
IPSCONTENT;

if ( $item->getIconData() ):
$return .= <<<IPSCONTENT

								{$item->getIconData()}
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="fa-solid" style="--icon:'{$item->getDefaultIcon()}'"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</span>
						<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navColumnItems", "navColumnItem:inside-end", [ $roots,$subBars,$parent,$preview ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navColumnItems", "navColumnItem:after", [ $roots,$subBars,$parent,$preview ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function navigationPanel(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navigationPanel", "navigationPanel:before", [  ] );
$return .= <<<IPSCONTENT
<div class="ipsNavPanel" data-ips-hook="navigationPanel">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navigationPanel", "navigationPanel:inside-start", [  ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->logo( 'side' );
$return .= <<<IPSCONTENT

	<nav class="ipsNavPanel__scroll" data-controller="core.front.core.navigationPanel">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->navColumn(  );
$return .= <<<IPSCONTENT

	</nav>
	
IPSCONTENT;

 /* 
$return .= <<<IPSCONTENT
<!-- Restore scroll position as soon as possible -->
IPSCONTENT;

 */ 
$return .= <<<IPSCONTENT

	<script>document.querySelector('[data-controller="core.front.core.navigationPanel"]').scrollTop = sessionStorage.getItem( 'navigationPanelScroll') || 0;</script>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navigationPanel", "navigationPanel:inside-end", [  ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/navigationPanel", "navigationPanel:after", [  ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function pageHeader( $title, $blurb='', $rawBlurb=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/pageHeader", "pageHeader:before", [ $title,$blurb,$rawBlurb ] );
$return .= <<<IPSCONTENT
<header data-ips-hook="pageHeader" class="ipsPageHeader ipsPageHeader--general ipsBox ipsPull">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/pageHeader", "pageHeader:inside-start", [ $title,$blurb,$rawBlurb ] );
$return .= <<<IPSCONTENT

	<h1 class="ipsPageHeader__title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
	
IPSCONTENT;

if ( $blurb ):
$return .= <<<IPSCONTENT

		<div class="ipsPageHeader__desc">
			
IPSCONTENT;

if ( !$rawBlurb ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blurb, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				{$blurb}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/pageHeader", "pageHeader:inside-end", [ $title,$blurb,$rawBlurb ] );
$return .= <<<IPSCONTENT
</header>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/pageHeader", "pageHeader:after", [ $title,$blurb,$rawBlurb ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function pixel( $events, $addScriptTags=true ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->fb_pixel_id and \IPS\Settings::i()->fb_pixel_enabled ):
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $addScriptTags ):
$return .= <<<IPSCONTENT
<script>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

setTimeout( function() {
	
IPSCONTENT;

foreach ( $events as $name => $params ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$inlineParams = '';
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $params ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$inlineParams = json_encode( $params );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $inlineParams ):
$return .= <<<IPSCONTENT

		fbq('track', '
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
', {$inlineParams});
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		fbq('track', '
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
');
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

}, 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \intval( \IPS\Settings::i()->fb_pixel_delay * 1000 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 );

IPSCONTENT;

if ( $addScriptTags ):
$return .= <<<IPSCONTENT
</script>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function prefersColorSchemeLoad(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( (\IPS\Theme::i()->getCssVariableFromKey('set__i-change-scheme') == "1" && isset(\IPS\Widget\Request::i()->cookie['scheme_preference']) && \IPS\Widget\Request::i()->cookie['scheme_preference'] === 'system') || (\IPS\Theme::i()->getCssVariableFromKey('set__i-change-scheme') == "0" && \IPS\Theme::i()->getCssVariableFromKey('set__i-default-scheme') == "system") || (\IPS\Theme::i()->getCssVariableFromKey('set__i-change-scheme') == "1" && !isset(\IPS\Widget\Request::i()->cookie['scheme_preference']) && \IPS\Theme::i()->getCssVariableFromKey('set__i-default-scheme') == "system")  ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

/*
$return .= <<<IPSCONTENT
<!-- If you can change the color scheme and you've chosen System OR if you can't change the color scheme and it's set to System.. apply it -->
IPSCONTENT;

*/
$return .= <<<IPSCONTENT

	<script>(() => document.documentElement.setAttribute('data-ips-scheme', (window.matchMedia('(prefers-color-scheme:dark)').matches) ? 'dark':'light'))();</script>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function prefix( $encoded, $text ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $text ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$url = \IPS\Content\Tag::buildTagUrl( $text );
$return .= <<<IPSCONTENT

	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$sprintf = array($text); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'find_tagged_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" class='ipsBadge ipsBadge--prefix' rel="tag" data-tag-label='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $text, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $text, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function profileNextStep( $nextStep, $canDismiss=false, $hideOnCompletion=true ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$completion = \intval( (string) \IPS\Member::loggedIn()->profileCompletionPercentage() );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ! ( $completion == 100 and $hideOnCompletion ) ):
$return .= <<<IPSCONTENT

<div data-role='profileWidget' data-controller="core.front.core.profileCompletion">
	
IPSCONTENT;

if ( $completion < 100 ):
$return .= <<<IPSCONTENT

		<div class='i-flex i-align-items_center i-flex-wrap_wrap i-margin-bottom_2'>
			<div class='i-flex_91'>
				<h4 class="ipsTitle ipsTitle--h6">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_step_next', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "profile_step_title_{$nextStep->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				<small class='i-color_soft'>
IPSCONTENT;

$sprintf = array($completion . '%'); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_completion_percent', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</small>
			</div>
			<ul class="ipsButtons">
				
IPSCONTENT;

if ( $canDismiss ):
$return .= <<<IPSCONTENT

					<li>
						<a class="ipsButton ipsButton--small ipsButton--inherit" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=dismissProfile" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 )->addRef((string) \IPS\Request::i()->url()), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-role='dismissProfile'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dismiss', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li>
					<a class="ipsButton ipsButton--small ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=completion&_new=1", null, "settings", array(), 0 )->addRef((string) \IPS\Request::i()->url()), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'complete_my_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right-long"></i></a>
				</li>
			</ul>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
	<progress class="ipsProgress" min="0" max="100" value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->profileCompletionPercentage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></progress>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function queryLog( $log ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elQueryLog">
	<h3>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $log ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

foreach ( $log as $i => $query ):
$return .= <<<IPSCONTENT

		<div>
			<pre class="prettyprint language-sql" data-ipsDialog data-ipsDialog-content="#elQueryLog
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
IPSCONTENT;

if ( $query['server'] ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $query['server'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
): 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $query['query'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
			
IPSCONTENT;

if ( $query['extra'] ):
$return .= <<<IPSCONTENT

				<div class="i-text-align_center">
					<strong class="i-color_warning"><i class="fa-solid fa-circle-exclamation"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $query['extra'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
				</div>
				<br>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div id='elQueryLog
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='i-padding_3 ipsHide elQueryLogMenu'>
				<br>
				<pre class="prettyprint language-sql">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $query['query'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
				<hr class="ipsHr">
				<pre class="prettyprint language-php">
IPSCONTENT;

$return .= var_export( $query['backtrace'], true );
$return .= <<<IPSCONTENT
</pre>
				<br>
			</div>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function quickSearch(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'search' ) ) AND !\in_array('ipsLayout_minimal', \IPS\Output::i()->bodyClasses ) ):
$return .= <<<IPSCONTENT

	<div id="elSearchWrapper">
		<div id='elSearch' data-controller="core.front.core.quickSearch">
			<form accept-charset='utf-8' action='
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=search&controller=search&do=quicksearch", null, "search", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
' method='post'>
                <input type='search' id='elSearchField' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_placeholder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' name='q' autocomplete='off' aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
                <details class='cSearchFilter'>
                    <summary class='cSearchFilter__text'></summary>
                    <ul class='cSearchFilter__menu'>
                        
IPSCONTENT;

$option = \IPS\Output::i()->defaultSearchOption;
$return .= <<<IPSCONTENT

                        <li><label><input type="radio" name="type" value="all" 
IPSCONTENT;

if ( \IPS\Output::i()->defaultSearchOption[0] == 'all' ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><span class='cSearchFilter__menuText'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'everywhere', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></label></li>
                        
IPSCONTENT;

if ( \count( \IPS\Output::i()->contextualSearchOptions ) ):
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

foreach ( array_reverse( \IPS\Output::i()->contextualSearchOptions ) as $name => $data ):
$return .= <<<IPSCONTENT

                                <li><label><input type="radio" name="type" value='contextual_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $data ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

if ( \IPS\Output::i()->defaultSearchOption[0] == $data['type'] ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><span class='cSearchFilter__menuText'>
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></label></li>
                            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

foreach ( \IPS\Output::i()->globalSearchMenuOptions() as $type => $name ):
$return .= <<<IPSCONTENT

                            <li><label><input type="radio" name="type" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

if ( empty(\IPS\Output::i()->contextualSearchOptions) and \IPS\Output::i()->defaultSearchOption[0] == $type ):
$return .= <<<IPSCONTENT
 checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><span class='cSearchFilter__menuText'>
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></label></li>
                        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                    </ul>
                </details>
				<button class='cSearchSubmit' type="submit" aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-magnifying-glass"></i></button>
			</form>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function rating( $size, $value, $max=5, $memberRating=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div 
IPSCONTENT;

if ( $memberRating ):
$return .= <<<IPSCONTENT
data-ipsTooltip aria-label='
IPSCONTENT;

$sprintf = array($memberRating, $max, $value); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_rated_x_stars', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class='ipsRating 
IPSCONTENT;

if ( $memberRating ):
$return .= <<<IPSCONTENT
ipsRating_rated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $size ):
$return .= <<<IPSCONTENT
ipsRating_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( $memberRating ):
$return .= <<<IPSCONTENT

		<ul class='ipsRating_mine'>
			
IPSCONTENT;

foreach ( range( 1, $max ) as $i ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $i <= $memberRating ):
$return .= <<<IPSCONTENT

					<li class='ipsRating_on'>
						<i class='fa-solid fa-star'></i>
					</li>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<li class='ipsRating_off'>
						<i class='fa-solid fa-star'></i>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<ul class='ipsRating_collective' data-v="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

foreach ( range( 1, $max ) as $i ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $i <= $value ):
$return .= <<<IPSCONTENT

				<li class='ipsRating_on'>
					<i class='fa-solid fa-star'></i>
				</li>
			
IPSCONTENT;

elseif ( ( $i - 0.5 ) <= $value ):
$return .= <<<IPSCONTENT

				<li class='ipsRating_half'>
					<i class='fa-solid fa-star-half'></i><i class='fa-solid fa-star-half fa-flip-horizontal'></i>
				</li>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<li class='ipsRating_off'>
					<i class='fa-solid fa-star'></i>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function reactionBlurb( $content, $anonymized=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$reactions = \IPS\Content\Reaction::roots();
$return .= <<<IPSCONTENT

	<ul class='ipsReact_reactions'>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

			<li class="ipsReact_overview">
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->whoReacted(null, $anonymized), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $content->reactBlurb() AS $key => $count ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $reactions[ $key ] ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$reaction = $reactions[ $key ];
$return .= <<<IPSCONTENT

				<li class='ipsReact_reactCount'>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url('showReactions')->setQueryString( 'reaction', $reaction->id ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsTooltip-label="<strong>
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip-ajax="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url('showReactions')->setQueryString( array( 'reaction' => $reaction->id, 'tooltip' => 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip-safe title="
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( 'reaction_title_' . $reaction->id )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted_x', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span data-ipsTooltip title="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<span>
								<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" loading="lazy">
							</span>
							<span>
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $count, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</span>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

	<span class='i-link-color_inherit'>
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->whoReacted(null, $anonymized), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_remove_reactions') ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url('showReactions'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>&nbsp;<i class="fa-solid fa-pencil"></i></a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reactionLog( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li class='ipsPhotoPanel ipsPhotoPanel--mini ipsPhotoPanel--reaction-log'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $row['member_id'] ), 'mini' );
$return .= <<<IPSCONTENT

		<div class="ipsPhotoPanel__text">
			<h3 class="ipsPhotoPanel__primary">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( \IPS\Member::load( $row['member_id'] ) );
$return .= <<<IPSCONTENT
</h3>
			<div class="ipsPhotoPanel__secondary">
				<span>
					
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->reaction ) || \IPS\Widget\Request::i()->reaction == 'all' ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$reaction = \IPS\Content\Reaction::load( $row['reaction'] );
$return .= <<<IPSCONTENT

						<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' height='20' width='20' loading="lazy" alt="">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 <span>{$row['rep_date']}</span>
				</span>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_remove_reactions') and \count( $row['_buttons'] ) ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['_buttons']['delete']['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $row['_buttons']['delete']['data'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $row['_buttons']['delete']['data'] as $k => $v ):
$return .= <<<IPSCONTENT

							data-
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

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="i-margin-start_icon"><i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['_buttons']['delete']['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i></a>
				
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

		return $return;
}

	function reactionLogTable( $table, $headers, $rows, $quickSearch ) {
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
' data-controller='core.global.core.table' 
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
>

	
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

		<ol class='ipsGrid i-basis_300 i-padding_3 
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

		<div class='i-padding_3 i-text-align_center'>
			<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rows_in_table', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

if ( method_exists( $table, 'container' ) AND $table->container() !== NULL ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $table->container()->can('add') ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->container()->url()->setQueryString( 'do', 'add' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_first_row', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
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

	function reactionOverview( $content, $showCount=TRUE, $size=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$reactions = $content->reputation;
$return .= <<<IPSCONTENT


IPSCONTENT;

$reactionCount = is_array( $reactions ) ? count( $reactions ) : 0;
$return .= <<<IPSCONTENT

<div class='ipsReactOverview
	
IPSCONTENT;

if ( \IPS\Settings::i()->reaction_count_display == 'count' ):
$return .= <<<IPSCONTENT

		ipsReactOverview--points
		
IPSCONTENT;

if ( $reactionCount == 0 ):
$return .= <<<IPSCONTENT
ipsReactOverview--none
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		ipsReactOverview--reactions
		
IPSCONTENT;

if ( ! is_array( $reactions ) or ! \count( $reactions ) ):
$return .= <<<IPSCONTENT

			ipsReactOverview--none
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $size ):
$return .= <<<IPSCONTENT
ipsReactOverview_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( \IPS\Settings::i()->reaction_count_display == 'count' ):
$return .= <<<IPSCONTENT

		<div class='i-text-align_center'>
			<span class='ipsReact_reactCountOnly i-text-align_center 
IPSCONTENT;

if ( $reactionCount >= 1 ):
$return .= <<<IPSCONTENT
i-background_positive
IPSCONTENT;

elseif ( $reactionCount < 0 ):
$return .= <<<IPSCONTENT
i-background_negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-background_2
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-link-color_inherit'>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url('showReactions'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reactionCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</span>
		</div>
		<p class='i-text-align_center'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'repuation_points', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $content->reactBlurb( $content->reputation ?? null ) ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$isItem = ( $content instanceof \IPS\Content\Item ) ? 1 : 0;
$return .= <<<IPSCONTENT

			<ul>
				
IPSCONTENT;

foreach ( array_reverse( $content->reactBlurb(), TRUE ) AS $key => $count ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$reaction = \IPS\Content\Reaction::load( $key );
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

if (\IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url('showReactions')->setQueryString( array( 'reaction' => $reaction->id, 'item' => $isItem ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip title="
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( 'reaction_title_' . $reaction->id )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted_x', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span data-ipsTooltip title="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" loading="lazy" width="120" height="120">
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

							</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \is_array( $reactions ) ):
$return .= <<<IPSCONTENT

		    
IPSCONTENT;

if ( $showCount && $size == 'small' && \count( $reactions ) ):
$return .= <<<IPSCONTENT

			    <span>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $reactions ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		    
IPSCONTENT;

elseif ( $showCount ):
$return .= <<<IPSCONTENT

			    <p class='i-text-align_center'>
				    
IPSCONTENT;

$pluralize = array( \count( $reactions ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'react_total', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

			    </p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function reactionTabs( $tabs, $activeId, $defaultContent, $url, $tabParam='tab', $parseNames=TRUE, $contained=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
<div class='ipsBox'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<i-tabs class='ipsTabs cReactionTabs' id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
data-ipsTabBar-updateURL='false'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<div role='tablist'>
		
IPSCONTENT;

foreach ( $tabs as $i => $tab ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( $tabParam, $i ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsTabs__tab 
IPSCONTENT;

if ( isset( $tab['count'] ) && $tab['count'] == 0 ):
$return .= <<<IPSCONTENT
ipsTabs__tab--disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

if ( $parseNames ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= strip_tags( \IPS\Member::loggedIn()->language()->get( $tab['title'] ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= strip_tags( $tab['title'] );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' role="tab" aria-controls='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' aria-selected="
IPSCONTENT;

if ( $i == $activeId ):
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

if ( isset( $tab['icon'] ) ):
$return .= <<<IPSCONTENT

					<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab["icon"]->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' width='20' height='20' alt="
IPSCONTENT;

$val = "reaction_title_{$i}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$val = "reaction_title_{$i}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" loading="lazy">
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $parseNames ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$tab['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
{$tab['title']}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $tab['count'] ) ):
$return .= <<<IPSCONTENT

					<span class='i-opacity_8'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
				
IPSCONTENT;

endif;
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
<section id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels 
IPSCONTENT;

if ( $contained ):
$return .= <<<IPSCONTENT
ipsTabs__panels--padded
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

foreach ( $tabs as $i => $tab ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $i == $activeId ):
$return .= <<<IPSCONTENT

			<div id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel" aria-labelledby="ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				{$defaultContent}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</section>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reactionTooltip( $reaction, $names, $others ) {
		$return = '';
		$return .= <<<IPSCONTENT

<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>

IPSCONTENT;

foreach ( $names as $name ):
$return .= <<<IPSCONTENT

	<br>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $others ):
$return .= <<<IPSCONTENT

	<br>
IPSCONTENT;

$pluralize = array( $others ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'react_blurb_others_secondary', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reputation( $content, $extraClass='', $forceType=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $content, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and $enabledReactions = \IPS\Content\Reaction::enabledReactions() ):
$return .= <<<IPSCONTENT

	<div data-controller='core.front.core.reaction' class='ipsReact 
IPSCONTENT;

if ( $extraClass ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $extraClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>	
		
IPSCONTENT;

if ( \IPS\Settings::i()->reaction_count_display == 'count' ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$reactionCount = $content->reactionCount();
$return .= <<<IPSCONTENT

			<div class='ipsReact_reactCountOnly 
IPSCONTENT;

if ( $reactionCount >= 1 ):
$return .= <<<IPSCONTENT
i-background_positive
IPSCONTENT;

elseif ( $reactionCount < 0 ):
$return .= <<<IPSCONTENT
i-background_negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-background_2
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-link-color_inherit 
IPSCONTENT;

if ( !\count( $content->reactions() ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='reactCount'>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url('showReactions'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<span data-role='reactCountText'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reactionCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$reactBlurb = $content->reactBlurb();
$return .= <<<IPSCONTENT

			<div class='ipsReact_blurb 
IPSCONTENT;

if ( !$reactBlurb ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='reactionBlurb'>
				
IPSCONTENT;

if ( $reactBlurb ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionBlurb( $content );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( $content->canReact() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$defaultReaction = reset( $enabledReactions );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$reactButton = ( $reacted = $content->reacted() and isset( $enabledReactions[ $reacted->id ] ) ) ? $enabledReactions[ $reacted->id ] : $defaultReaction;
$return .= <<<IPSCONTENT


			<div class='ipsReact_types' data-role='reactionInteraction' data-unreact="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( 'unreact' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

if ( !\IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

					<ul data-role='reactTypes' hidden>
						
IPSCONTENT;

foreach ( $enabledReactions as $reaction ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $reaction->id == $reactButton->id ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

continue;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


							<li>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( 'react' )->setQueryString( 'reaction', $reaction->id )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsReact_reaction' data-role="reaction" 
IPSCONTENT;

if ( $reaction->id == $defaultReaction->id ):
$return .= <<<IPSCONTENT
data-defaultReaction
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<img src='
IPSCONTENT;

if ( $reaction->use_custom ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" loading="lazy" width="120" height="120">
									<span class='ipsReact_name'>
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				<span class='ipsReact_button 
IPSCONTENT;

if ( $reacted !== FALSE ):
$return .= <<<IPSCONTENT
ipsReact_reacted
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action='reactLaunch'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( 'react' )->setQueryString( 'reaction', $reactButton->id )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsReact_reaction' data-role="reaction" 
IPSCONTENT;

if ( $reactButton->id == $defaultReaction->id ):
$return .= <<<IPSCONTENT
data-defaultReaction
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<img src='
IPSCONTENT;

if ( $reactButton->use_custom ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reactButton->_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reactButton->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

$val = "reaction_title_{$reactButton->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$val = "reaction_title_{$reactButton->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" loading="lazy" width="120" height="120">
						<span class='ipsReact_name'>
IPSCONTENT;

$val = "reaction_title_{$reactButton->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</a>
				</span>

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( 'unreact' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsReact_unreact 
IPSCONTENT;

if ( $reacted == FALSE ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action='unreact' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reaction_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>&times;</a>
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

	function reputationBadge( $author ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_show_profile and $author->member_id ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$author->member_id}&do=reputation", null, "profile_reputation", array( $author->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_badge_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip class='ipsRepBadge 
IPSCONTENT;

if ( $author->pp_reputation_points > 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--positive
IPSCONTENT;

elseif ( $author->pp_reputation_points < 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsRepBadge_neutral
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<span title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_badge_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip class='ipsRepBadge 
IPSCONTENT;

if ( $author->pp_reputation_points > 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--positive
IPSCONTENT;

elseif ( $author->pp_reputation_points < 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsRepBadge_neutral
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<i class='fa-solid 
IPSCONTENT;

if ( $author->pp_reputation_points > 0 ):
$return .= <<<IPSCONTENT
fa-plus-circle
IPSCONTENT;

elseif ( $author->pp_reputation_points < 0 ):
$return .= <<<IPSCONTENT
fa-minus-circle
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-circle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( abs( $author->pp_reputation_points ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

		</a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reputationLog( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li class='ipsPhotoPanel ipsPhotoPanel--mini'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $row['member_id'] ), 'mini' );
$return .= <<<IPSCONTENT

		<div class="ipsPhotoPanel__text">
			<h3 class="ipsPhotoPanel__primary">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( \IPS\Member::load( $row['member_id'] ) );
$return .= <<<IPSCONTENT
</h3>
			<div class="ipsPhotoPanel__secondary">
				
IPSCONTENT;

if ( $row['rep_rating'] === '1' && \IPS\Settings::i()->reputation_point_types != 'like' ):
$return .= <<<IPSCONTENT
<i class='i-font-size_2 i-color_positive fa-solid fa-circle-arrow-up'></i>
IPSCONTENT;

elseif ( \IPS\Settings::i()->reputation_point_types != 'like' ):
$return .= <<<IPSCONTENT
<i class='i-font-size_2 i-color_negative fa-solid fa-circle-arrow-down'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 <span>
IPSCONTENT;

$val = ( $row['rep_date'] instanceof \IPS\DateTime ) ? $row['rep_date'] : \IPS\DateTime::ts( $row['rep_date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
			</div>
		</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reputationLogTable( $table, $headers, $rows, $quickSearch ) {
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
' data-controller='core.global.core.table' 
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
>

	
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

		<ol class='ipsGrid i-basis_300 i-padding_3 
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
' data-role="tableRows">
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='i-padding_3 i-text-align_center'>
			<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rows_in_table', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

if ( method_exists( $table, 'container' ) AND $table->container() !== NULL ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $table->container()->can('add') ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->container()->url()->setQueryString( 'do', 'add' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_first_row', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
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

	function reputationMini( $content, $allowRep=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$allowRep = TRUE;
$return .= <<<IPSCONTENT

<div data-controller='core.front.core.reaction' class='ipsReact ipsReact_mini 
IPSCONTENT;

if ( !$allowRep ):
$return .= <<<IPSCONTENT
ipsReact_miniNoInteraction
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
	
IPSCONTENT;

if ( $content ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->reaction_count_display == 'count' ):
$return .= <<<IPSCONTENT

			<div class='ipsReact_reactCountOnly ipsReact_reactCountOnly_mini 
IPSCONTENT;

if ( $content->reactionCount() >= 1 ):
$return .= <<<IPSCONTENT
i-background_positive
IPSCONTENT;

elseif ( $content->reactionCount() < 0 ):
$return .= <<<IPSCONTENT
i-background_negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-background_2
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-link-color_inherit 
IPSCONTENT;

if ( !\count( $content->reactions() ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='reactCount'>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url('showReactions'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<span data-role='reactCountText'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->reactionCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='ipsReact_blurb 
IPSCONTENT;

if ( !$content->reactBlurb() ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='reactionBlurb'>
				
IPSCONTENT;

if ( $content->reactBlurb() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionBlurb( $content );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( $content->canReact() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$reactButton = NULL;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$defaultReaction = NULL;
$return .= <<<IPSCONTENT

	
			
IPSCONTENT;

foreach ( \IPS\Content\Reaction::roots() AS $id => $reaction ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !$defaultReaction ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$defaultReaction = $reaction;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( ( $content->reacted() !== FALSE && $reaction->id == $content->reacted()->id ) || ( $content->reacted() === FALSE ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$reactButton = $reaction;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $allowRep ):
$return .= <<<IPSCONTENT

				<span class='ipsReact_count ipsHide' data-role="reactCount">
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url('showReactions'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $content->reactions() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</a>
				</span>
				<div class='ipsReact_types' data-role='reactionInteraction' data-unreact="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( 'unreact' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

if ( !\IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

						<ul data-role='reactTypes'>
							
IPSCONTENT;

foreach ( \IPS\Content\Reaction::roots() AS $id => $reaction ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $reaction->id == $reactButton->id OR $reaction->_enabled === FALSE ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

continue;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
								<li>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( 'react' )->setQueryString( 'reaction', $reaction->id )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsReact_reaction' data-role="reaction" 
IPSCONTENT;

if ( $reaction->id == $defaultReaction->id ):
$return .= <<<IPSCONTENT
data-defaultReaction
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
										<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
">
										<span class='ipsReact_name'>
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
									</a>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							<li>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( 'unreact' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsReact_unreact 
IPSCONTENT;

if ( $content->reacted() == FALSE ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action='unreact' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reaction_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>&times;</a>
							</li>
						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
					<span class='ipsReact_button 
IPSCONTENT;

if ( $content->reacted() !== FALSE ):
$return .= <<<IPSCONTENT
ipsReact_reacted
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action='reactLaunch'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( 'react' )->setQueryString( 'reaction', $reactButton->id )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsReact_reaction' data-role="reaction" 
IPSCONTENT;

if ( $reactButton->id == $defaultReaction->id ):
$return .= <<<IPSCONTENT
data-defaultReaction
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
							<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reactButton->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

$val = "reaction_title_{$reactButton->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$val = "reaction_title_{$reactButton->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
">
							<span class='ipsReact_name'>
IPSCONTENT;

$val = "reaction_title_{$reactButton->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</a>
					</span>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function reputationOthers( $contentURL, $lang, $names ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contentURL, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-destructOnClose data-ipsDialog-size='medium' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_liked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsTooltip-label='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $names, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsTooltip-json data-ipsTooltip-safe>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contentURL, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-destructOnClose data-ipsDialog-size='medium' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsTooltip-label='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $names, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsTooltip-json data-ipsTooltip-safe>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function review( $item, $review, $editorName, $app, $type ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap" data-controller="core.front.core.comment" data-feedid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commentapp="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commenttype="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-review" data-commentid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-quotedata="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( array('userid' => $review->author()->member_id, 'username' => $review->author()->name, 'timestamp' => $review->mapped('date'), 'contentapp' => $app, 'contenttype' => $type, 'contentid' => $item->id, 'contentcommentid' => $review->id) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsEntry__content js-ipsEntry__content" 
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $review, 'IPS\Content\IntersectionViewTracking' ) AND $hash=$review->getViewTrackingHash() ):
$return .= <<<IPSCONTENT
 data-view-hash="{$hash}" data-view-tracking-data="
IPSCONTENT;

$return .= base64_encode(json_encode( $review->getViewTrackingData() ));
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<header class="ipsEntry__header">
		<div class="ipsEntry__header-align">
			<div class="ipsPhotoPanel">
				<div class="ipsAvatarStack">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $review->author(), 'fluid', $review->warningRef() );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $review->author()->canHaveAchievements() and \IPS\core\Achievements\Rank::show() and $rank = $review->author()->rank() ):
$return .= <<<IPSCONTENT

						{$rank->html( 'ipsAvatarStack__rank' )}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsPhotoPanel__text">
					<div class="ipsEntry__username">
						<h3>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $review->author(), $review->warningRef(), NULL, \IPS\IPS::classUsesTrait( $review, 'IPS\Content\Anonymous' ) ? $review->isAnonymous() : FALSE );
$return .= <<<IPSCONTENT
</h3>
						
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $review, 'IPS\Content\Anonymous' ) AND !$review->isAnonymous() ):
$return .= <<<IPSCONTENT

							<span class="ipsEntry__group">
IPSCONTENT;

$return .= \IPS\Member\Group::load( $review->author()->member_group_id )->formattedName;
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<p class="ipsPhotoPanel__secondary">
						
IPSCONTENT;

if ( $review->mapped('date') ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$val = ( $review->mapped('date') instanceof \IPS\DateTime ) ? $review->mapped('date') : \IPS\DateTime::ts( $review->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unknown_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->editLine() ):
$return .= <<<IPSCONTENT

							(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edited_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
			
IPSCONTENT;

if ( \count( $item->reviewMultimodActions() ) ):
$return .= <<<IPSCONTENT

				<input type="checkbox" name="multimod[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" data-role="moderation" data-actions="
IPSCONTENT;

if ( $review->hidden() === -1 AND $review->canUnhide() ):
$return .= <<<IPSCONTENT
unhide
IPSCONTENT;

elseif ( $review->hidden() === 1 AND $review->canUnhide() ):
$return .= <<<IPSCONTENT
approve
IPSCONTENT;

elseif ( $review->canHide() ):
$return .= <<<IPSCONTENT
hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $review->canDelete() ):
$return .= <<<IPSCONTENT
delete
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-state="
IPSCONTENT;

if ( $review->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			{$review->menu()}
			
IPSCONTENT;

if ( $review->author()->member_id ):
$return .= <<<IPSCONTENT

				<!-- Expand mini profile -->
				<button class="ipsEntry__topButton ipsEntry__topButton--profile" type="button" aria-controls="mini-profile-review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-expanded="false" data-ipscontrols aria-label="Toggle mini profile"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $review->author()->member_id ):
$return .= <<<IPSCONTENT


			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->miniProfile( $review, $review->id, 'review' );
$return .= <<<IPSCONTENT

			
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class="ipsEntry__post">
		
IPSCONTENT;

if ( $review->hidden() AND $review->hidden() != -2 ):
$return .= <<<IPSCONTENT

			<div class="ipsEntry__hiddenMessage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

elseif ( $review->hidden() == -2 ):
$return .= <<<IPSCONTENT

			<div class="ipsEntry__hiddenMessage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->deletedBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsRating ipsRating_large i-margin-bottom_3">
			<ul>
				
IPSCONTENT;

foreach ( range( 1, \intval( \IPS\Settings::i()->reviews_rating_out_of ) ) as $i ):
$return .= <<<IPSCONTENT

					<li class="
IPSCONTENT;

if ( $review->mapped('rating') >= $i ):
$return .= <<<IPSCONTENT
ipsRating_on
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsRating_off
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						<i class="fa-solid fa-star"></i>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		   
IPSCONTENT;

if ( $review->mapped('votes_total') ):
$return .= <<<IPSCONTENT
<strong>{$review->helpfulLine()}</strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div id="review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsRichText ipsRichText--user" data-role="commentContent" data-controller="core.front.core.lightboxedImages">
			{$review->content()}
			
IPSCONTENT;

if ( $review->editLine() ):
$return .= <<<IPSCONTENT

				{$review->editLine()}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>

		
IPSCONTENT;

if ( $review->hasAuthorResponse() ):
$return .= <<<IPSCONTENT

			<div class="ipsReviewResponse i-padding_3 i-margin-bottom_3 i-background_2">
				<div class="i-flex i-align-items_center i-justify-content_space-between i-margin-bottom_2">
					<h4 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_response_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					
IPSCONTENT;

if ( $review->canEditResponse() OR $review->canDeleteResponse() ):
$return .= <<<IPSCONTENT

						<a href="#elControlsReviews_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_response_menu" class="ipsEntry__topButton ipsEntry__topButton--ellipsis" id="elControlsReviews_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_response" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-appendto="#review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap"><i class="fa-solid fa-ellipsis"></i></a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div data-role="reviewResponse" class="ipsRichText" data-controller="core.front.core.lightboxedImages">{$review->mapped('author_response')}</div>

				
IPSCONTENT;

if ( $review->canEditResponse() OR $review->canDeleteResponse() ):
$return .= <<<IPSCONTENT

					<ul class="ipsMenu ipsMenu_narrow ipsHide" id="elControlsReviews_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_response_menu">
						
IPSCONTENT;

if ( $review->canEditResponse() ):
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('editResponse'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_author_respond', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->canDeleteResponse() ):
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('deleteResponse')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $review->hidden() !== 1 ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id and ( !$review->mapped('votes_data') or !array_key_exists( \IPS\Member::loggedIn()->member_id, json_decode( $review->mapped('votes_data'), TRUE ) ) ) and $review->author()->member_id != \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				<div class="i-flex i-align-items_center i-flex-wrap_wrap i-gap_2 i-margin-top_3">
					<div class="i-font-weight_500 i-color_hard i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'did_you_find_this_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
					<div class="ipsButtons i-font-size_-2">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('rate')->setQueryString( 'helpful', TRUE )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-color_positive" data-action="rateReview"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'yes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('rate')->setQueryString( 'helpful', FALSE )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-color_negative" data-action="rateReview"><i class="fa-solid fa-xmark"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

	
IPSCONTENT;

if ( ( \IPS\Member::loggedIn()->member_id and ( !$review->mapped('votes_data') or !array_key_exists( \IPS\Member::loggedIn()->member_id, json_decode( $review->mapped('votes_data'), TRUE ) ) ) ) || $review->canEdit() || $review->canDelete() || $review->canHide() || $review->canUnhide() || ( $review->hidden() !== 1 && \IPS\IPS::classUsesTrait( $review, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and $review->hasReactionBar() ) ):
$return .= <<<IPSCONTENT

		<div class="ipsEntry__footer">
			
IPSCONTENT;

if ( $review->canEdit() || $review->canDelete() || $review->canHide() || $review->canUnhide() || ( $review->hidden() !== 1 && $review->canRespond() )  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/review", "commentControls:before", [ $item,$review,$editorName,$app,$type ] );
$return .= <<<IPSCONTENT
<menu class="ipsEntry__controls" data-role="commentControls" data-ips-hook="commentControls">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/review", "commentControls:inside-start", [ $item,$review,$editorName,$app,$type ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $review->hidden() === 1 && ( $review->canUnhide() || $review->canDelete() ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->canUnhide() ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('unhide')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="approveComment"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->canDelete() ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('delete')->csrf()->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="deleteComment" data-updateondelete="#commentCount"><i class="fa-solid fa-xmark"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->canEdit() || $review->canSplit() ):
$return .= <<<IPSCONTENT

							<li>
								<a href="#elControlsReviewsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="elControlsReviewsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-appendto="#review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
								<ul id="elControlsReviewsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="ipsMenu ipsMenu_narrow ipsHide">
									
IPSCONTENT;

if ( $review->canEdit() ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $review->mapped('first') and $review->item()->canEdit() ):
$return .= <<<IPSCONTENT

											<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->url()->setQueryString( 'do', 'edit' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('edit'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="editComment">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $review->canSplit() ):
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('split'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="splitComment" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $item::$title )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split_to_new', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( $review->hidden() !== 1 && $review->canRespond() ):
$return .= <<<IPSCONTENT

                        <li>
                            <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('respond'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="respond" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_author_respond', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_author_respond', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
                        </li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $review, FALSE );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
					<li class="ipsHide" data-role="commentLoading">
						<span class="ipsLoading ipsLoading--tiny"></span>
					</li>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/review", "commentControls:inside-end", [ $item,$review,$editorName,$app,$type ] );
$return .= <<<IPSCONTENT
</menu>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/review", "commentControls:after", [ $item,$review,$editorName,$app,$type ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $review->hidden() !== 1 && \IPS\IPS::classUsesTrait( $review, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $review );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->sharemenu( $review );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function reviewContainer( $item, $review ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $review::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $review->isIgnored() ):
$return .= <<<IPSCONTENT

	<div class='ipsEntry ipsEntry--ignored'>
		<i class="fa-solid fa-user-slash"></i> 
IPSCONTENT;

$sprintf = array($review->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ignoring_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<a id='review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></a>
	<a id='findReview-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></a>
	<article id="elReview_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsEntry js-ipsEntry ipsEntry--simple ipsEntry--review 
IPSCONTENT;

if ( $review->hidden() OR $item->hidden() == -2 ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->review( $item, $review, $item::$formLangPrefix . 'review', $item::$application, $item::$module );
$return .= <<<IPSCONTENT

	</article>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reviewHelpful( $helpful, $total ) {
		$return = '';
		$return .= <<<IPSCONTENT


<span class='ipsResponsive_hidePhone'>
	
IPSCONTENT;

$sprintf = array($helpful, \IPS\Member::loggedIn()->language()->pluralize( \IPS\Member::loggedIn()->language()->get( 'x_members' ), array( $total ) )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_members_found_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

</span>
<span class='ipsResponsive_showPhone'>
	<i class='fa-solid fa-face-smile'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $helpful, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 / 
IPSCONTENT;

$pluralize = array( $total ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_members_found_helpful_phone', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

</span>
IPSCONTENT;

		return $return;
}

	function rssMenu(  ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( \IPS\Output::i()->rssFeeds ) ):
$return .= <<<IPSCONTENT

	<a href='#' id='elRSS' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'available_rss', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsMenu data-ipsMenu-above><i class="fa-solid fa-rss"></i> RSS</a>
	<ul id='elRSS_menu' class='ipsMenu ipsMenu_auto ipsHide'>
		
IPSCONTENT;

foreach ( \IPS\Output::i()->rssFeeds as $title => $url ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item'><a title="
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

		return $return;
}

	function searchDialog(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ( \IPS\Settings::i()->site_online || \IPS\Member::loggedIn()->group['g_access_offline'] ) and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'search' ) ) ):
$return .= <<<IPSCONTENT

<dialog class='ipsSearchDialog' id='ipsSearchDialog' data-ips-hidden-light-dismiss>
	<search role="search">
		<form class='ipsSearchDialog__modal' accept-charset='utf-8' action='
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=search&controller=search&do=quicksearch", null, "search", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
' method='post'>
			<div class='ipsSearchDialog__input'>
				<input type='text' id='ipsSearchDialog__input' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_placeholder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' name='q' autocomplete='off' aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-autofocus>
				<button type='submit' class="ipsButton ipsButton--primary"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right"></i></button>
			</div>
			<div class='ipsSearchDialog__filters ipsFluid i-gap_lines'>
				<div>
					<div class='ipsSimpleSelect'>
						<i class="fa-regular fa-file-lines"></i>
						<select name="type">
							
IPSCONTENT;

$option = \IPS\Output::i()->defaultSearchOption;
$return .= <<<IPSCONTENT

							<option value="all" 
IPSCONTENT;

if ( \IPS\Output::i()->defaultSearchOption[0] == 'all' ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'everywhere', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

if ( \count( \IPS\Output::i()->contextualSearchOptions ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

foreach ( array_reverse( \IPS\Output::i()->contextualSearchOptions ) as $name => $data ):
$return .= <<<IPSCONTENT

									<option value='contextual_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $data ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Output::i()->defaultSearchOption[0] == $data['type'] ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( \IPS\Output::i()->globalSearchMenuOptions() as $type => $name ):
$return .= <<<IPSCONTENT

								<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( empty(\IPS\Output::i()->contextualSearchOptions) and \IPS\Output::i()->defaultSearchOption[0] == $type ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</select>
					</div>
				</div>
				<div>
					<label for='search-modal__find-results-in' class='ipsInvisible'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'searchIn', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsSimpleSelect'>
						<i class="fa-regular fa-file-lines"></i>
						<select name="search_in" id="search-modal__find-results-in">
							<option value="all" selected>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_titles_and_body', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="titles">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_titles', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
						</select>
					</div>
				</div>
				<div>
					<label for='search-modal__date-created' class='ipsInvisible'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'startDate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsSimpleSelect'>
						<i class="fa-regular fa-pen-to-square"></i>
						<select name="startDate" id="search-modal__date-created">
							<option value="any" selected>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_created_any', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="day">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_created_day', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="week">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_created_week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="month">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_created_month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="six_months">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_created_six_months', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="year">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_created_year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
						</select>
					</div>
				</div>
				<div hidden>
					<input type="hidden" name="search_and_or" value="or">
					<!-- <label for='search-modal__find-results-that-contain' class='ipsInvisible'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'andOr', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsSimpleSelect'>
						<i class="fa-solid fa-list-check"></i>
						<select name="search_and_or" id="search-modal__find-results-that-contain">
							<option value="and" selected>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_use_all_terms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="or">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_use_any_terms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
						</select>
					</div> -->
				</div>
				<div>
					<label for='search-modal__last-updated' class='ipsInvisible'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updatedDate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsSimpleSelect'>
						<i class="fa-regular fa-calendar"></i>
						<select name="updatedDate" id="search-modal__last-updated">
							<option value="any" selected>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_updated_any', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="day">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_updated_day', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="week">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_updated_week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="month">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_updated_month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="six_months">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_updated_six_months', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							<option value="year">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_modal_updated_year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
						</select>
					</div>
				</div>
			</div>
		</form>
	</search>
</dialog>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function searchDialogTrigger(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ( \IPS\Settings::i()->site_online || \IPS\Member::loggedIn()->group['g_access_offline'] ) and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'search' ) ) ):
$return .= <<<IPSCONTENT

	<button class='ipsSearchPseudo' aria-controls="ipsSearchDialog" aria-expanded="false" data-ipscontrols>
		<i class="fa-solid fa-magnifying-glass"></i>
		<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_trigger', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
	</button>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function sharelinks( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $item->sharelinks() )  ):
$return .= <<<IPSCONTENT

	<ul class='ipsShareLinks' data-controller="core.front.core.sharelink">
		
IPSCONTENT;

foreach ( $item->sharelinks() as $sharelink  ):
$return .= <<<IPSCONTENT

			<li>{$sharelink}</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $shareData = $item->webShareData() ):
$return .= <<<IPSCONTENT

	<button class='ipsHide ipsButton ipsButton--small ipsButton--soft ipsButton--wide i-margin-top_2' data-controller='core.front.core.webshare' data-role='webShare' data-webShareTitle='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $shareData['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-webShareText='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $shareData['text'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-webShareUrl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $shareData['url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_share_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function sharemenu( $comment ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Shareable' ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $comment::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

$type = ( $comment instanceof \IPS\Content\Review ) ? 'review' : 'comment';
$return .= <<<IPSCONTENT


<div class='i-padding_3 ipsHide cPostShareMenu' id='elShare
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \ucfirst( $type ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
	<h5 class="i-font-weight_600 i-color_hard i-margin-bottom_2">
IPSCONTENT;

$val = "link_to_$type"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h5>
	
IPSCONTENT;

if ( $comment->isFirst()  ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$url = $comment->item()->url();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$url = $comment->shareableUrl( $type );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->ref_on ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$url = $url->setQueryString( array( '_rid' => \IPS\Member::loggedIn()->member_id  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsPageActions__mainLink" data-role="shareButton" data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy_share_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-regular fa-copy"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

	
IPSCONTENT;

if ( (!$comment->item()->containerWrapper() OR !$comment->item()->container()->disable_sharelinks ) and \count( $comment->sharelinks() ) ):
$return .= <<<IPSCONTENT

		<h5 class="i-font-weight_600 i-color_hard i-margin-top_3 i-margin-bottom_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'share_externally', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h5>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->sharelinks( $comment );
$return .= <<<IPSCONTENT

	
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

	function sidebar(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$adsForceSidebar = ( \IPS\Settings::i()->ads_force_sidebar AND \IPS\core\Advertisement::loadByLocation( 'ad_sidebar' ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Output::i()->showSidebar() ):
$return .= <<<IPSCONTENT

	<aside id="ipsLayout_sidebar" class="ipsLayout__secondary-column" data-controller="core.front.widgets.sidebar">
		<div class="ipsLayout__secondary-sticky-outer">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "sidebar:before", [  ] );
$return .= <<<IPSCONTENT
<div class="ipsLayout__secondary-sticky-inner" data-ips-hook="sidebar">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "sidebar:inside-start", [  ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $announcements = \IPS\core\Announcements\Announcement::loadAllByLocation( 'sidebar' ) AND \IPS\Output::i()->sidebarHasContent() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->announcementSidebar( $announcements );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( \IPS\Output::i()->sidebar['contextual'] ) and !empty( trim( \IPS\Output::i()->sidebar['contextual'] ) ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "contextualSidebar:before", [  ] );
$return .= <<<IPSCONTENT
<div id="elContextualTools" 
IPSCONTENT;

if ( isset( \IPS\Output::i()->sidebar['sticky'] ) ):
$return .= <<<IPSCONTENT
data-class="i-position_sticky-top" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ips-hook="contextualSidebar">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "contextualSidebar:inside-start", [  ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Output::i()->sidebar['contextual'];
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "contextualSidebar:inside-end", [  ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "contextualSidebar:after", [  ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $adsForceSidebar OR ( \IPS\core\Advertisement::loadByLocation( 'ad_sidebar' ) AND \IPS\Output::i()->sidebarHasContent() ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "adSidebar:before", [  ] );
$return .= <<<IPSCONTENT
<div data-role="sidebarAd" data-ips-hook="adSidebar">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "adSidebar:inside-start", [  ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\core\Advertisement::loadByLocation( 'ad_sidebar' );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "adSidebar:inside-end", [  ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "adSidebar:after", [  ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->widgetContainer( 'sidebar', 'vertical' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "sidebar:inside-end", [  ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/sidebar", "sidebar:after", [  ] );
$return .= <<<IPSCONTENT

		</div>
	</aside>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function signature( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $member->canEditSignature() AND !\IPS\Member::loggedIn()->isIgnoring( $member, 'signatures' ) AND \IPS\Member::loggedIn()->members_bitoptions['view_sigs'] ):
$return .= <<<IPSCONTENT

	<div data-role="memberSignature" class='ipsEntry__signature 
IPSCONTENT;

if ( !\IPS\Settings::i()->signatures_mobile ):
$return .= <<<IPSCONTENT
ipsResponsive_hidePhone
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$uniqid = mt_rand();
$return .= <<<IPSCONTENT

			<div class='i-float_end'>
				<a href='#elSigIgnore
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uniqid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' data-memberID="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='elSigIgnore
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uniqid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='signatureOptions' data-ipsMenu class='i-color_soft' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_signature_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
					<i class='fa-solid fa-xmark'></i> <i class='fa-solid fa-caret-down'></i>
				</a>

				<ul class='ipsMenu ipsMenu_medium ipsHide' id='elSigIgnore
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uniqid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id != $member->member_id AND $member->canBeIgnored() ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item' data-ipsMenuValue='oneSignature'>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore&do=ignoreType&type=signatures&member_id={$member->member_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "ignore", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide_members_signature', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li class='ipsMenu_item' data-ipsMenuValue='allSignatures'>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=toggleSigs" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 )->addRef((string) \IPS\Request::i()->url()), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide_all_signatures', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				</ul>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div class='ipsRichText i-color_soft'>
			{$member->signature}
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function siteSocialProfiles(   ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->site_social_profiles AND $links = json_decode( \IPS\Settings::i()->site_social_profiles, TRUE ) AND \count( $links ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $links as $profile ):
$return .= <<<IPSCONTENT

		<li>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $profile['key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' class='ipsSocialIcons__icon ipsSocialIcons__icon--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $profile['value'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel='noopener noreferrer'>
				
IPSCONTENT;

if ( $profile['value'] === 'facebook' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M512 256C512 114.6 397.4 0 256 0S0 114.6 0 256C0 376 82.7 476.8 194.2 504.5V334.2H141.4V256h52.8V222.3c0-87.1 39.4-127.5 125-127.5c16.2 0 44.2 3.2 55.7 6.4V172c-6-.6-16.5-1-29.6-1c-42 0-58.2 15.9-58.2 57.2V256h83.6l-14.4 78.2H287V510.1C413.8 494.8 512 386.9 512 256h0z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'youtube' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6-11.4 42.9-11.4 132.3-11.4 132.3s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.2 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zm-317.5 213.5V175.2l142.7 81.2-142.7 81.2z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'x' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'twitter' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M459.4 151.7c.3 4.5 .3 9.1 .3 13.6 0 138.7-105.6 298.6-298.6 298.6-59.5 0-114.7-17.2-161.1-47.1 8.4 1 16.6 1.3 25.3 1.3 49.1 0 94.2-16.6 130.3-44.8-46.1-1-84.8-31.2-98.1-72.8 6.5 1 13 1.6 19.8 1.6 9.4 0 18.8-1.3 27.6-3.6-48.1-9.7-84.1-52-84.1-103v-1.3c14 7.8 30.2 12.7 47.4 13.3-28.3-18.8-46.8-51-46.8-87.4 0-19.5 5.2-37.4 14.3-53 51.7 63.7 129.3 105.3 216.4 109.8-1.6-7.8-2.6-15.9-2.6-24 0-57.8 46.8-104.9 104.9-104.9 30.2 0 57.5 12.7 76.7 33.1 23.7-4.5 46.5-13.3 66.6-25.3-7.8 24.4-24.4 44.8-46.1 57.8 21.1-2.3 41.6-8.1 60.4-16.2-14.3 20.8-32.2 39.3-52.6 54.3z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'tumblr' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M309.8 480.3c-13.6 14.5-50 31.7-97.4 31.7-120.8 0-147-88.8-147-140.6v-144H17.9c-5.5 0-10-4.5-10-10v-68c0-7.2 4.5-13.6 11.3-16 62-21.8 81.5-76 84.3-117.1 .8-11 6.5-16.3 16.1-16.3h70.9c5.5 0 10 4.5 10 10v115.2h83c5.5 0 10 4.4 10 9.9v81.7c0 5.5-4.5 10-10 10h-83.4V360c0 34.2 23.7 53.6 68 35.8 4.8-1.9 9-3.2 12.7-2.2 3.5 .9 5.8 3.4 7.4 7.9l22 64.3c1.8 5 3.3 10.6-.4 14.5z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'deviantart' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M320 93.2l-98.2 179.1 7.4 9.5H320v127.7H159.1l-13.5 9.2-43.7 84c-.3 0-8.6 8.6-9.2 9.2H0v-93.2l93.2-179.4-7.4-9.2H0V102.5h156l13.5-9.2 43.7-84c.3 0 8.6-8.6 9.2-9.2H320v93.1z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'etsy' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M384 348c-1.8 10.8-13.8 110-15.5 132-117.9-4.3-219.9-4.7-368.5 0v-25.5c45.5-8.9 60.6-8 61-35.3 1.8-72.3 3.5-244.1 0-322-1-28.5-12.1-26.8-61-36v-25.5c73.9 2.4 255.9 8.6 363-3.8-3.5 38.3-7.8 126.5-7.8 126.5H332C320.9 115.7 313.2 68 277.3 68h-137c-10.3 0-10.8 3.5-10.8 9.8V241.5c58 .5 88.5-2.5 88.5-2.5 29.8-1 27.6-8.5 40.8-65.3h25.8c-4.4 101.4-3.9 61.8-1.8 160.3H257c-9.2-40.1-9.1-61-39.5-61.5 0 0-21.5-2-88-2v139c0 26 14.3 38.3 44.3 38.3H263c63.6 0 66.6-25 98.8-99.8H384z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'flickr' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zM144.5 319c-35.1 0-63.5-28.4-63.5-63.5s28.4-63.5 63.5-63.5 63.5 28.4 63.5 63.5-28.4 63.5-63.5 63.5zm159 0c-35.1 0-63.5-28.4-63.5-63.5s28.4-63.5 63.5-63.5 63.5 28.4 63.5 63.5-28.4 63.5-63.5 63.5z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'foursquare' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 368 512"><path d="M323.1 3H49.9C12.4 3 0 31.3 0 49.1v433.8c0 20.3 12.1 27.7 18.2 30.1 6.2 2.5 22.8 4.6 32.9-7.1C180 356.5 182.2 354 182.2 354c3.1-3.4 3.4-3.1 6.8-3.1h83.4c35.1 0 40.6-25.2 44.3-39.7l48.6-243C373.8 25.8 363.1 3 323.1 3zm-16.3 73.8l-11.4 59.7c-1.2 6.5-9.5 13.2-16.9 13.2H172.1c-12 0-20.6 8.3-20.6 20.3v13c0 12 8.6 20.6 20.6 20.6h90.4c8.3 0 16.6 9.2 14.8 18.2-1.8 8.9-10.5 53.8-11.4 58.8-.9 4.9-6.8 13.5-16.9 13.5h-73.5c-13.5 0-17.2 1.8-26.5 12.6 0 0-8.9 11.4-89.5 108.3-.9 .9-1.8 .6-1.8-.3V75.9c0-7.7 6.8-16.6 16.6-16.6h219c8.2 0 15.6 7.7 13.5 17.5z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'instagram' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'discord' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M524.5 69.8a1.5 1.5 0 0 0 -.8-.7A485.1 485.1 0 0 0 404.1 32a1.8 1.8 0 0 0 -1.9 .9 337.5 337.5 0 0 0 -14.9 30.6 447.8 447.8 0 0 0 -134.4 0 309.5 309.5 0 0 0 -15.1-30.6 1.9 1.9 0 0 0 -1.9-.9A483.7 483.7 0 0 0 116.1 69.1a1.7 1.7 0 0 0 -.8 .7C39.1 183.7 18.2 294.7 28.4 404.4a2 2 0 0 0 .8 1.4A487.7 487.7 0 0 0 176 479.9a1.9 1.9 0 0 0 2.1-.7A348.2 348.2 0 0 0 208.1 430.4a1.9 1.9 0 0 0 -1-2.6 321.2 321.2 0 0 1 -45.9-21.9 1.9 1.9 0 0 1 -.2-3.1c3.1-2.3 6.2-4.7 9.1-7.1a1.8 1.8 0 0 1 1.9-.3c96.2 43.9 200.4 43.9 295.5 0a1.8 1.8 0 0 1 1.9 .2c2.9 2.4 6 4.9 9.1 7.2a1.9 1.9 0 0 1 -.2 3.1 301.4 301.4 0 0 1 -45.9 21.8 1.9 1.9 0 0 0 -1 2.6 391.1 391.1 0 0 0 30 48.8 1.9 1.9 0 0 0 2.1 .7A486 486 0 0 0 610.7 405.7a1.9 1.9 0 0 0 .8-1.4C623.7 277.6 590.9 167.5 524.5 69.8zM222.5 337.6c-29 0-52.8-26.6-52.8-59.2S193.1 219.1 222.5 219.1c29.7 0 53.3 26.8 52.8 59.2C275.3 311 251.9 337.6 222.5 337.6zm195.4 0c-29 0-52.8-26.6-52.8-59.2S388.4 219.1 417.9 219.1c29.7 0 53.3 26.8 52.8 59.2C470.7 311 447.5 337.6 417.9 337.6z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'twitch' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M391.2 103.5H352.5v109.7h38.6zM285 103H246.4V212.8H285zM120.8 0 24.3 91.4V420.6H140.1V512l96.5-91.4h77.3L487.7 256V0zM449.1 237.8l-77.2 73.1H294.6l-67.6 64v-64H140.1V36.6H449.1z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'github' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512"><path d="M165.9 397.4c0 2-2.3 3.6-5.2 3.6-3.3 .3-5.6-1.3-5.6-3.6 0-2 2.3-3.6 5.2-3.6 3-.3 5.6 1.3 5.6 3.6zm-31.1-4.5c-.7 2 1.3 4.3 4.3 4.9 2.6 1 5.6 0 6.2-2s-1.3-4.3-4.3-5.2c-2.6-.7-5.5 .3-6.2 2.3zm44.2-1.7c-2.9 .7-4.9 2.6-4.6 4.9 .3 2 2.9 3.3 5.9 2.6 2.9-.7 4.9-2.6 4.6-4.6-.3-1.9-3-3.2-5.9-2.9zM244.8 8C106.1 8 0 113.3 0 252c0 110.9 69.8 205.8 169.5 239.2 12.8 2.3 17.3-5.6 17.3-12.1 0-6.2-.3-40.4-.3-61.4 0 0-70 15-84.7-29.8 0 0-11.4-29.1-27.8-36.6 0 0-22.9-15.7 1.6-15.4 0 0 24.9 2 38.6 25.8 21.9 38.6 58.6 27.5 72.9 20.9 2.3-16 8.8-27.1 16-33.7-55.9-6.2-112.3-14.3-112.3-110.5 0-27.5 7.6-41.3 23.6-58.9-2.6-6.5-11.1-33.3 2.6-67.9 20.9-6.5 69 27 69 27 20-5.6 41.5-8.5 62.8-8.5s42.8 2.9 62.8 8.5c0 0 48.1-33.6 69-27 13.7 34.7 5.2 61.4 2.6 67.9 16 17.7 25.8 31.5 25.8 58.9 0 96.5-58.9 104.2-114.8 110.5 9.2 7.9 17 22.9 17 46.4 0 33.7-.3 75.4-.3 83.6 0 6.5 4.6 14.4 17.3 12.1C428.2 457.8 496 362.9 496 252 496 113.3 383.5 8 244.8 8zM97.2 352.9c-1.3 1-1 3.3 .7 5.2 1.6 1.6 3.9 2.3 5.2 1 1.3-1 1-3.3-.7-5.2-1.6-1.6-3.9-2.3-5.2-1zm-10.8-8.1c-.7 1.3 .3 2.9 2.3 3.9 1.6 1 3.6 .7 4.3-.7 .7-1.3-.3-2.9-2.3-3.9-2-.6-3.6-.3-4.3 .7zm32.4 35.6c-1.6 1.3-1 4.3 1.3 6.2 2.3 2.3 5.2 2.6 6.5 1 1.3-1.3 .7-4.3-1.3-6.2-2.2-2.3-5.2-2.6-6.5-1zm-11.4-14.7c-1.6 1-1.6 3.6 0 5.9 1.6 2.3 4.3 3.3 5.6 2.3 1.6-1.3 1.6-3.9 0-6.2-1.4-2.3-4-3.3-5.6-2z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'pinterest' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512"><path d="M496 256c0 137-111 248-248 248-25.6 0-50.2-3.9-73.4-11.1 10.1-16.5 25.2-43.5 30.8-65 3-11.6 15.4-59 15.4-59 8.1 15.4 31.7 28.5 56.8 28.5 74.8 0 128.7-68.8 128.7-154.3 0-81.9-66.9-143.2-152.9-143.2-107 0-163.9 71.8-163.9 150.1 0 36.4 19.4 81.7 50.3 96.1 4.7 2.2 7.2 1.2 8.3-3.3 .8-3.4 5-20.3 6.9-28.1 .6-2.5 .3-4.7-1.7-7.1-10.1-12.5-18.3-35.3-18.3-56.6 0-54.7 41.4-107.6 112-107.6 60.9 0 103.6 41.5 103.6 100.9 0 67.1-33.9 113.6-78 113.6-24.3 0-42.6-20.1-36.7-44.8 7-29.5 20.5-61.3 20.5-82.6 0-19-10.2-34.9-31.4-34.9-24.9 0-44.9 25.7-44.9 60.2 0 22 7.4 36.8 7.4 36.8s-24.5 103.8-29 123.2c-5 21.4-3 51.6-.9 71.2C65.4 450.9 0 361.1 0 256 0 119 111 8 248 8s248 111 248 248z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'linkedin' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M416 32H31.9C14.3 32 0 46.5 0 64.3v383.4C0 465.5 14.3 480 31.9 480H416c17.6 0 32-14.5 32-32.3V64.3c0-17.8-14.4-32.3-32-32.3zM135.4 416H69V202.2h66.5V416zm-33.2-243c-21.3 0-38.5-17.3-38.5-38.5S80.9 96 102.2 96c21.2 0 38.5 17.3 38.5 38.5 0 21.3-17.2 38.5-38.5 38.5zm282.1 243h-66.4V312c0-24.8-.5-56.7-34.5-56.7-34.6 0-39.9 27-39.9 54.9V416h-66.4V202.2h63.7v29.2h.9c8.9-16.8 30.6-34.5 62.9-34.5 67.2 0 79.7 44.3 79.7 101.9V416z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'slack' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M94.1 315.1c0 25.9-21.2 47.1-47.1 47.1S0 341 0 315.1c0-25.9 21.2-47.1 47.1-47.1h47.1v47.1zm23.7 0c0-25.9 21.2-47.1 47.1-47.1s47.1 21.2 47.1 47.1v117.8c0 25.9-21.2 47.1-47.1 47.1s-47.1-21.2-47.1-47.1V315.1zm47.1-189c-25.9 0-47.1-21.2-47.1-47.1S139 32 164.9 32s47.1 21.2 47.1 47.1v47.1H164.9zm0 23.7c25.9 0 47.1 21.2 47.1 47.1s-21.2 47.1-47.1 47.1H47.1C21.2 244 0 222.8 0 196.9s21.2-47.1 47.1-47.1H164.9zm189 47.1c0-25.9 21.2-47.1 47.1-47.1 25.9 0 47.1 21.2 47.1 47.1s-21.2 47.1-47.1 47.1h-47.1V196.9zm-23.7 0c0 25.9-21.2 47.1-47.1 47.1-25.9 0-47.1-21.2-47.1-47.1V79.1c0-25.9 21.2-47.1 47.1-47.1 25.9 0 47.1 21.2 47.1 47.1V196.9zM283.1 385.9c25.9 0 47.1 21.2 47.1 47.1 0 25.9-21.2 47.1-47.1 47.1-25.9 0-47.1-21.2-47.1-47.1v-47.1h47.1zm0-23.7c-25.9 0-47.1-21.2-47.1-47.1 0-25.9 21.2-47.1 47.1-47.1h117.8c25.9 0 47.1 21.2 47.1 47.1 0 25.9-21.2 47.1-47.1 47.1H283.1z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'xing' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M162.7 210c-1.8 3.3-25.2 44.4-70.1 123.5-4.9 8.3-10.8 12.5-17.7 12.5H9.8c-7.7 0-12.1-7.5-8.5-14.4l69-121.3c.2 0 .2-.1 0-.3l-43.9-75.6c-4.3-7.8 .3-14.1 8.5-14.1H100c7.3 0 13.3 4.1 18 12.2l44.7 77.5zM382.6 46.1l-144 253v.3L330.2 466c3.9 7.1 .2 14.1-8.5 14.1h-65.2c-7.6 0-13.6-4-18-12.2l-92.4-168.5c3.3-5.8 51.5-90.8 144.8-255.2 4.6-8.1 10.4-12.2 17.5-12.2h65.7c8 0 12.3 6.7 8.5 14.1z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'weibo' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M407 177.6c7.6-24-13.4-46.8-37.4-41.7-22 4.8-28.8-28.1-7.1-32.8 50.1-10.9 92.3 37.1 76.5 84.8-6.8 21.2-38.8 10.8-32-10.3zM214.8 446.7C108.5 446.7 0 395.3 0 310.4c0-44.3 28-95.4 76.3-143.7C176 67 279.5 65.8 249.9 161c-4 13.1 12.3 5.7 12.3 6 79.5-33.6 140.5-16.8 114 51.4-3.7 9.4 1.1 10.9 8.3 13.1 135.7 42.3 34.8 215.2-169.7 215.2zm143.7-146.3c-5.4-55.7-78.5-94-163.4-85.7-84.8 8.6-148.8 60.3-143.4 116s78.5 94 163.4 85.7c84.8-8.6 148.8-60.3 143.4-116zM347.9 35.1c-25.9 5.6-16.8 43.7 8.3 38.3 72.3-15.2 134.8 52.8 111.7 124-7.4 24.2 29.1 37 37.4 12 31.9-99.8-55.1-195.9-157.4-174.3zm-78.5 311c-17.1 38.8-66.8 60-109.1 46.3-40.8-13.1-58-53.4-40.3-89.7 17.7-35.4 63.1-55.4 103.4-45.1 42 10.8 63.1 50.2 46 88.5zm-86.3-30c-12.9-5.4-30 .3-38 12.9-8.3 12.9-4.3 28 8.6 34 13.1 6 30.8 .3 39.1-12.9 8-13.1 3.7-28.3-9.7-34zm32.6-13.4c-5.1-1.7-11.4 .6-14.3 5.4-2.9 5.1-1.4 10.6 3.7 12.9 5.1 2 11.7-.3 14.6-5.4 2.8-5.2 1.1-10.9-4-12.9z"/></svg>
				
IPSCONTENT;

elseif ( $profile['value'] === 'vk' ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M31.5 63.5C0 95 0 145.7 0 247V265C0 366.3 0 417 31.5 448.5C63 480 113.7 480 215 480H233C334.3 480 385 480 416.5 448.5C448 417 448 366.3 448 265V247C448 145.7 448 95 416.5 63.5C385 32 334.3 32 233 32H215C113.7 32 63 32 31.5 63.5zM75.6 168.3H126.7C128.4 253.8 166.1 290 196 297.4V168.3H244.2V242C273.7 238.8 304.6 205.2 315.1 168.3H363.3C359.3 187.4 351.5 205.6 340.2 221.6C328.9 237.6 314.5 251.1 297.7 261.2C316.4 270.5 332.9 283.6 346.1 299.8C359.4 315.9 369 334.6 374.5 354.7H321.4C316.6 337.3 306.6 321.6 292.9 309.8C279.1 297.9 262.2 290.4 244.2 288.1V354.7H238.4C136.3 354.7 78 284.7 75.6 168.3z"/></svg>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<i class="fa-brands fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $profile['value'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $profile['value'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</a>
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

	function solvedBadge( $author, $count ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$author->member_id}&do=solutions", null, "profile_solutions", array( $author->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solved_badge_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip class='i-color_inherit'>
	<i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT

</a>

IPSCONTENT;

		return $return;
}

	function tabs( $tabNames, $activeId, $defaultContent, $url, $tabParam='tab', $parseNames=TRUE, $contained=FALSE, $extraClasses='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-tabs class='ipsTabs
IPSCONTENT;

if ( $extraClasses ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $extraClasses, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
data-ipsTabBar-updateURL='false'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<div role='tablist'>
		
IPSCONTENT;

foreach ( $tabNames as $i => $name ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( $tabParam, $i ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsTabs__tab" title='
IPSCONTENT;

if ( $parseNames ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= strip_tags( \IPS\Member::loggedIn()->language()->get( $name ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= strip_tags( $name );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' role="tab" aria-controls='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' aria-selected="
IPSCONTENT;

if ( $i == $activeId ):
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

if ( $parseNames ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
{$name}
IPSCONTENT;

endif;
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
<section id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels 
IPSCONTENT;

if ( $contained ):
$return .= <<<IPSCONTENT
ipsTabs__panels--padded
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

foreach ( $tabNames as $i => $name ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $i == $activeId ):
$return .= <<<IPSCONTENT

			<div id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				{$defaultContent}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</section>

IPSCONTENT;

		return $return;
}

	function tag( $tag, $tagEditUrl=NULL, $classes=null ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$url = \IPS\Content\Tag::buildTagUrl( $tag );
$return .= <<<IPSCONTENT

<li class='ipsTags__item 
IPSCONTENT;

if ( $classes ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $tagEditUrl ):
$return .= <<<IPSCONTENT
ipsTags__item--deletable
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTags__tag' title="
IPSCONTENT;

$sprintf = array($tag); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'find_tagged_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" rel="tag" data-tag-label="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	
IPSCONTENT;

if ( $tagEditUrl ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagEditUrl->setQueryString( 'do', 'editTags' )->setQueryString( 'removeTag', $tag )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTags__remove' data-action='removeTag' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>&times;</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>
IPSCONTENT;

		return $return;
}

	function tags( $tags, $showCondensed=FALSE, $hideResponsive=FALSE, $tagEditUrl=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$id = mt_rand();
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $tags ) OR $tagEditUrl ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $showCondensed ):
$return .= <<<IPSCONTENT

		<ul class='ipsTags ipsTags--condensed 
IPSCONTENT;

if ( $hideResponsive ):
$return .= <<<IPSCONTENT
ipsResponsive_hidePhone
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

if ( \count( $tags ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $tags as $idx => $tag ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $idx < 4 ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tag( $tag, $tagEditUrl );
$return .= <<<IPSCONTENT

					
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

if ( \count( $tags ) > 4 ):
$return .= <<<IPSCONTENT

				<li class='ipsTags__more ipsJS_show'>
					<a href="#" class='ipsTags__tag' data-ipsMenu id='elTags_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$pluralize = array( \count( $tags ) - 4 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-angle-down"></i></a>
					<div class='ipsHide ipsMenu ipsMenu_normal i-padding_2 cTagPopup' id='elTags_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
						<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tagged_with', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						<ul class='ipsTags'>
							
IPSCONTENT;

foreach ( $tags as $tag ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tag( $tag, NULL );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<ul class='ipsTags 
IPSCONTENT;

if ( $hideResponsive ):
$return .= <<<IPSCONTENT
ipsResponsive_hidePhone
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $tagEditUrl ):
$return .= <<<IPSCONTENT
data-controller='core.front.core.tagEditor' data-tagEditID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Settings::i()->tags_min ):
$return .= <<<IPSCONTENT
data-minTags='
IPSCONTENT;

$return .= \IPS\Settings::i()->tags_min;
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Settings::i()->tags_max ):
$return .= <<<IPSCONTENT
data-maxTags='
IPSCONTENT;

$return .= \IPS\Settings::i()->tags_max;
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

if ( \count( $tags ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $tags as $tag ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tag( $tag, $tagEditUrl );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $tagEditUrl ):
$return .= <<<IPSCONTENT

				<li class='ipsTags__item ipsTags__item--edit'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagEditUrl->setQueryString( 'do', 'editTags' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-closeOnClick='false' id='elTagEditor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTags__tag'><i class='fa-solid fa-plus'></i>
IPSCONTENT;

if ( !\count( $tags ) ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
		
IPSCONTENT;

if ( $tagEditUrl ):
$return .= <<<IPSCONTENT

			<div id='elTagEditor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsMenu ipsMenu_wide ipsHide'>
				<div data-controller='core.front.core.tagEditorForm'>
					<div class='i-padding_3'>
						<span><i class='ipsLoadingIcon'></i>  &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</div>
				</div>
			</div>
		
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

		return $return;
}

	function tagsWithPrefix( $tags, $prefix=null, $showCondensed=FALSE, $hideResponsive=FALSE, $tagEditUrl=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$id = mt_rand();
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $tags ) OR $prefix OR $tagEditUrl ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $showCondensed ):
$return .= <<<IPSCONTENT

	    
IPSCONTENT;

$maxTags = $prefix ? 3 : 4;
$return .= <<<IPSCONTENT

		<ul class='ipsTags ipsTags--condensed 
IPSCONTENT;

if ( $hideResponsive ):
$return .= <<<IPSCONTENT
ipsResponsive_hidePhone
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		    
IPSCONTENT;

if ( $prefix ):
$return .= <<<IPSCONTENT

		        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tag( $prefix, $tagEditUrl, 'ipsTags__item--prefix' );
$return .= <<<IPSCONTENT

		    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $tags ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $tags as $idx => $tag ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $idx < $maxTags ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tag( $tag, $tagEditUrl );
$return .= <<<IPSCONTENT

					
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

if ( \count( $tags ) > $maxTags ):
$return .= <<<IPSCONTENT

				<li class='ipsTags__more ipsJS_show'>
					<a href="#" class='ipsTags__tag' data-ipsMenu id='elTags_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$pluralize = array( \count( $tags ) - $maxTags ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-angle-down"></i></a>
					<div class='ipsHide ipsMenu ipsMenu_normal i-padding_2 cTagPopup' id='elTags_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
						<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tagged_with', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						<ul class='ipsTags'>
							
IPSCONTENT;

foreach ( $tags as $tag ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tag( $tag, NULL );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<ul class='ipsTags 
IPSCONTENT;

if ( $hideResponsive ):
$return .= <<<IPSCONTENT
ipsResponsive_hidePhone
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $tagEditUrl ):
$return .= <<<IPSCONTENT
data-controller='core.front.core.tagEditor' data-tagEditID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Settings::i()->tags_min ):
$return .= <<<IPSCONTENT
data-minTags='
IPSCONTENT;

$return .= \IPS\Settings::i()->tags_min;
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Settings::i()->tags_max ):
$return .= <<<IPSCONTENT
data-maxTags='
IPSCONTENT;

$return .= \IPS\Settings::i()->tags_max;
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		    
IPSCONTENT;

if ( $prefix ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tag( $prefix, $tagEditUrl, 'ipsTags__item--prefix' );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $tags ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $tags as $tag ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tag( $tag, $tagEditUrl );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $tagEditUrl ):
$return .= <<<IPSCONTENT

				<li class='ipsTags__item ipsTags__item--edit'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tagEditUrl->setQueryString( 'do', 'editTags' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-closeOnClick='false' id='elTagEditor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTags__tag'><i class='fa-solid fa-plus'></i>
IPSCONTENT;

if ( !\count( $tags ) ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
		
IPSCONTENT;

if ( $tagEditUrl ):
$return .= <<<IPSCONTENT

			<div id='elTagEditor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsMenu ipsMenu_wide ipsHide'>
				<div data-controller='core.front.core.tagEditorForm'>
					<div class='i-padding_3'>
						<span><i class='ipsLoadingIcon'></i>  &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</div>
				</div>
			</div>
		
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

		return $return;
}

	function team( $team ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $team->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assignment_team', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function teamAssignment( $team ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsPhotoPanel i-align-items_center'>
    <i class='fa-solid fa-user-group i-font-size_3 i-text-align_center i-color_soft'></i>
    <div class="">
        <div><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $team->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></div>
        <span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'assignment_team', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
    </div>
</div>
IPSCONTENT;

		return $return;
}

	function thumbImage( $image, $name, $size='medium', $classes='', $lang='view_this', $url='', $extension='core_Attachment', $dataParam='', $lazyLoad=false ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $url ):
$return .= <<<IPSCONTENT

	<a 
IPSCONTENT;

if ( $dataParam ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $dataParam, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$val = "{$lang}"; $sprintf = array($name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsThumb ipsThumb_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt=''>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<span class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsThumb ipsThumb_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$image = ( $image instanceof \IPS\File ) ? (string) $image->url : $image;
$return .= <<<IPSCONTENT

	<img src='
IPSCONTENT;

$return .= \IPS\File::get( $extension, $image )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<i></i>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $url ):
$return .= <<<IPSCONTENT

	</a>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function updateWarning(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->isAdmin() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$notifications = \IPS\core\AdminNotification::notifications( NULL, array( \IPS\core\AdminNotification::SEVERITY_CRITICAL ) ); 
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count($notifications) ):
$return .= <<<IPSCONTENT

		<div data-controller="core.global.core.notificationList" class="cNotificationList i-grid i-gap_1">
			
IPSCONTENT;

foreach ( $notifications as $notification ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$style = $notification->style();
$return .= <<<IPSCONTENT

				<div class="ipsMessage ipsMessage--acp ipsMessage--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $style, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsPull">
					<i class='fa-solid fa-
IPSCONTENT;

if ( $style == $notification::STYLE_INFORMATION OR $style == $notification::STYLE_EXPIRE ):
$return .= <<<IPSCONTENT
circle-info
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
download
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsMessage__icon'></i>
					<div class="i-flex i-justify-content_space-between i-gap_2">
						<div class="i-flex_11">
							<h3 class="ipsMessage__title">{$notification->title()}</h3>
							<span class='i-font-size_-2 i-font-weight_normal i-opacity_8'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notification_frontend_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</div>
						
IPSCONTENT;

$dismissible = $notification->dismissible();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $dismissible !== $notification::DISMISSIBLE_NO ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=dismissAcpNotification&id={$notification->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsMessage__close i-flex_00" title="
IPSCONTENT;

$val = "acp_notification_dismiss_{$dismissible}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip data-action="dismiss">
								<i class="fa-solid fa-times"></i>
							</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					{$notification->body()}
				</div>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function uploadedIcon( $icon ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $icon ):
$return .= <<<IPSCONTENT

<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userBar( $userLinkOnly=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userBar", "userBar:before", [ $userLinkOnly ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="userBar" class="ipsUserNav ipsUserNav--member" data-controller="core.front.core.userbar
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id && \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
,core.front.core.instantNotifications
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userBar", "userBar:inside-start", [ $userLinkOnly ] );
$return .= <<<IPSCONTENT

		<li id="cUserLink" data-el="user">
		    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->menu( 'account' );
$return .= <<<IPSCONTENT

		</li>
        
IPSCONTENT;

if ( !$userLinkOnly ):
$return .= <<<IPSCONTENT

            <li data-el="notifications">
                <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications", null, "notifications", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsUserNav__link" id="elFullNotifications" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-closeonclick="false">
                    <i class="fa-regular fa-bell ipsUserNav__icon"></i>
                    <span class="ipsNotification" 
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->notification_cnt ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-notificationtype="notify" data-currentcount="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->notification_cnt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->notification_cnt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
                    <span class="ipsUserNav__text ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                </a>
                <div id="elFullNotifications_menu" class="ipsMenu ipsMenu_wide ipsHide">
                    <div class="ipsMenu_headerBar">
                        <h4 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
                        <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=options", null, "notifications_options", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary"><i class="fa-solid fa-list-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notification_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
                    </div>
                    <div class="ipsMenu_innerContent">
                        <div id="elNotificationsBrowser" data-controller="core.front.core.notifications"></div>
                        <i-data>
                            <ol class="ipsData ipsData--table ipsData--compact ipsData--user-notifications" data-role="notifyList" data-ipskeynav data-ipskeynav-observe="return" id="elNotifyContent"></ol>
                        </i-data>
                    </div>
                    <div class="ipsMenu_footerBar">
                        <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications", null, "notifications", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsMenu_footerBarLink"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_all_notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right-long"></i></a>
                    </div>
                </div>
            </li>
            
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm != 2 and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

                <li data-el="inbox">
                    <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger", null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsUserNav__link" id="elFullInbox" data-ipstooltip aria-label="
IPSCONTENT;

if ( ! \IPS\Member::loggedIn()->members_disable_pm ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_messages', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_messages_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-closeonclick="false">
                        
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm ):
$return .= <<<IPSCONTENT

                            <i class="fa-solid fa-microphone-slash ipsUserNav__icon i-opacity_5"></i>
                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                            <i class="fa-regular fa-envelope ipsUserNav__icon"></i>
                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        <span class="ipsNotification" 
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->msg_count_new ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-notificationtype="inbox" data-currentcount="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->msg_count_new, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->msg_count_new, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
                        <span class="ipsUserNav__text ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_messages', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                    </a>
                    <div id="elFullInbox_menu" class="ipsMenu ipsMenu_wide ipsHide">
                        <div class="ipsMenu_headerBar">
                            <h4 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_messages', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
                            <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-remotesubmit data-ipsdialog-destructonclose data-ipsdialog-flashmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_sent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" id="elMessengerPopup_compose" class="ipsButton ipsButton--primary"><i class="fa-regular fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
                        </div>
                        <div class="ipsMenu_innerContent">
                            <i-data>
                                <ol class="ipsData ipsData--table ipsData--compact ipsData--user-inbox" data-role="inboxList" data-ipskeynav data-ipskeynav-observe="return" id="elInboxContent"></ol>
                            </i-data>
                        </div>
                        <div class="ipsMenu_footerBar">
                            <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger", null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsMenu_footerBarLink"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_inbox', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right-long"></i></a>
                        </div>
                    </div>
                </li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'modcp' ) ) and \IPS\Member::loggedIn()->canAccessReportCenter() ):
$return .= <<<IPSCONTENT

                <li data-el="reports">
                    <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=reports", null, "modcp_reports", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsUserNav__link" id="elFullReports" data-ipstooltip aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'userbar_reports', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-closeonclick="false">
                        <i class="fa-regular fa-flag ipsUserNav__icon"></i>
                        
IPSCONTENT;

if ( \IPS\Member::loggedIn()->reportCount() ):
$return .= <<<IPSCONTENT

                            <span class="ipsNotification" data-notificationtype="reports">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->reportCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        <span class="ipsUserNav__text ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_center_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                    </a>
                    <div id="elFullReports_menu" class="ipsMenu ipsMenu_wide ipsHide">
                        <div class="ipsMenu_headerBar"><h4 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_center_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4></div>
                        <div class="ipsMenu_innerContent" data-role="reportsList"></div>
                        <div class="ipsMenu_footerBar i-text-align_center">
                            <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=reports", null, "modcp_reports", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-bars"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_center_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                        </div>
                    </div>
                </li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'discover' ) )  ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$defaultStream = \IPS\core\Stream::defaultStream();
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( $defaultStream ):
$return .= <<<IPSCONTENT

                    <li data-el="activity">
                        <a data-action="defaultStream" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $defaultStream->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsUserNav__link" data-ipstooltip aria-label="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $defaultStream->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
                            <i class="fa-regular fa-file-lines ipsUserNav__icon"></i>
                            <span class="ipsUserNav__text ipsInvisible" data-role="defaultStreamName">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $defaultStream->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
                        </a>
                    </li>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->userNav();
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userBar", "userBar:inside-end", [ $userLinkOnly ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userBar", "userBar:after", [ $userLinkOnly ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<ul id="elUserNav" class="ipsUserNav ipsUserNav--guest">
        
IPSCONTENT;

$login = new \IPS\Login( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$usernamePasswordMethods = $login->usernamePasswordMethods();
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$frontSsoUrl = $login->frontSsoUrl();
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( $usernamePasswordMethods or $buttonMethods ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( ( \count( $usernamePasswordMethods ) OR \count( $buttonMethods ) > 1 ) AND !$frontSsoUrl ):
$return .= <<<IPSCONTENT

            <li id="elSignInLink" data-el="sign-in">
                <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsUserNav__link" data-ipsmenu-closeonclick="false" data-ipsmenu id="elUserSignIn">
                	<i class="fa-solid fa-circle-user"></i>
                	<span class="ipsUserNav__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                </a>
                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->loginPopup( $login );
$return .= <<<IPSCONTENT

            </li>
            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

            <li id="elSignInLink" data-el="sign-in">
                <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsUserNav__link" id="elUserSignIn">
                	<i class="fa-solid fa-circle-user"></i>
					<span class="ipsUserNav__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</a>
            </li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Login::registrationType() != 'disabled' ):
$return .= <<<IPSCONTENT

			<li data-el="sign-up">
				
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'redirect' ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_reg_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsUserNav__link ipsUserNav__link--sign-up" target="_blank" rel="noopener">
						<i class="fa-solid fa-user-plus"></i>
						<span class="ipsUserNav__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsUserNav__link ipsUserNav__link--sign-up" 
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'normal' ):
$return .= <<<IPSCONTENT
data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRegisterButton">
						<i class="fa-solid fa-user-plus"></i>
						<span class="ipsUserNav__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->userNav();
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userLink( $member, $warningRef=NULL, $groupFormatting=NULL, $anonymous=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $anonymous ):
$return .= <<<IPSCONTENT

	<span class='ipsUsername ipsUsername--anonymous'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_anonymously_placename', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT


IPSCONTENT;

$groupFormatting = ( $groupFormatting === NULL ) ? ( ( \IPS\Settings::i()->group_formatting == 'global' ) ? TRUE : FALSE ) : $groupFormatting;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $member->member_id AND \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) )  ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;

if ( $warningRef ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url()->setQueryString( 'wr', $warningRef ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' rel="nofollow" data-ipsHover data-ipsHover-width='370' data-ipsHover-target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url()->setQueryString( array( 'do' => 'hovercard', 'wr' => $warningRef, 'referrer' => urlencode( \IPS\Request::i()->url() ) ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_user_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" class="ipsUsername">
IPSCONTENT;

if ( $groupFormatting && $member->group['prefix'] ):
$return .= <<<IPSCONTENT
{$member->group['prefix']}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $groupFormatting && $member->group['suffix'] ):
$return .= <<<IPSCONTENT
{$member->group['suffix']}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $groupFormatting && $member->group['prefix'] ):
$return .= <<<IPSCONTENT
{$member->group['prefix']}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $groupFormatting && $member->group['suffix'] ):
$return .= <<<IPSCONTENT
{$member->group['suffix']}
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

		return $return;
}

	function userLinkFromData( $id, $name, $seoName, $groupIdForFormatting=NULL, $groupFormatting=NULL, $anonymous=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $anonymous ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_anonymously_placename', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $id AND \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) )  ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$id}", null, "profile", array( $seoName ?: \IPS\Http\Url::seoTitle( $name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' rel="nofollow" data-ipsHover data-ipsHover-width="370" data-ipsHover-target='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$id}&do=hovercard", null, "profile", array( $seoName ?: \IPS\Http\Url::seoTitle( $name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$sprintf = array($name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_user_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" class="ipsUsername">
IPSCONTENT;

if ( $groupIdForFormatting AND ( $groupFormatting === TRUE OR ( $groupFormatting === NULL AND \IPS\Settings::i()->group_formatting == 'global' ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member\Group::load( $groupIdForFormatting )->formatName( $name );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $groupIdForFormatting AND ( $groupFormatting === TRUE OR ( $groupFormatting === NULL AND \IPS\Settings::i()->group_formatting == 'global' ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member\Group::load( $groupIdForFormatting )->formatName( $name );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

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

		return $return;
}

	function userMenuAchievements(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->canHaveAchievements() and \IPS\core\Achievements\Rank::show() and \IPS\core\Achievements\Rank::getStore() and $rank = \IPS\Member::loggedIn()->rank() ):
$return .= <<<IPSCONTENT

<li class='i-padding_2'>
    <div class='elUserNav_achievements i-flex i-gap_2'>
        <div class='elUserNav_achievements__icon i-basis_40 i-flex_00'>{$rank->html('i-aspect-ratio_10')}</div>
        <div class='elUserNav_achievements__content i-flex_11'>
            <div><strong class='i-color_hard i-font-size_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></div>
            <div class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_current_rank', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['pos'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['max'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</div>
            
IPSCONTENT;

if ( $nextRank = \IPS\Member::loggedIn()->nextRank() ):
$return .= <<<IPSCONTENT

                <div class='i-margin-top_2'>
                    <progress class='ipsProgress ipsProgress--rank i-margin-bottom_1' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->achievements_points / $nextRank->points * 100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' max="100"></progress>
                    <div class='i-font-size_-2 i-color_soft'>
IPSCONTENT;

$pluralize = array( $nextRank->points - \IPS\Member::loggedIn()->achievements_points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_next_rank', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
                </div>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>
    </div>
</li>

IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->canHaveAchievements() and \IPS\Settings::i()->achievements_rebuilding ):
$return .= <<<IPSCONTENT

<li class='i-padding_2'>
    <div class='elUserNav_achievements i-flex i-gap_4 i-row-gap_0 i-color_soft'>
        <div class='elUserNav_achievements__icon i-flex_00'><i class="fa-solid fa-circle-info fa-fw i-font-size_2"></i></div>
        <div class='elUserNav_achievements__content i-flex_11'>
            <p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ranks_are_being_recalculated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
        </div>
    </div>
</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userMenuLink(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class='ipsUserPhoto'>
    <img src='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
</span>
<span class='ipsUserNav__text'>
    
IPSCONTENT;

if ( isset( $_SESSION['logged_in_as_key'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($_SESSION['logged_in_from']['name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'front_logged_in_as', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

</span>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions['email_messages_bounce'] ):
$return .= <<<IPSCONTENT

    <span class='ipsNotification'><i class="fa-solid fa-triangle-exclamation"></i></span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userNameFromData( $name, $groupIdForFormatting=NULL, $groupFormatting=NULL, $anonymous=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $anonymous ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_anonymously_placename', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $groupIdForFormatting AND ( $groupFormatting === TRUE OR ( $groupFormatting === NULL AND \IPS\Settings::i()->group_formatting == 'global' ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member\Group::load( $groupIdForFormatting )->formatName( $name );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userPhoto( $member, $size='small', $warningRef=NULL, $classes='', $hovercard=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $member->member_id and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$memberURL = ( $warningRef ) ? $member->url()->setQueryString( 'wr', $warningRef ) : $member->url();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhoto", "userPhotoWithUrl:before", [ $member,$size,$warningRef,$classes,$hovercard ] );
$return .= <<<IPSCONTENT
<a data-ips-hook="userPhotoWithUrl" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $memberURL, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow" 
IPSCONTENT;

if ( $hovercard ):
$return .= <<<IPSCONTENT
data-ipshover data-ipshover-width="370" data-ipshover-target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $memberURL->setQueryString( 'do', 'hovercard' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsUserPhoto ipsUserPhoto--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $classes ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_user_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhoto", "userPhotoWithUrl:inside-start", [ $member,$size,$warningRef,$classes,$hovercard ] );
$return .= <<<IPSCONTENT

		<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhoto", "userPhotoWithUrl:inside-end", [ $member,$size,$warningRef,$classes,$hovercard ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhoto", "userPhotoWithUrl:after", [ $member,$size,$warningRef,$classes,$hovercard ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhoto", "userPhotoWithoutUrl:before", [ $member,$size,$warningRef,$classes,$hovercard ] );
$return .= <<<IPSCONTENT
<span data-ips-hook="userPhotoWithoutUrl" class="ipsUserPhoto ipsUserPhoto--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $classes ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhoto", "userPhotoWithoutUrl:inside-start", [ $member,$size,$warningRef,$classes,$hovercard ] );
$return .= <<<IPSCONTENT

		<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhoto", "userPhotoWithoutUrl:inside-end", [ $member,$size,$warningRef,$classes,$hovercard ] );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhoto", "userPhotoWithoutUrl:after", [ $member,$size,$warningRef,$classes,$hovercard ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userPhotoFromData( $id, $name, $seoName, $photoUrl, $size='small', $classes='', $hovercard=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $id and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhotoFromData", "userPhotoWithUrl:before", [ $id,$name,$seoName,$photoUrl,$size,$classes,$hovercard ] );
$return .= <<<IPSCONTENT
<a data-ips-hook="userPhotoWithUrl" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$id}", null, "profile", array( $seoName ?: \IPS\Http\Url::seoTitle( $name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" 
IPSCONTENT;

if ( $hovercard ):
$return .= <<<IPSCONTENT
data-ipshover data-ipshover-target="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$id}&do=hovercard", null, "profile", array( $seoName ?: \IPS\Http\Url::seoTitle( $name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsUserPhoto ipsUserPhoto--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $classes ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_user_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhotoFromData", "userPhotoWithUrl:inside-start", [ $id,$name,$seoName,$photoUrl,$size,$classes,$hovercard ] );
$return .= <<<IPSCONTENT

		<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photoUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhotoFromData", "userPhotoWithUrl:inside-end", [ $id,$name,$seoName,$photoUrl,$size,$classes,$hovercard ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhotoFromData", "userPhotoWithUrl:after", [ $id,$name,$seoName,$photoUrl,$size,$classes,$hovercard ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhotoFromData", "userPhotoWithoutUrl:before", [ $id,$name,$seoName,$photoUrl,$size,$classes,$hovercard ] );
$return .= <<<IPSCONTENT
<span data-ips-hook="userPhotoWithoutUrl" class="ipsUserPhoto ipsUserPhoto--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $classes ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhotoFromData", "userPhotoWithoutUrl:inside-start", [ $id,$name,$seoName,$photoUrl,$size,$classes,$hovercard ] );
$return .= <<<IPSCONTENT

		<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photoUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhotoFromData", "userPhotoWithoutUrl:inside-end", [ $id,$name,$seoName,$photoUrl,$size,$classes,$hovercard ] );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/global/userPhotoFromData", "userPhotoWithoutUrl:after", [ $id,$name,$seoName,$photoUrl,$size,$classes,$hovercard ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function viglink(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!-- @todo remove in 4.8 -->
IPSCONTENT;

		return $return;
}

	function widgetArea( $area ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !$area->isEmpty() OR \IPS\Dispatcher::i()->application?->canManageWidgets() ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$dataAttributes = $area->dataAttributes();
$return .= <<<IPSCONTENT

<section
	class="cWidgetContainer
IPSCONTENT;

foreach ( $area->classes() as $class ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

if ( \IPS\Dispatcher::i()->application->canManageWidgets() ):
$return .= <<<IPSCONTENT
data-controller='core.front.widgets.area'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	data-role='widgetReceiver'
	data-orientation='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $area->orientation(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-widgetArea='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $area->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	style="
IPSCONTENT;

foreach ( $area->styles() as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
"
	
IPSCONTENT;

foreach ( $dataAttributes as $k => $v ):
$return .= <<<IPSCONTENT

	    data-
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

    
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->_blockManager and (!$area->totalVisibleWidgets() || $area->isEmpty()) ):
$return .= <<<IPSCONTENT
 hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
    
IPSCONTENT;

foreach ( $area->children as $child ):
$return .= <<<IPSCONTENT

        {$child}
    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


    
IPSCONTENT;

foreach ( $area->widgets as $_widget ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( $widget = \IPS\Widget::createWidgetFromStoredData( $_widget ) ):
$return .= <<<IPSCONTENT

        <div class="ipsWidget__content--wrap">
            <div
                class='
IPSCONTENT;

if ( \get_class( $widget ) !== 'IPS\cms\widgets\Database' ):
$return .= <<<IPSCONTENT
ipsWidget ipsWidget--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $area->orientation(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $widget->getWrapperClasses() as $class ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

foreach ( $widget->dataAttributes() as $k => $v ):
$return .= <<<IPSCONTENT
 data-
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

                data-controller='core.front.widgets.block'
            >
            {$area->getWidgetContent()}
        </div>
        </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


    
IPSCONTENT;

if ( $area->wrapBehavior === 'carousel' ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$totalWidgets = $area->totalVisibleWidgets() ?: 1;
$return .= <<<IPSCONTENT

        <div class="cWidgetContainer__carousel-buttons" data-controller="core.front.widgets.carouselControls" 
IPSCONTENT;

if ( $totalWidgets <= 1 ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
            <div class="cWidgetContainer__carousel-indicator"></div>
            <button data-carousel-arrow="prev">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button data-carousel-arrow="next">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function widgetContainer( $id, $orientation='horizontal' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $id == 'header' ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->announcementContentTop(  );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( isset( \IPS\Output::i()->sidebar['widgetareas'][$id] ) ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$area = \IPS\Output::i()->sidebar['widgetareas'][$id];
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->widgetArea( $area );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$widgets = \IPS\Output::i()->sidebar['widgets'] ?? [];
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( ( ( isset( $widgets[ $id ] ) ) || \IPS\Dispatcher::i()->application?->canManageWidgets() ) ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( isset( $widgets[ $id ] ) AND \is_string( $widgets[ $id ] ) ):
$return .= <<<IPSCONTENT

        {$widgets[ $id ]}
        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

        <section class='cWidgetContainer cWidgetContainer--main' 
IPSCONTENT;

if ( ! isset( $widgets[ $id ] ) ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Dispatcher::i()->application->canManageWidgets() ):
$return .= <<<IPSCONTENT
data-controller='core.front.widgets.area'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role='widgetReceiver' data-orientation='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $orientation, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-widgetArea='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
            <ul>
                
IPSCONTENT;

if ( isset( $widgets[ $id ] ) ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

foreach ( $widgets[ $id ] as $widget ):
$return .= <<<IPSCONTENT

                    <li
                        class='
IPSCONTENT;

if ( \get_class( $widget ) != 'IPS\cms\widgets\Database' ):
$return .= <<<IPSCONTENT
ipsWidget ipsWidget--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $orientation, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $widget->getWrapperClasses() as $class ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

foreach ( $widget->dataAttributes() as $k => $v ):
$return .= <<<IPSCONTENT
 data-
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

                        data-controller='core.front.widgets.block'
                    >
                        {$widget}
                    </li>
                    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </ul>
        </section>
        
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

		return $return;
}}