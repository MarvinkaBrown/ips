<?php
namespace IPS\Theme;
class class_core_front_alerts extends \IPS\Theme\Template
{	function alertModal( $alert ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$form = $alert->getNewConversationForm();
$return .= <<<IPSCONTENT

<div id='elAlert' class="ipsModal 
IPSCONTENT;

if ( $form ):
$return .= <<<IPSCONTENT
ipsAlert--with-form
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsalertmessage 
IPSCONTENT;

if ( $alert->reply == 2 and !(( $alert->reply !== 2 or !$alert->author()->member_id ) or ( $alert->reply === 2 and ! \IPS\Member::loggedIn()->canUseMessenger() )) ):
$return .= <<<IPSCONTENT
data-alert-required
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <div class="ipsBox">
        <h4 class='ipsDialog_title'>
IPSCONTENT;

if ( $alert->anonymous ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-bullhorn"></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $alert->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>

        
IPSCONTENT;

if ( $alert->author()->member_id and ! $alert->anonymous ):
$return .= <<<IPSCONTENT

        <div class='ipsPhotoPanel i-padding_2 i-padding-bottom_0'>
            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $alert->author(), 'tiny' );
$return .= <<<IPSCONTENT

            <div class='ipsPhotoPanel__text'>
                <div class='ipsPhotoPanel__primary'>
IPSCONTENT;

$htmlsprintf = array($alert->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
            </div>
        </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


        <div class='i-padding_3 ipsRichText'>
            {$alert->content}
        </div>

        
IPSCONTENT;

if ( $alert->reply === 2 and $alert->author()->member_id and ! $alert->anonymous and ! \IPS\Member::loggedIn()->canUseMessenger() ):
$return .= <<<IPSCONTENT

        <div data-role="reply-prompt" class="i-padding_2 i-color_soft i-font-size_-2">
            <span class="ipsBadge ipsBadge--icon ipsBadge--negative"><i class="fa-solid fa-info"></i></span> 
IPSCONTENT;

$sprintf = array($alert->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_must_reply_modal_but_no_messenger', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

        </div>
        
IPSCONTENT;

elseif ( $alert->reply == 2 and $alert->author()->member_id and ! $alert->anonymous ):
$return .= <<<IPSCONTENT

        <div data-role="reply-prompt" class="i-padding_2 i-color_soft i-font-size_-2">
            <span class="ipsBadge ipsBadge--icon ipsBadge--negative"><i class="fa-solid fa-info"></i></span> 
IPSCONTENT;

$sprintf = array($alert->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_must_reply_modal', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

        </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


        
IPSCONTENT;

if ( $form ):
$return .= <<<IPSCONTENT

            <hr class="ipsHr"/>
            {$form}
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        <div class='ipsSubmitRow'>
            <div class='i-flex i-align-items_center i-justify-content_end i-gap_2'>
                
IPSCONTENT;

if ( ( $alert->reply !== 2 or !$alert->author()->member_id ) or ( $alert->reply === 2 and ! \IPS\Member::loggedIn()->canUseMessenger() )  ):
$return .= <<<IPSCONTENT

                    <a class="ipsButton ipsButton--secondary ipsButton--small" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=alerts&do=dismiss&id={$alert->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "alert", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-role="dismiss">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_dismiss', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( !$alert->anonymous and $alert->reply and $alert->author()->member_id and \IPS\Member::loggedIn()->canUseMessenger() ):
$return .= <<<IPSCONTENT

                    <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$alert->member_id}&title={$alert->title}&alert={$alert->id}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small' disabled data-role="reply">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reply', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </div>
        </div>
	</div>
</div>
IPSCONTENT;

		return $return;
}}