<?php
namespace IPS\Theme;
class class_core_admin_achievements extends \IPS\Theme\Template
{	function awardField( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="cRulesForm__assign-rewards">
	<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[points]" value="
IPSCONTENT;

if ( isset( $value['points'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['points'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsField_short" min="0">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_points_awarded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[badge]">
		<option value="">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_badge_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
		
IPSCONTENT;

foreach ( \IPS\core\Achievements\Badge::roots() as $badge ):
$return .= <<<IPSCONTENT

			<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $value['badge'] ) and $value['badge'] == $badge->id ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</select>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function badgeExport( $exportableBadges ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $exportableBadges ):
$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_3'>
<div class="ipsMessage ipsMessage--info">
	
IPSCONTENT;

$sprintf = array($exportableBadges); $pluralize = array( $exportableBadges ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_achievements_export_badges_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

</div>
<div class="ipsSubmitRow">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=achievements&controller=badges&do=export", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_achievements_export', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_3'>
<div class="ipsMessage ipsMessage--error">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_achievements_export_badges_blurb_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function badgePreview( $content, $prefix='badge' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li
	id='preview'
	data-controller='ips.core.badgepreview'
	class='ipsFieldRow ipsFieldRow--badge-creator-preview ipsBadgePreview'
	data-badgetype="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $prefix, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
>
	<div class='ipsFieldRow__label'></div>
	<div class='ipsFieldRow__content'>
		<div data-role="preview">
			{$content}
		</div>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function memberCount( $url, $count ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsTree_row_cells">
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsTree_row_cell">
		
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_members', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

	</a>
</div>
IPSCONTENT;

		return $return;
}

	function milestoneWithSubjectSwitch( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]">
	<option value="receiver" 
IPSCONTENT;

if ( isset( $value[0] ) and $value[0] == 'receiver' ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievement_who_reciever', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="giver" 
IPSCONTENT;

if ( isset( $value[0] ) and $value[0] == 'giver' ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievement_who_giver', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
<input type="number" class="ipsInput ipsField_short" min="0" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]" value="
IPSCONTENT;

if ( isset( $value[1] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievement_who_suffix', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function rebuildProgress( $data, $needsBottomSpacer=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-position_sticky-top i-padding_2 i-background_1 i-text-align_center 
IPSCONTENT;

if ( $needsBottomSpacer ):
$return .= <<<IPSCONTENT
i-margin-bottom_1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<strong class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_rules_rebuilding_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
	<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_rules_rebuilding_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<p class="i-color_soft"><span data-role="percentage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['percentage'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>% (<span data-role="number">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['processed'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> /
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</p>
	<progress class="ipsProgress" value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( $data['percentage'], 2), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></progress>
</div>
IPSCONTENT;

		return $return;
}

	function ruleAwards( $points, $badge, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	<div class="i-flex i-align-items_center i-flex-wrap_wrap i-column-gap_2">
		
IPSCONTENT;

if ( $badge ):
$return .= <<<IPSCONTENT

			<span style="max-width:20px">{$badge->html('', FALSE)}</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $points and $badge ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$sprintf = array($badge->_title); $pluralize = array( $points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_awards_points_and_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( $points ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$pluralize = array( $points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_awards_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( $badge ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$sprintf = array($badge->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_awards_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $title ):
$return .= <<<IPSCONTENT

			<div class='i-color_soft'>
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function ruleDescription( $conciseAction, $conditions ) {
		$return = '';
		$return .= <<<IPSCONTENT

<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conciseAction, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>

IPSCONTENT;

foreach ( $conditions as $condition ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $condition, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function ruleDescriptionBadge( $type, $value, $hoverValues = NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="ipsAchievementsBadge ipsAchievementsBadge--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $hoverValues ):
$return .= <<<IPSCONTENT
data-ipsTooltip data-ipsTooltip-safe title="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( '<br>', $hoverValues ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

</span>
IPSCONTENT;

		return $return;
}

	function rulesForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



<div class='cRulesForm ipsPos_center i-margin-top_4'>
	
IPSCONTENT;

if ( isset( $hiddenValues['rule_enabled'] ) and ! $hiddenValues['rule_enabled'] ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--general i-margin-bottom_3">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_rule_is_paused', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<form accept-charset="utf-8" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-controller="core.admin.members.achievementRuleForm" class='ipsBox '>
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

					
IPSCONTENT;

if ( $_k != 'rule_enabled' ):
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

endif;
$return .= <<<IPSCONTENT

				
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


		<div class='i-padding_3'>
			<h1 class='ipsTitle ipsTitle--h3'>
				
IPSCONTENT;

if ( \IPS\Widget\Request::i()->id ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_achievement_rule', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_achievement_rule', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</h1>
			<p class='i-font-size_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievement_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
		<div class='i-background_2 i-border-top_3 i-border-bottom_3 i-padding_3 i-position_relative cRulesForm__container'>
			
			
IPSCONTENT;

$ruleSelect = $elements['']['achievement_rule_action'];
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$options = $ruleSelect->options['options']; 
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$toggles = $ruleSelect->options['toggles']; 
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$ruleSelect->options['toggles'] = array();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$parts = array();
$return .= <<<IPSCONTENT


			
IPSCONTENT;

// First, just store the elements in groups so we can build the HTML later
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $options as $option => $extension ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $elements[''] as $k => $element ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \in_array( $k, $toggles[ $option ] )  ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

// Find related filters
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \substr( $k, 0, 19 ) === 'achievement_filter_' ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$parts[ $option ]['filter'][$k]['element'] = $element;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$subFilterPrefix = "achievement_subfilter_" . \substr( $k, 19 ) . "_";
$return .= <<<IPSCONTENT

							
							
IPSCONTENT;

// Find related subfilters
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $elements[''] as $j => $subElement ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \substr( $j, 0, \strlen( $subFilterPrefix ) ) === $subFilterPrefix ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$parts[ $option ]['filter'][$k]['subFilters'][$j] = $subElement;
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

// Find award fields 
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \substr( $k, -14 ) === '_award_subject' ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$parts[ $option ]['awardSubject'] = $element;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( \substr( $k, -12 ) === '_award_other' ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$parts[ $option ]['awardOther'] = $element;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


                        
IPSCONTENT;

// Find award translatable fields 
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

if ( \substr( $k, -20 ) === '_award_subject_badge' ):
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

$parts[ $option ]['awardSubjectTranslatable'] = $element;
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

elseif ( \substr( $k, -18 ) === '_award_other_badge' ):
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

$parts[ $option ]['awardOtherTranslatable'] = $element;
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

endforeach;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

// Now we can build all the HTML
$return .= <<<IPSCONTENT

			<div class="cRulesForm__condition ipsBox i-position_relative i-padding_3 i-margin-bottom_3 ipsFieldRow--fullWidth">
				<h2 class='cRulesForm__condition__title ipsRadius i-text-align_center i-font-size_1 i-font-weight_600 ipsType_veryLight i-flex_00'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievement_rule_action', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<div>{$elements['']['achievement_rule_action']->html()}</div>
			</div>

			
IPSCONTENT;

foreach ( $options as $option => $extension ):
$return .= <<<IPSCONTENT

				<div id='rule_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $option, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="ruleWrap" style='display: none'>
					
IPSCONTENT;

if ( \count( $parts[ $option ]['filter']) ):
$return .= <<<IPSCONTENT

						<div class='cRulesForm__conditionButtons' data-role="conditionButtons">
							
IPSCONTENT;

foreach ( $parts[ $option ]['filter'] as $k => $filter ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->rulesForm_addFilterButton( $k, $filter['element'], $form );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</div>

						
IPSCONTENT;

foreach ( $parts[ $option ]['filter'] as $k => $filter ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->rulesForm_filter( $filter['element'] );
$return .= <<<IPSCONTENT


							
IPSCONTENT;

if ( isset( $filter['subFilters'] ) && \count( $filter['subFilters']) ):
$return .= <<<IPSCONTENT

								<div class='cRulesForm__conditionButtons' data-role="conditionButtons">
									
IPSCONTENT;

foreach ( $filter['subFilters'] as $j => $subFilter ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->rulesForm_addFilterButton( $j, $subFilter, $form );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</div>

								
IPSCONTENT;

foreach ( $filter['subFilters'] as $j => $subFilter ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->rulesForm_filter( $subFilter );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
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
				

					<div class="cRulesForm__condition ipsBox i-position_relative i-padding_3 i-margin-top_3">
						<h2 class='cRulesForm__condition__title ipsRadius i-text-align_center i-font-size_1 i-font-weight_600 ipsType_veryLight i-flex_00'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievement_rule_award', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						<div class='i-flex_11'>
							
IPSCONTENT;

$awardSubject = $parts[ $option ]['awardSubject'];
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$awardOther = $parts[ $option ]['awardOther'] ?? NULL;
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

$awardSubjectTranslatable = $parts[ $option ]['awardSubjectTranslatable'];
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

$awardOtherTranslatable = $parts[ $option ]['awardOtherTranslatable'] ?? NULL;
$return .= <<<IPSCONTENT


							<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $awardSubject->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								
IPSCONTENT;

if ( $awardSubject->label ):
$return .= <<<IPSCONTENT

									<p class='i-font-weight_bold cRulesForm__condition__toDesc'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $awardSubject->label, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								{$awardSubject->html()}
							</div>
                            <div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $awardSubjectTranslatable->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='i-padding-top_3' style='display: none'>
                                <p class='cRulesForm__condition__toDesc'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'Achievement_reason_badge_awarded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                {$awardSubjectTranslatable->html()}
                            </div>

							
IPSCONTENT;

if ( $awardOther !== NULL ):
$return .= <<<IPSCONTENT

								<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $awardOther->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='i-border-top_3 i-margin-top_3 i-padding-top_3'>
									
IPSCONTENT;

if ( $awardOther->label ):
$return .= <<<IPSCONTENT

										<p class='i-font-weight_bold cRulesForm__condition__toDesc'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $awardOther->label, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									{$awardOther->html()}
								</div>
                                <div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $awardOtherTranslatable->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='i-padding-top_3' style='display: none'>
                                    <p class='cRulesForm__condition__toDesc'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'Achievement_reason_badge_awarded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                    {$awardOtherTranslatable->html()}
                                </div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>			
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
		<div class='i-padding_3 i-flex i-justify-content_end'>
			<button type="submit" class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save_rule', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function rulesForm_addFilterButton( $k, $element, $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-role="toggleFilter">
	<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" style="visibility: hidden; position: absolute">
		<input type="checkbox" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_filterCheckbox" name="activeFilters[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" data-control="toggle" data-togglesOn="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_filter" data-togglesOff="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_button" data-filter="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_filter" 
IPSCONTENT;

if ( \in_array( $k, $form->_activeFilters ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	</div>
	<button type="button" class='cRulesForm__conditionButton ipsRadius i-font-size_-1' data-action="filterReveal" data-filter="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_filterCheckbox" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_button">
		<span class='cRulesForm__conditionButton__icon i-display_inline-flex i-align-items_center i-justify-content_center'><i class='fa-solid fa-plus'></i></span> 
IPSCONTENT;

if ( $element->label ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->label, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$element->name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</button>
</div>
IPSCONTENT;

		return $return;
}

	function rulesForm_filter( $element ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-role="filterField" class='cRulesForm_conditionWrap' id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_filter" style='display: none;'>
	<div class="cRulesForm__condition cRulesForm__condition--subCondition ipsBox i-position_relative i-padding_3 i-margin-top_3">
		<h2 class='cRulesForm__condition__title ipsRadius i-text-align_center i-font-size_1 i-font-weight_600 i-flex_00'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievement_filter', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class='i-flex i-align-items_center i-flex_11 i-gap_2'>
			<div>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->prefix, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
			<div>{$element->html()}</div>
			<button type="button" data-action="filterCollapse" data-filter="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_filterCheckbox" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_condition', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='cRulesForm__condition__close'><i class="fa-solid fa-xmark"></i></button>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function rulesList( $rules, $pagination, $rootButtons ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=achievements&controller=rules", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-controller="core.global.core.table">
	<!--<div class="ipsBox i-padding_3 i-margin-bottom_1">
		<ul class="ipsList ipsList--inline">
			<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievement_rules_filters', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></li>
			
IPSCONTENT;

foreach ( [ 'points', 'badges', 'milestone' ] as $filter ):
$return .= <<<IPSCONTENT

				<li>
					<input type="checkbox" id="filter_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $filter, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" name="filter_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $filter, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle" data-action="filter" checked>
					<label for="filter_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $filter, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "achievement_rules_filters_{$filter}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>-->
	
IPSCONTENT;

if ( $data = \IPS\core\Achievements\Rule::getRebuildProgress() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->rebuildProgress( $data );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class=''>
		<div data-role="tablePagination" class="i-margin-bottom_1 
IPSCONTENT;

if ( !$pagination ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">{$pagination}</div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->buttons( $rootButtons );
$return .= <<<IPSCONTENT

	</div>
	<div class="ipsBox">
		<div class="ipsTree_wrapper">
            
IPSCONTENT;

if ( empty( $rules ) ):
$return .= <<<IPSCONTENT

            <div class='i-color_soft i-padding_3'>
                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

            </div>
            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class="ipsTree_rows">
				<ol class="ipsTree" data-role="tableRows">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->rulesListRows( $rules );
$return .= <<<IPSCONTENT

				</ol>
			</div>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	<div data-role="tablePagination" class='i-margin-top_2 
IPSCONTENT;

if ( !$pagination ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>{$pagination}</div>
</div>
IPSCONTENT;

		return $return;
}

	function rulesListRows( $rules ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rules as $rule ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$canUse = $rule->extension()->canUse();
$return .= <<<IPSCONTENT

	<li data-role="node">
		<div class="ipsTree_row">
			<div class="ipsTree_align">
				<div class="ipsTree_rowData 
IPSCONTENT;

if ( !$rule->enabled or !$canUse ):
$return .= <<<IPSCONTENT
i-opacity_4
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					<div class="i-flex i-flex-wrap_wrap i-gap_2">
						<div class="i-flex_01 i-basis_600">
							<div class="ipsTree_title">
								{$rule->extension()->ruleDescription( $rule )}
							</div>
							
IPSCONTENT;

if ( !$canUse ):
$return .= <<<IPSCONTENT

								<div class="i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_rule_disabled_reason', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !$rule->enabled ):
$return .= <<<IPSCONTENT

								<div class="ipsTree_description">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_rule_paused', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
							
IPSCONTENT;

elseif ( !$canUse ):
$return .= <<<IPSCONTENT

								<div class="ipsTree_description">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_rule_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<div class="i-flex_11 i-basis_400">
							<div class="ipsTree_row_cells">
								<!-- 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_awards', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 -->
								<div class='i-grid i-gap_2'>
									
IPSCONTENT;

if ( ( $rule->points_subject or $rule->badgeSubject() ) and ( $rule->points_other or $rule->badgeOther() ) ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->ruleAwards( $rule->points_subject, $rule->badgeSubject(), $rule->extension()->awardOptions( $rule->filters )['subject'] );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->ruleAwards( $rule->points_other, $rule->badgeOther(), $rule->extension()->awardOptions( $rule->filters )['other'] );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

elseif ( $rule->points_subject or $rule->badgeSubject() ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->ruleAwards( $rule->points_subject, $rule->badgeSubject(), $rule->extension()->awardOptions( $rule->filters )['subject'] );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

elseif ( $rule->points_other or $rule->badgeOther() ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->ruleAwards( $rule->points_other, $rule->badgeOther(), $rule->extension()->awardOptions( $rule->filters )['subject'] );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "achievements", \IPS\Request::i()->app )->ruleAwards( $rule->points_other, NULL, NULL );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</div>
						</div>
					</div>
				</div>				
				<div class="ipsTree_controls">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( $rule->getButtons( \IPS\Http\Url::internal('app=core&module=achievements&controller=rules') ) );
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function statsBadgeCount( $count, $groupId, $startTime, $endTime ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=stats&controller=badges&do=showBadges&member_group_id={$groupId}&badgeDateStart={$startTime}&badgeDateEnd={$endTime}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size="narrow" data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_badges_count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_badge_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $count, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

		return $return;
}

	function statsBadgeModal( $results ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="ipsTableScroll">
		<table class="ipsTable ipsTable_zebra " data-role="table">
			<thead>
				<tr class="i-background_3">
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_badge_mgroup_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_badge_mgroup_count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				</tr>
			</thead>
			<tbody>
				
IPSCONTENT;

foreach ( $results as $row ):
$return .= <<<IPSCONTENT

					<tr class="">
						<td class=" ipsTable_wrap ipsTable_primary ">
							{$row['badge']->html('ipsDimension:3')} 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['badge']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</td>
						<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
					</tr>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</tbody>
		</table>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function statsBadgeWrapper( $form, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ips-template="statsBadgeWrapper">
	<div class='ipsBox'>
		<h1 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_badges_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<div>
			{$form}
		</div>
	</div>
</div>
<div class='i-margin-top_4'>
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function statsMemberBadgeCount( $count, $memberId, $startTime, $endTime ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=stats&controller=badges&do=showMemberBadges&member_id={$memberId}&badgeDateStart={$startTime}&badgeDateEnd={$endTime}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size="narrow" data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_badges_count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_badge_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $count, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

		return $return;
}

	function statsMemberBadgeModal( $results ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="ipsTableScroll">
		<table class="ipsTable ipsTable_zebra " data-role="table">
			<thead>
				<tr class="i-background_3">
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_badge_member_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_badge_member_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				</tr>
			</thead>
			<tbody>
				
IPSCONTENT;

foreach ( $results as $row ):
$return .= <<<IPSCONTENT

					<tr class="">
						<td class=" ipsTable_wrap ipsTable_primary ">
							{$row['badge']->html('ipsDimension:3')} 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['badge']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</td>
						<td>
IPSCONTENT;

$val = ( $row['datetime'] instanceof \IPS\DateTime ) ? $row['datetime'] : \IPS\DateTime::ts( $row['datetime'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</td>
					</tr>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</tbody>
		</table>
	</div>
</div>
IPSCONTENT;

		return $return;
}}