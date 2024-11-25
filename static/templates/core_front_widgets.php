<?php
namespace IPS\Theme;
class class_core_front_widgets extends \IPS\Theme\Template
{	function achievements( $result, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $title ):
$return .= <<<IPSCONTENT

<h3 class="ipsWidget__header">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='ipsWidget__content'>
	<i-data>
		<ul class='ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsData--achievements'>
			
IPSCONTENT;

foreach ( $result as $row ):
$return .= <<<IPSCONTENT

				<li class='ipsData__item'>
					
IPSCONTENT;

if ( $row['type'] === 'badge' ):
$return .= <<<IPSCONTENT

						<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row['member'], 'fluid', '', '' );
$return .= <<<IPSCONTENT
</div>
						<div class="ipsData__content">
							<div class='ipsData__main'>
								<div class='ipsData__title'><h4>
IPSCONTENT;

$htmlsprintf = array($row['member']->link(), $row['badge']->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_achievements_earned_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</h4></div>
								
IPSCONTENT;

if ( ! empty( $row['badge']->awardDescription ) ):
$return .= <<<IPSCONTENT
<p class="ipsData__desc">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['badge']->awardDescription, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<p class="ipsData__meta">
IPSCONTENT;

$val = ( $row['date'] instanceof \IPS\DateTime ) ? $row['date'] : \IPS\DateTime::ts( $row['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
							</div>
						</div>
						<div class='cAchievementsWidget__badge'>{$row['badge']->html('')}</div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row['member'], 'fluid', '', '' );
$return .= <<<IPSCONTENT
</div>
						<div class="ipsData__content">
							<div class='ipsData__main'>
								<div class='ipsData__title'><h4>
IPSCONTENT;

$htmlsprintf = array($row['member']->link(), $row['rank']->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_achievements_earned_rank', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</h4></div>
								<p class="ipsData__meta">
IPSCONTENT;

$val = ( $row['date'] instanceof \IPS\DateTime ) ? $row['date'] : \IPS\DateTime::ts( $row['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
							</div>
						</div>
						<div class='cAchievementsWidget__badge'>{$row['rank']->html('')}</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
</div>
IPSCONTENT;

		return $return;
}

	function activeUsers( $members, $memberCount, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.front.core.onlineUsersWidget' style="border-radius:inherit">
    <h3 class='ipsWidget__header'>
        
IPSCONTENT;

if ( \IPS\Dispatcher::i()->application->directory !== 'core' ):
$return .= <<<IPSCONTENT

            <span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_activeUsers', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

            <span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_activeUsers_noApp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( isset($orientation) and $orientation == 'horizontal' ):
$return .= <<<IPSCONTENT

            <span class='ipsWidget__header-secondary' data-memberCount="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $memberCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$pluralize = array( $memberCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_user_online_info', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

            <span class='ipsWidget__header-secondary i-color_soft' data-memberCount="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $memberCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $memberCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_user_online_info', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'><i class="fa-regular fa-user i-margin-end_icon"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $memberCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </h3>
    <div class='ipsWidget__content ipsWidget__padding'>
        <ul class='ipsList ipsList--csv'>
            
IPSCONTENT;

if ( $memberCount ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) )  ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

foreach ( $members as $row ):
$return .= <<<IPSCONTENT

                    <li data-memberId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$row['member_id']}", null, "profile", array( $row['seo_name'] ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsHover data-ipsHover-target='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$row['member_id']}&do=hovercard", null, "profile", array( $row['seo_name'] ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $row['in_editor'] ):
$return .= <<<IPSCONTENT
data-ipsTooltip data-ipsTooltip-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_user_in_editor', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
title="
IPSCONTENT;

$sprintf = array($row['member_name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_user_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

if ( $row['in_editor'] ):
$return .= <<<IPSCONTENT
<em>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member\Group::load( $row['member_group'] )->formatName( $row['member_name'] );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $row['in_editor'] ):
$return .= <<<IPSCONTENT
</em>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a></li>
                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                
IPSCONTENT;

foreach ( $members as $row ):
$return .= <<<IPSCONTENT

                    <li data-memberId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $row['in_editor'] ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle-notch fa-spin" data-ipsTooltip data-ipsTooltip-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_user_in_editor', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

$return .= \IPS\Member\Group::load( $row['member_group'] )->formatName( $row['member_name'] );
$return .= <<<IPSCONTENT
</li>
                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                <li class='i-color_soft i-font-weight_500' data-noneOnline>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'active_users_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </ul>
        
IPSCONTENT;

if ( $memberCount > 60 && $orientation == 'vertical' ):
$return .= <<<IPSCONTENT

            <p class='i-margin-top_2 i-font-weight_500'>
                <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=online&controller=online", null, "online", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='i-color_soft'>
IPSCONTENT;

$pluralize = array( $memberCount - 60 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_others', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
            </p>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>
</div>
IPSCONTENT;

		return $return;
}

	function advertisements( $advertisement, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $advertisement!==null ):
$return .= <<<IPSCONTENT


	<section class="ipsWidget__content ipsWidget__content--tnemesitrevda i-text-align_center">	
		
IPSCONTENT;

foreach ( $advertisement as $ad ):
$return .= <<<IPSCONTENT

			{$ad}
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</section>


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function blankWidget( $widget ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsWidgetBlank">
	
IPSCONTENT;

$val = "{$widget->errorMessage}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function blockGroup( $group, $title='', $favorites=[], $isCustom=false, $isFavorites=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div
    data-role="block_group"
    
IPSCONTENT;

if ( $isFavorites ):
$return .= <<<IPSCONTENT
data-favorites
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( empty($group) and !($isCustom and \IPS\Dispatcher::i()->checkAcpPermission( "block_manage", "cms", "pages", return: true )) ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( $isCustom ):
$return .= <<<IPSCONTENT
data-show-when-empty
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

>
	<h3 class='ipsToolbox_sectionTitle'>
        <span>{$title}</span>
    
IPSCONTENT;

if ( $isCustom and \IPS\Dispatcher::i()->checkAcpPermission( "block_manage", "cms", "pages", return: true ) ):
$return .= <<<IPSCONTENT

        <span data-role="custom_block_info"><i class="fa-solid fa-info-circle"></i><p data-role="custom_block_description">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack('block_Blocks_custom_link_desc');
$return .= <<<IPSCONTENT
</p></span>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h3>
	<ul>
		
IPSCONTENT;

foreach ( $group as $block ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$customBlock = is_array( $block ) ? $block[1] : null;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$block = is_array($block) ? $block[0] : $block;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$blockid = ( isset( $customBlock ) ? $block->key . ":" . $customBlock->_title : $block->key );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $blockid );
$return .= <<<IPSCONTENT

		<li
			data-blockID="app_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $block->app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blockid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
			
IPSCONTENT;

if ( isset($customBlock) ):
$return .= <<<IPSCONTENT
data-blocktitle="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customBlock->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-customblockid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customBlock->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $block->dataAttributes() as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $k != 'blockID' and !(in_array( $k, ['blocktitle', 'searchterms'] ) && isset($customBlock)) ):
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

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( isset($customBlock) ):
$return .= <<<IPSCONTENT
data-searchterms="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_widget_custom_block', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			class='i-cursor_move cSidebarManager_block'
		>
			<h4 data-role="blocktitle">
IPSCONTENT;

if ( isset($customBlock) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $customBlock->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "block_{$block->key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<i data-role="favorite-indicator" 
IPSCONTENT;

if ( isset($favorites[$blockid]) ):
$return .= <<<IPSCONTENT
data-is-favorite
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="fa-solid fa-star"></i></h4>
			<p>
                
IPSCONTENT;

if ( isset($customBlock) ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$lang = $customBlock->_description;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $lang );
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( empty(trim($lang)) ? \IPS\Member::loggedIn()->language()->addToStack('block_Blocks_desc_default') : $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT


                    
IPSCONTENT;

if ( \IPS\Dispatcher::i()->checkAcpPermission("block_manage", "cms", "pages", return: true) ):
$return .= <<<IPSCONTENT

                        &nbsp;<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=blocks&subnode=1&do=form&id={$customBlock->id}", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' target="_blank">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-external-link"></i></a>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$val = "block_{$block->key}_desc"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( $block->app === 'cms' and $block->key === 'Blocks' and \IPS\Dispatcher::i()->checkAcpPermission( "block_manage", "cms", "pages", return: true ) ):
$return .= <<<IPSCONTENT

            </p><p>
                        <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=blocks", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' target="_blank">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_Blocks_custom_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-external-link"></i></a>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </p>
		</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
	<p class='i-color_soft i-text-align_center i-padding_3 ipsHide'><em>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_app_widgets', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em></p>
</div>
IPSCONTENT;

		return $return;
}

	function blockList( $availableBlocks, $favorites=[], $customBlocks=[] ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$favoriteTitle = "<i class='fa-solid fa-star'></i>&nbsp;&nbsp;" . \IPS\Member::loggedIn()->language()->addToStack( "sidebar_favorites" );
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "core", 'front' )->blockGroup( $favorites, $favoriteTitle, $favorites, false, true );
$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $availableBlocks as $app => $blocks ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$title = \IPS\Member::loggedIn()->language()->addToStack( "__app_{$app}" );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "core", 'front' )->blockGroup( $blocks,$title,$favorites );
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT




IPSCONTENT;

$customTitle = \IPS\Member::loggedIn()->language()->addToStack( "block_Blocks" );
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "core", 'front' )->blockGroup( $customBlocks, $customTitle, $favorites, true );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function builderWrapper( $output, $config ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->do ) and \IPS\Widget\Request::i()->do == 'getBlock' ):
$return .= <<<IPSCONTENT

	<style>
		
IPSCONTENT;

if ( isset($config['custom']) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $config['custom'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( ! $config['border'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$block = \IPS\Widget\Request::i()->blockID;
$return .= <<<IPSCONTENT

			.ipsBox[data-blockid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $block, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"] {
				border-width: 0;
				box-shadow: none;
			}
			.ipsWidget[data-blockid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $block, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"] {
				background: transparent !important;
			}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</style>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ! empty( $config['background_custom_image_overlay'] ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$padding = isset( $config['style']['padding'] ) ? $config['style']['padding'] : '';
$return .= <<<IPSCONTENT

	
IPSCONTENT;

unset( $config['style']['padding'] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$style = implode( " ", $config['style']);
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$style = implode( " ", $config['style']);
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $config['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( ! empty($config['border']) ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( empty( $config['background_custom_image_overlay'] ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( ! empty($config['padding']) and $config['padding'] == 'half' ):
$return .= <<<IPSCONTENT

					i-padding_2
				
IPSCONTENT;

elseif ( ! empty($config['padding']) and $config['padding'] == 'full' ):
$return .= <<<IPSCONTENT

					ipsWidget__padding
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ! empty($config['fontsize']) and $config['fontsize'] != 'custom' and $config['fontsize'] != 'inherit' ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $config['fontsize'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
	 style="
IPSCONTENT;

if ( ! empty($config['style']) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $style, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

if ( ! empty( $config['background_custom_image_overlay'] ) ):
$return .= <<<IPSCONTENT

	<div class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $config['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_overlay' style="background-color: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $config['background_custom_image_overlay'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $padding, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
		 class="
IPSCONTENT;

if ( ! empty($config['padding']) and $config['padding'] == 'half' ):
$return .= <<<IPSCONTENT
i-padding_2
IPSCONTENT;

elseif ( ! empty($config['padding']) and $config['padding'] == 'full' ):
$return .= <<<IPSCONTENT
ipsWidget__padding
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	{$output}
	
IPSCONTENT;

if ( ! empty( $config['background_custom_image_overlay'] ) ):
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function clubs( $clubs, $title=NULL, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $title ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget--clubs_' . mt_rand();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsWidget__content">
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
 ipsData--widget-clubs' 
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

foreach ( $clubs as $club ):
$return .= <<<IPSCONTENT

				<li class='ipsData__item'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
					<div class='ipsData__image'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'fluid', '' );
$return .= <<<IPSCONTENT
</div>
					<div class="ipsData__content">
						<div class='ipsData__main'>
							<h4 class='ipsData__title'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
							<p class='ipsData__meta'>
								
IPSCONTENT;

$val = "club_{$club->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $club->type !== $club::TYPE_PUBLIC ):
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

$pluralize = array( $club->members ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</p>
						</div>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
</div>
IPSCONTENT;

		return $return;
}

	function formTemplate( $widget, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT


IPSCONTENT;

$visibilityFields = array( 'show_on_all_devices', 'devices_to_show', 'clubs_visibility');
$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--widget-template" action="
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
 data-ipsForm>
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

	<h4 class='ipsTitle ipsTitle--h3 ipsTitle--padding'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'editBlockSettings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>

	
IPSCONTENT;

$hasSettings = FALSE;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \mb_substr( $inputName, 0, 12 ) != 'widget_adv__' and ! \in_array( $inputName, $visibilityFields ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$hasSettings = TRUE; break 2;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
	<i-tabs class='ipsTabs' id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content'>
		<div role='tablist'>
			
IPSCONTENT;

$checkedTab = NULL;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $hasSettings ):
$return .= <<<IPSCONTENT

				<button type="button" id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_settings' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_settings_panel" aria-selected="
IPSCONTENT;

if ( !$checkedTab ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

$checkedTab = 'settings';
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</button>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<button type="button" id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_visibility' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_visibility_panel" aria-selected="
IPSCONTENT;

if ( !$checkedTab ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

$checkedTab = 'visibility';
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'visibility', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</button>
			
IPSCONTENT;

if ( $widget->isBuilderWidget() ):
$return .= <<<IPSCONTENT

				<button type="button" id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_advanced' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_advanced_panel" aria-selected="false">
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'widget_tab_advanced', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</button>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

	</i-tabs>
	<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels'>
		<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_settings_panel' class="
IPSCONTENT;

if ( $widget->menuStyle !== 'modal' ):
$return .= <<<IPSCONTENT
ipsMenu_innerContent
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_settings" 
IPSCONTENT;

if ( $checkedTab != 'settings' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<ul class='ipsForm ipsForm--vertical ipsForm--widget-template'>
				
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \mb_substr( $inputName, 0, 12 ) != 'widget_adv__' and ! \in_array( $inputName, $visibilityFields, TRUE ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \is_object( $input ) ):
$return .= <<<IPSCONTENT

								{$input->rowHtml($form)}
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								{$input}
							
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

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_visibility_panel' class="
IPSCONTENT;

if ( $widget->menuStyle !== 'modal' ):
$return .= <<<IPSCONTENT
ipsMenu_innerContent
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_visibility" 
IPSCONTENT;

if ( $checkedTab != 'visibility' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<ul class='ipsForm ipsForm--vertical ipsForm--widget-template'>
				
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \is_object( $input ) and \in_array( $input->name, $visibilityFields, TRUE ) ):
$return .= <<<IPSCONTENT

							{$input->rowHtml($form)}
						
IPSCONTENT;

elseif ( \in_array( $inputName, $visibilityFields, TRUE ) ):
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
		</div>

		
IPSCONTENT;

if ( $widget->isBuilderWidget() ):
$return .= <<<IPSCONTENT

		<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_advanced_panel' class="
IPSCONTENT;

if ( $widget->menuStyle !== 'modal' ):
$return .= <<<IPSCONTENT
ipsMenu_innerContent
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_advanced" hidden>
			<ul class='ipsForm ipsForm--vertical ipsForm--widget-template'>
				
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \mb_substr( $inputName, 0, 12 ) == 'widget_adv__' and ! \in_array( $inputName, $visibilityFields, TRUE ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \is_object( $input ) ):
$return .= <<<IPSCONTENT

								{$input->rowHtml($form)}
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								{$input}
							
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

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsMenu_footerBar i-text-align_center'>
		
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

			{$button}
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
</form>
IPSCONTENT;

		return $return;
}

	function guestSignUp( $login, $text, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT


IPSCONTENT;

$usernamePasswordMethods = $login->usernamePasswordMethods();
$return .= <<<IPSCONTENT


IPSCONTENT;

$ref = \IPS\Widget\Request::i()->externalref ?: base64_encode( \IPS\Widget\Request::i()->url() ); 
$return .= <<<IPSCONTENT

<div class='ipsWidget__content'>
	<div class='ipsWidget__padding'>
		<h2 class="ipsTitle ipsTitle--h3 ipsTitle--margin">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
		<div class="ipsRichText">{$text}</div>
	</div>
	<div class="ipsSubmitRow i-flex i-flex-wrap_wrap i-gap_1 i-justify-content_space-between">
		
IPSCONTENT;

if ( $usernamePasswordMethods ):
$return .= <<<IPSCONTENT

			<ul class="ipsButtons ipsButtons--fill">
				<li>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary"><i class="fa-solid fa-user-plus"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</li>
				<li>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--inherit"><i class="fa-solid fa-arrow-right-to-bracket"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $buttonMethods ):
$return .= <<<IPSCONTENT

			<form accept-charset='utf-8' method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_parent">
				<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				<input type="hidden" name="ref" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $ref, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				<div class='ipsButtons ipsButtons--fill'>
					
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

						{$method->button()}
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			</form>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

		return $return;
}

	function invite( $subject, $url, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_invite', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
	<div class='ipsWidget__padding'>
		<div class="i-text-align_center i-font-weight_600">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('block_invite_text', FALSE, array( 'sprintf' => array( \IPS\Settings::i()->board_name ) ) ) );
$return .= <<<IPSCONTENT

		</div>
		<ul class="ipsButtons i-margin-top_3">
			<li><a class="ipsButton ipsButton--inherit" href='mailto:?subject=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subject, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&body=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-envelope"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_invite_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
			<li><a class="ipsButton ipsButton--inherit" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=invite", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size="narrow"><i class="fa-solid fa-share-nodes"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_invite_share', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
		</ul>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function members( $members, $title, $display='csv', $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

</h3>

IPSCONTENT;

if ( $display === 'csv' ):
$return .= <<<IPSCONTENT

	<div class='ipsWidget__content ipsWidget__padding'>
		
IPSCONTENT;

if ( \count( $members ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--csv'>
				
IPSCONTENT;

foreach ( $members as $row ):
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLinkFromData( $row->member_id, $row->name, $row->members_seo_name, $row->member_group_id );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'widget_members_no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='ipsWidget__content'>
		
IPSCONTENT;

if ( \count( $members )  ):
$return .= <<<IPSCONTENT

			<i-data>
				<ul class='ipsData ipsData--table ipsData--members'>
					
IPSCONTENT;

foreach ( $members as $member ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'fluid' );
$return .= <<<IPSCONTENT
</div>
							<div class='ipsData__content'>
								<div class='ipsData__main'>
									<h4 class='i-font-weight_600'>{$member->link()} <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=reputation", null, "profile_reputation", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'  class='ipsRepBadge 
IPSCONTENT;

if ( $member->pp_reputation_points > 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--positive
IPSCONTENT;

elseif ( $member->pp_reputation_points < 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsRepBadge_neutral
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><i class='
IPSCONTENT;

if ( $member->pp_reputation_points > 0 ):
$return .= <<<IPSCONTENT
fa-solid fa-plus-circle
IPSCONTENT;

elseif ( $member->pp_reputation_points < 0 ):
$return .= <<<IPSCONTENT
fa-solid fa-minus-circle
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-regular fa-circle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->pp_reputation_points );
$return .= <<<IPSCONTENT
</a></h4>
									<div class='i-color_soft i-font-weight_500'>{$member->groupName}</div>
								</div>
								<div class='i-color_soft'>
									<div>
IPSCONTENT;

$htmlsprintf = array($member->joined->html()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'widget_member_joined_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
									
IPSCONTENT;

if ( $member->last_activity ):
$return .= <<<IPSCONTENT

										<div>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $member->last_activity )->html()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'widget_member_last_active_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
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

				</ul>
			</i-data>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'widget_members_no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
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

	function mostContributions( $contributions, $area, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsWidget__header'>
	<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$carouselID = 'widget-most-contributions_' . mt_rand();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</header>
<div class='ipsWidget__content'>
	<i-data>
		<ol class='ipsData ipsData--
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
 ipsData--widget-most-contributions' 
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

foreach ( $contributions['members'] as $member ):
$return .= <<<IPSCONTENT

				<li class='ipsData__item'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" tabindex="-1" aria-hidden="true"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
					<figure class='ipsData__image'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'fluid' );
$return .= <<<IPSCONTENT
</figure>
					<div class="ipsData__content">
						<div class='ipsData__main'>
							<h4 class='ipsData__title'>{$member->link()}</h4>
							<p class='ipsData__meta'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_member_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

if ( $area ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contributions['counts'][$member->member_id], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_posts, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>
						</div>
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

	function mostSolved( $topSolvedThisWeek, $limit, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_mostSolved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>

IPSCONTENT;

$tabID = 'ipsTabs_mostSolved' . mt_rand();
$return .= <<<IPSCONTENT

<i-tabs class='ipsTabs ipsTabs--small ipsTabs--stretch' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-updateURL='false' data-ipsTabBar-contentArea='#
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content'>
	<div role="tablist">
		<button type="button" data-taburl='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=mostSolved&time=week&limit={$limit}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_week' class='ipsTabs__tab' role="tab" aria-controls="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_week_panel" aria-selected='true'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		<button type="button" data-taburl='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=mostSolved&time=month&limit={$limit}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_month' class='ipsTabs__tab' role="tab" aria-controls="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_month_panel" aria-selected='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		<button type="button" data-taburl='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=mostSolved&time=year&limit={$limit}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_year' class='ipsTabs__tab' role="tab" aria-controls="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_year_panel" aria-selected='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		<button type="button" data-taburl='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=mostSolved&time=all&limit={$limit}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_all' class='ipsTabs__tab' role="tab" aria-controls="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_all_panel" aria-selected='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alltime', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

</i-tabs>
<section id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels ipsWidget__content'>
	<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_week_panel" class='ipsTabs__panel' role="tabpanel" aria-labelledby="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_week">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "core" )->mostSolvedRows( $topSolvedThisWeek, 'week' );
$return .= <<<IPSCONTENT

	</div>
</section>
IPSCONTENT;

		return $return;
}

	function mostSolvedRows( $results, $timeframe, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $results ) ):
$return .= <<<IPSCONTENT

	<i-data>
		<ul class="ipsData ipsData--grid ipsData--widget-mostSolvedRows i-basis_320">
			
IPSCONTENT;

$count = 0;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $results as $memberId => $solvedCount ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$member = \IPS\Member::load( $memberId );
$return .= <<<IPSCONTENT

				<li class='ipsData__item i-align-items_center'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
					<div class='ipsData__content'>
						<div class='ipsData__main'>
							<div class='ipsPhotoPanel'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'fluid' );
$return .= <<<IPSCONTENT

								<div class='ipsPhotoPanel__text'>
									<div class='ipsPhotoPanel__primary'>{$member->link()}</div>
									<div class='ipsPhotoPanel__secondary'>
										<span title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solved_badge_tooltip_time', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip class='ipsRepBadge ipsRepBadge--positive'><i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->formatNumber( $solvedCount ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									</div>
								</div>
							</div>
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

	<div class='ipsEmptyMessage'>
		<p>
IPSCONTENT;

$val = "top_solved_empty__{$timeframe}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function newsletter( $ref, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_newsletter_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content ipsWidget__padding'>
	<div class='i-flex i-gap_3'>
		<div class='i-flex_00 i-font-size_6 i-color_soft i-opacity_5'><i class="fa-regular fa-envelope"></i></div>
		<div class='i-flex_11'>
			<div class='i-font-weight_500 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('block_newsletter_signup') );
$return .= <<<IPSCONTENT
</div>
			<div class='ipsButtons ipsButtons--fill'>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

					<a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=newsletterSubscribe&ref={$ref}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 )->addRef($ref), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-user-plus"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_newsletter_signup_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a class="ipsButton ipsButton--inherit" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register&newsletter=1", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' 
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
><i class="fa-solid fa-user-plus"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_newsletter_signup_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function pagebuilderoembed( $video, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-margin-inline_auto'>{$video}</div>
IPSCONTENT;

		return $return;
}

	function pagebuildertext( $text, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>{$text}</div>
IPSCONTENT;

		return $return;
}

	function pagebuilderupload( $images, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ! \is_array( $images ) ):
$return .= <<<IPSCONTENT

<div><img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $images, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsPageBuilderUpload" loading="lazy" alt=""></div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function promoted( $promoted, $layout='grid', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsWidget__header'>
	<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_promoted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$carouselID = 'widget--core-promoted_' . mt_rand();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</header>
<div class="ipsWidget__content">
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
 ipsData--core-widget-promoted" 
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

foreach ( $promoted as $item ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$photoCount = ( $imageObjects = $item->imageObjects() ) ? \count( $imageObjects ) : 0;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$staff = \IPS\Member::load( $item->added_by );
$return .= <<<IPSCONTENT

				<li class='ipsData__item cPromoted cPromotedWidgetItem'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->object()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->ourPicksTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->object()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsData__image' aria-hidden="true" tabindex="-1">
						
IPSCONTENT;

if ( $photoCount ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$firstPhoto = $item->imageObjects()[0];
$return .= <<<IPSCONTENT

							<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $firstPhoto->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->objectTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading='lazy'>
						
IPSCONTENT;

elseif ( $image = $item->object()->primaryImage() ):
$return .= <<<IPSCONTENT

							<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->objectTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->defaultThumb(  );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
					<div class='ipsData__content'>
						<div class="ipsData__main">
							<h4 class='ipsData__title'>
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->object()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->ourPicksTitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							</h4>
							
IPSCONTENT;

if ( $text = $item->getText(true) ):
$return .= <<<IPSCONTENT

								<div class="ipsData__desc ipsRichText">{$text}</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<p class='ipsData__meta'>{$item->objectMetaDescription}</p>
							
IPSCONTENT;

if ( $photoCount > 1 ):
$return .= <<<IPSCONTENT

								<ul class='ipsGrid ipsGrid--widgets-promoted cPromotedImages i-gap_1 i-margin-top_2'>
									
IPSCONTENT;

foreach ( $item->imageObjects() as $file ):
$return .= <<<IPSCONTENT

										<li>
											<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsThumb" data-ipsLightbox data-ipsLightbox-group='g
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-label="Related image">
												<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
											</a>
										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<div class="ipsData__extra">
							<ul class="ipsData__stats">
								
IPSCONTENT;

$reactionClass = $item->objectReactionClass;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $reactionClass || $item->objectDataCount ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $reactionClass ):
$return .= <<<IPSCONTENT

										<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $reactionClass, FALSE );
$return .= <<<IPSCONTENT
</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $counts = $item->objectDataCount ):
$return .= <<<IPSCONTENT

										<li>
											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $counts['words'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
							<div class="ipsData__last">
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $item->object()->author(), 'tiny' );
$return .= <<<IPSCONTENT

								<div class="ipsData__last-text">
									<div class="ipsData__last-primary">
										{$item->object()->author()->link()}
									</div>
									<div class="ipsData__last-secondary">
										
IPSCONTENT;

$val = ( $item->object()->mapped('date') instanceof \IPS\DateTime ) ? $item->object()->mapped('date') : \IPS\DateTime::ts( $item->object()->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

									</div>
								</div>
							</div>
						</div>

						<div class="ipsData__last" hidden>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $staff, 'tiny' );
$return .= <<<IPSCONTENT

							<div class="ipsData__last-text">
								<div class="ipsData__last-primary">
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promoted_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $staff );
$return .= <<<IPSCONTENT

								</div>
								<div class="ipsData__last-secondary">
									
IPSCONTENT;

$val = ( $item->added instanceof \IPS\DateTime ) ? $item->added : \IPS\DateTime::ts( $item->added );$return .= $val->html();
$return .= <<<IPSCONTENT

								</div>
							</div>
						</div>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
	<div class='i-padding_2 i-border-top_3 i-text-align_end'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=feature&controller=featured", null, "featured_show", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_picks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-arrow-right-long"></i></a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function relatedContent( $similar, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $similar )  ):
$return .= <<<IPSCONTENT

	<h3 class='ipsWidget__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( isset($orientation) and $orientation == 'vertical' ):
$return .= <<<IPSCONTENT

		<div class='ipsWidget__content'>
			<i-data>
				<ul class='ipsData ipsData--table ipsData--related-content'>
					
IPSCONTENT;

foreach ( $similar as $item ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$numComments = ( $item::$firstCommentRequired ) ? $item->mapped('num_comments') - 1 : $item->mapped('num_comments');
$return .= <<<IPSCONTENT

						<li class='ipsData__item 
IPSCONTENT;

if ( $item->hidden() ):
$return .= <<<IPSCONTENT
 ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($item->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
							<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $item->author(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
							<div class='ipsData__main'>
								<div class='ipsData__title'>
									<div class='ipsBadges'>
										
IPSCONTENT;

if ( $item->hidden() === -1 ):
$return .= <<<IPSCONTENT

											<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
										
IPSCONTENT;

elseif ( $item->hidden() === 1 ):
$return .= <<<IPSCONTENT

											<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-triangle-exclamation'></i></span>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $item->mapped('featured') ):
$return .= <<<IPSCONTENT

											<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-star'></i></span>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $item, 'IPS\Content\Solvable' ) and $item->isSolved() ):
$return .= <<<IPSCONTENT

											<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_solved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-check'></i></span>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
									<h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($item->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
								</div>
								<p class='ipsData__meta'>
									
IPSCONTENT;

$htmlsprintf = array($item->author()->link( NULL, NULL, \IPS\IPS::classUsesTrait( $item, 'IPS\Content\Anonymous' ) ? $item->isAnonymous() : FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $item->mapped('date') )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'started_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								</p>
							</div>
							<div class="ipsData__extra">
								<ul class="ipsData__stats">
									<li data-statType='replies'>
										<span class='ipsData__stats-icon' data-stat-value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->formatNumber( $numComments ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $numComments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replies_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
										<span class='ipsData__stats-label'>
IPSCONTENT;

$pluralize = array( $numComments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replies_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
									</li>
								</ul>
							</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='ipsWidget__content'>
			<i-data>
				<ul class="ipsData ipsData--table ipsData--entries ipsData--related-content">
					
IPSCONTENT;

foreach ( $similar as $item ):
$return .= <<<IPSCONTENT

						<li class="ipsData__item 
IPSCONTENT;

if ( $item->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($item->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
							<div class='ipsData__content'>
								<div class='ipsData__main'>
									<div class='ipsData__title'>
										<div class='ipsBadges'>
											
IPSCONTENT;

foreach ( $item->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $item->prefix() ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $item->prefix( TRUE ), $item->prefix() );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</div>
										<h4>
														<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $item->tableHoverUrl and $item->canView() ):
$return .= <<<IPSCONTENT
data-ipsHover data-ipsHover-target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->setQueryString('preview', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsHover-timeout='1.5' 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
										</h4>
										
IPSCONTENT;

if ( $item->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

											{$item->commentPagination( array(), 'miniPagination' )}
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
									<div class='ipsData__meta'>
IPSCONTENT;

$htmlsprintf = array($item->author()->link( NULL, NULL, \IPS\IPS::classUsesTrait( $item, 'IPS\Content\Anonymous' ) ? $item->isAnonymous() : FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $item->mapped('date') instanceof \IPS\DateTime ) ? $item->mapped('date') : \IPS\DateTime::ts( $item->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
									
IPSCONTENT;

if ( \count( $item->tags() ) ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $item->tags(), true, true );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
								<ul class="ipsData__stats">
									
IPSCONTENT;

foreach ( $item->stats(FALSE) as $k => $v ):
$return .= <<<IPSCONTENT

										<li 
IPSCONTENT;

if ( \in_array( $k, $item->hotStats ) ):
$return .= <<<IPSCONTENT
class="ipsData__stats-hot" data-text='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
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
								<div class="ipsData__last">
									
IPSCONTENT;

if ( $item->mapped('num_comments') ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $item->lastCommenter(), 'fluid' );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $item->author(), 'fluid' );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									<div class='ipsData__last-text'>
										<div class='ipsData__last-primary'>
											
IPSCONTENT;

if ( $item->mapped('num_comments') ):
$return .= <<<IPSCONTENT

												{$item->lastCommenter()->link()}
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												{$item->author()->link( NULL, NULL, \IPS\IPS::classUsesTrait( $item, 'IPS\Content\Anonymous' ) ? $item->isAnonymous() : FALSE )}
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</div>
										<div class='ipsData__last-secondary'>
											<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url( 'getLastComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $item->mapped('last_comment') ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $item->mapped('last_comment') instanceof \IPS\DateTime ) ? $item->mapped('last_comment') : \IPS\DateTime::ts( $item->mapped('last_comment') );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $item->mapped('date') instanceof \IPS\DateTime ) ? $item->mapped('date') : \IPS\DateTime::ts( $item->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
										</div>
									</div>
								</div>
							</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
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

	function stats( $stats, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_stats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
	<ul class='ipsList ipsList--stats ipsList--stacked ipsList--border ipsList--fill'>
		<li>
			<strong class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $stats['member_count'] );
$return .= <<<IPSCONTENT
</strong>
			<span class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_total_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</li>
		<li>
			<strong class='ipsList__value' data-ipsTooltip title='<time data-norelative="true">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stats['most_online']['time'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</time>'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $stats['most_online']['count'] );
$return .= <<<IPSCONTENT
</strong>
			<span class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_most_online', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</li>
		
IPSCONTENT;

if ( $stats['last_registered'] instanceof \IPS\Member ):
$return .= <<<IPSCONTENT

			<li class='i-padding_2 i-grid i-place-content_center'>
				<div class='ipsPhotoPanel i-align-items_center i-text-align_start'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $stats['last_registered'], '' );
$return .= <<<IPSCONTENT

					<div class='ipsPhotoPanel__text'>
						<strong class='ipsPhotoPanel__primary'>{$stats['last_registered']->link()}</strong>
						<small class='ipsPhotoPanel__secondary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_newest_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span class='i-color_soft'> &middot; <time>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stats['last_registered']->joined->getTimestamp(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</time></span></small>
					</div>
				</div>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function stream( $stream, $results, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
	<ol class="ipsStream ipsStream_withTimeline ipsStream--widget">
		
IPSCONTENT;

foreach ( $results AS $result ):
$return .= <<<IPSCONTENT

			{$result->html('horizontal', $stream->include_comments ? 'last_comment' : 'date', TRUE, $result->asArray()['template'] )}
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ol>
</div>
IPSCONTENT;

		return $return;
}

	function streamItem( $indexData, $summaryLanguage, $authorData, $itemData, $unread, $objectUrl, $itemUrl, $containerUrl, $containerTitle, $repCount, $showRepUrl, $snippet, $iPostedIn, $view, $canIgnoreComments=FALSE, $reactions=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsStreamItem' data-orientation="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $view, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

if ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$itemClass = $indexData['index_class']::$itemClass;
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['title'] ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		<div class='ipsStreamItem__iconCell'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhotoFromData( $authorData['member_id'], $authorData['name'], $authorData['members_seo_name'], \IPS\Member::photoUrl( $authorData ), 'fluid' );
$return .= <<<IPSCONTENT
			
		</div>
		<div class='ipsStreamItem__mainCell'>
			<div class='ipsStreamItem__header'>
				<div class='ipsStreamItem__title'>
					
IPSCONTENT;

if ( isset( $indexData['index_prefix'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( rawurlencode($indexData['index_prefix']), $indexData['index_prefix'] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<h2><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-linkType="link" data-searchable>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['title'] ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
					
IPSCONTENT;

if ( $indexData['index_hidden'] ):
$return .= <<<IPSCONTENT

						<div class='ipsBadges'>
							
IPSCONTENT;

if ( $indexData['index_hidden'] === -1 ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
							
IPSCONTENT;

elseif ( $indexData['index_hidden'] === 1 ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-triangle-exclamation'></i></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class='ipsStreamItem__summary'>
					
IPSCONTENT;

if ( $indexData['index_title'] ):
$return .= <<<IPSCONTENT

						<span data-ipsTooltip title='
IPSCONTENT;

$val = "{$itemClass::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemClass::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></span>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
				
						<span data-ipsTooltip title='
IPSCONTENT;

$val = "{$indexData['index_class']::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_class']::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summaryLanguage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $containerUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>{$containerTitle}</a>
				</div>
			</div>
			<div class='ipsStreamItem__content'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core", 'front' )->searchResultSnippet( $indexData );
$return .= <<<IPSCONTENT

			</div>

			<ul class='ipsStreamItem__stats'>
				<li>
					<i class='fa-regular fa-clock'></i> <a href='
IPSCONTENT;

if ( $indexData['index_title'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( \in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'findReview', 'review' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'findComment', 'comment' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = ( $indexData['index_date_created'] instanceof \IPS\DateTime ) ? $indexData['index_date_created'] : \IPS\DateTime::ts( $indexData['index_date_created'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
				</li>
				
IPSCONTENT;

if ( isset( $itemClass::$databaseColumnMap['num_comments'] ) and isset( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] ] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$commentCount = $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] ];
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $itemClass::$firstCommentRequired ):
$return .= <<<IPSCONTENT

						<li><i class="fa-regular fa-comments"></i> 
IPSCONTENT;

$pluralize = array( $commentCount - 1 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replies_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<li><i class="fa-regular fa-comments"></i> 
IPSCONTENT;

$pluralize = array( $commentCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replies_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $indexData['index_class'], 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and \count( $reactions ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "search", "core" )->searchReaction( $reactions, $itemUrl->setQueryString('do', 'showReactionsReview')->setQueryString('review', $indexData['index_object_id']), $repCount );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "search", "core" )->searchReaction( $reactions, $itemUrl->setQueryString('do', 'showReactionsComment')->setQueryString('comment', $indexData['index_object_id']), $repCount );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $indexData['index_tags'] ) and $view == 'horizontal' ):
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( explode( ',', $indexData['index_tags'] ), true, true );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$itemClass = $indexData['index_class'];
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		<div class='ipsStreamItem__iconCell'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhotoFromData( $authorData['member_id'], $authorData['name'], $authorData['members_seo_name'], \IPS\Member::photoUrl( $authorData ), 'fluid' );
$return .= <<<IPSCONTENT
			
		</div>
		<div class='ipsStreamItem__mainCell'>
			<div class='ipsStreamItem__header'>
				<h4 class='ipsStreamItem__title'>
					
IPSCONTENT;

if ( isset( $indexData['index_prefix'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( rawurlencode($indexData['index_prefix']), $indexData['index_prefix'] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-linkType="link" data-searchable>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
                    
IPSCONTENT;

if ( $indexData['index_hidden'] ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</h4>
			</div>
			
IPSCONTENT;

if ( $containerTitle ):
$return .= <<<IPSCONTENT

				<div class='ipsStreamItem__summary'>
					
IPSCONTENT;

if ( $indexData['index_title'] ):
$return .= <<<IPSCONTENT

						<span data-ipsTooltip title='
IPSCONTENT;

$val = "{$itemClass::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemClass::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></span>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
				
						<span data-ipsTooltip title='
IPSCONTENT;

$val = "{$indexData['index_class']::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_class']::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summaryLanguage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $containerUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>{$containerTitle}</a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( $containerTitle ):
$return .= <<<IPSCONTENT

				<div class='ipsStreamItem__content'>
					{$snippet}
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<ul class='ipsStreamItem__stats'>
				<li>
					<i class='fa-regular fa-clock'></i> <a href='
IPSCONTENT;

if ( $indexData['index_title'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( \in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'findReview', 'review' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'findComment', 'comment' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = ( $indexData['index_date_created'] instanceof \IPS\DateTime ) ? $indexData['index_date_created'] : \IPS\DateTime::ts( $indexData['index_date_created'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
				</li>
				
IPSCONTENT;

if ( isset( $itemClass::$databaseColumnMap['num_comments'] ) and isset( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] ] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$commentCount = $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] ];
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $itemClass::$firstCommentRequired ):
$return .= <<<IPSCONTENT

						<li><i class="fa-regular fa-comments"></i> 
IPSCONTENT;

$pluralize = array( $commentCount - 1 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replies_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<li><i class="fa-regular fa-comments"></i> 
IPSCONTENT;

$pluralize = array( $commentCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replies_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $containerTitle and $view == 'horizontal' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $indexData['index_tags'] ) ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( explode( ',', $indexData['index_tags'] ), true, true );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>
IPSCONTENT;

		return $return;
}

	function tableofcontents( $items, $canEdit, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsWidget__padding ipsTableOfContents ipsTableOfContents__root" data-ipstableofcontents data-ipstableofcontents-currentitems="
IPSCONTENT;

$return .= base64_encode(json_encode($items));;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $canEdit ):
$return .= <<<IPSCONTENT
data-ipstableofcontents-canedit
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <h2 class="ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_tableofcontents__blocktitle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
    <hr class="ipsHr"/>
    <div class="ipsWidget__padding ipsTableOfContents__content ipsLoading ipsLoading--small">
    </div>
</div>
IPSCONTENT;

		return $return;
}

	function tagRows( $items, $layout='table' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
    <ul class='ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsData--widget-tags'>
        
IPSCONTENT;

foreach ( $items as $item ):
$return .= <<<IPSCONTENT

            <li class='ipsData__item'>
                <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url( 'getPrefComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped( 'title' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
				
IPSCONTENT;

if ( in_array($layout, array("wallpaper", "featured", "grid")) ):
$return .= <<<IPSCONTENT

					<figure class='ipsData__image'>
						
IPSCONTENT;

if ( $image = $item->primaryImage() ):
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

					<figure class='ipsData__icon'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $item->author(), 'fluid' );
$return .= <<<IPSCONTENT

					</figure>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class="ipsData__content">
					<div class='ipsData__main'>
						<div class='ipsData__title'>
							<h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($item->mapped( 'title' )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped( 'title' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
						</div>
						<div class='ipsData__desc ipsRichText' data-controller='core.front.core.lightboxedImages'>{$item->truncated()}</div>
						<p class='ipsData__meta'>
						    
IPSCONTENT;

$htmlsprintf = array($item->author()->link( NULL, NULL, $item->isAnonymous() ), \IPS\DateTime::ts( $item->mapped('date') )->html(FALSE)); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_name_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

						</p>
					</div>
				</div>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function tags( $title, $data, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>

IPSCONTENT;

$key = md5( uniqid( microtime() ) );
$return .= <<<IPSCONTENT

<div class='ipsWidget__content'>
    
IPSCONTENT;

if ( count( $data ) > 1 ):
$return .= <<<IPSCONTENT

	<i-tabs class='ipsTabs ipsTabs--stretch' id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-updateURL='false' data-ipsTabBar-contentArea='#ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content'>
		<div role="tablist">
			
IPSCONTENT;

$checked = NULL;
$return .= <<<IPSCONTENT

		    
IPSCONTENT;

foreach ( $data as $tag => $items ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$tabId = \str_replace( ' ', '-', $tag );
$return .= <<<IPSCONTENT

				<button type="button" id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" aria-controls="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( !$checked ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

$checked = $tabId;
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</button>
			
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
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels'>
	    
IPSCONTENT;

foreach ( $data as $tag => $items ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$tabId = \str_replace( ' ', '-', $tag );
$return .= <<<IPSCONTENT

			<div id="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class='ipsTabs__panel' role="tabpanel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $checked != $tabId ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "core" )->tagRows( $items, $layout );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</section>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	    
IPSCONTENT;

foreach ( $data as $tag => $items ):
$return .= <<<IPSCONTENT

	        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "core" )->tagRows( $items, $layout );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function topContributorRows( $results, $timeframe, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $results ) ):
$return .= <<<IPSCONTENT

	<i-data>
		<ol class="ipsData ipsData--mini-grid cTopContributors i-basis_320">
			
IPSCONTENT;

foreach ( $results as $memberId => $rep ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$member = \IPS\Member::load( $memberId );
$return .= <<<IPSCONTENT

				<li class='ipsData__item i-align-items_center'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
					<div class="ipsData__icon">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'fluid' );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsData__content'>
						<div class='ipsData__main'>
							<div class='ipsData__title'>{$member->link()}</div>
							<div class='ipsData__meta'>
								
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

									<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=reputation", null, "profile_reputation", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_badge_tooltip_period', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip class='ipsRepBadge 
IPSCONTENT;

if ( $rep > 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--positive
IPSCONTENT;

elseif ( $rep < 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsRepBadge_neutral
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><i class='
IPSCONTENT;

if ( $rep > 0 ):
$return .= <<<IPSCONTENT
fa-solid fa-plus-circle
IPSCONTENT;

elseif ( $rep < 0 ):
$return .= <<<IPSCONTENT
fa-solid fa-minus-circle
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-regular fa-circle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->formatNumber( $rep ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<span title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_badge_tooltip_period', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip class='ipsRepBadge 
IPSCONTENT;

if ( $rep > 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--positive
IPSCONTENT;

elseif ( $rep < 0 ):
$return .= <<<IPSCONTENT
ipsRepBadge--negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsRepBadge_neutral
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><i class='
IPSCONTENT;

if ( $rep > 0 ):
$return .= <<<IPSCONTENT
fa-solid fa-plus-circle
IPSCONTENT;

elseif ( $rep < 0 ):
$return .= <<<IPSCONTENT
fa-solid fa-minus-circle
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-regular fa-circle
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->formatNumber( $rep ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</div>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	</i-data>
	
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_leaderboard_on and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'discover' ) ) ):
$return .= <<<IPSCONTENT

		<div class="i-padding_2 i-text-align_end i-background_2 i-border-end-start-radius_box i-border-end-end-radius_box i-border-top_3">
			
IPSCONTENT;

$_timeframe = $timeframe == 'all' ? 'time=oldest' : ( $timeframe == 'year' ? ( 'custom_date_start=' . ( time() - 31536000 ) ) : ( 'time=' . $timeframe ) );
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=discover&controller=popular&tab=leaderboard&{$_timeframe}", null, "leaderboard_leaderboard", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='i-font-weight_600 i-color_soft i-font-size_-2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'leaderboard_show_more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-arrow-right-long i-margin-start_icon"></i></a>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='ipsEmptyMessage'>
		<p>
IPSCONTENT;

$val = "top_contributors_empty__{$timeframe}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function topContributors( $topContributorsThisWeek, $limit, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_topContributors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
	
IPSCONTENT;

$tabID = mt_rand();
$return .= <<<IPSCONTENT

	<i-tabs class='ipsTabs ipsTabs--stretch' id='ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-updateURL='false' data-ipsTabBar-contentArea='#ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content'>
		<div role="tablist">
			<button type="button" data-taburl='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=topContributors&time=week&limit={$limit}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" id='ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_week' aria-controls="ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_week_panel" aria-selected='true'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			<button type="button" data-taburl='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=topContributors&time=month&limit={$limit}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" id='ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_month' aria-controls="ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_month_panel" aria-selected='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			<button type="button" data-taburl='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=topContributors&time=year&limit={$limit}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" id='ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_year' aria-controls="ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_year_panel" aria-selected='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			<button type="button" data-taburl='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=topContributors&time=all&limit={$limit}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" id='ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_all' aria-controls="ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_all_panel" aria-selected='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alltime', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

	</i-tabs>
	<section id='ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels'>
		<div id="ipsTabs_topContributors
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_week_panel" class='ipsTabs__panel' role="tabpanel">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "core" )->topContributorRows( $topContributorsThisWeek, 'week', $layout, $isCarousel );
$return .= <<<IPSCONTENT

		</div>
	</section>
</div>
IPSCONTENT;

		return $return;
}

	function whosOnline( $members, $memberCount, $guests, $anonymous, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.front.core.onlineUsersWidget' style="border-radius:inherit">
    <h3 class='ipsWidget__header'>
        <span>
            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_whosOnline', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

        </span>
        <span class='ipsWidget__header-secondary'>
            
IPSCONTENT;

if ( isset($orientation) and $orientation == 'horizontal' ):
$return .= <<<IPSCONTENT

                <span><span data-memberCount="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $memberCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$pluralize = array( $memberCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_whos_online_info_members', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>, 
IPSCONTENT;

$pluralize = array( $anonymous ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_whos_online_info_anonymous', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

$pluralize = array( $guests ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_whos_online_info_guests', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>&nbsp; &nbsp;
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=online&controller=online", null, "online", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_full_list', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
        </span>
    </h3>
    <div class='ipsWidget__content ipsWidget__padding'>
        <ul class='ipsList ipsList--csv'>
            
IPSCONTENT;

if ( $memberCount ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

foreach ( $members as $row ):
$return .= <<<IPSCONTENT

                    <li data-memberId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLinkFromData( $row['member_id'], $row['member_name'], $row['seo_name'], $row['member_group'], TRUE );
$return .= <<<IPSCONTENT
</li>
                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                <li class='i-color_soft' data-noneOnline>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'whos_online_users_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </ul>
        
IPSCONTENT;

if ( isset($orientation) and $orientation == 'vertical' and $memberCount > 60 ):
$return .= <<<IPSCONTENT

            <p>
                <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=online&controller=online", null, "online", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$pluralize = array( $memberCount - 60 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_others', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
            </p>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>
</div>
IPSCONTENT;

		return $return;
}}