<?php
namespace IPS\Theme;
class class_core_admin_settings extends \IPS\Theme\Template
{	function authySetupProtection( $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_setup_protection_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input name="authy_setup_protection[0]" type="number" class="ipsInput ipsField_tiny" min="1" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_setup_protection_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input name="authy_setup_protection[1]" type="number" class="ipsInput ipsField_tiny" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_setup_protection_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function dataLayerContext( $propertyBlock ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3'>
	<div class="">
		<h2 class="ipsTitle ipsTitle--h3 i-margin-bottom_2" >
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_page_context', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="i-font-size_2 i-color_soft i-margin-bottom_3 i-padding-bottom_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_page_content_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</div>
	<div id='table_area'>
		{$propertyBlock}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function dataLayerSelector( $items, $tab, $itemKey, $head, $activeKey='', $pre=true ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="i-padding_2">
    <h2 class="ipsTitle ipsTitle--h3 i-margin-bottom_3">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $head, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
    <ul class="ipsList i-gap_1" id="dataLayerSelector">
      
IPSCONTENT;

foreach ( $items as $key => $item ):
$return .= <<<IPSCONTENT

        <li class="
IPSCONTENT;

if ( $key === $activeKey ):
$return .= <<<IPSCONTENT
ipsButton_active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 listItem">
            <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=dataLayer&tab={$tab}&{$itemKey}={$key}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
                <span class='ipsOnlineStatus ipsOnlineStatus_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( ($item['enabled'] ?? 1) ? 'online' : 'offline', ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></span>
                <span data-ipstooltip title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['short'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-font-family_monospace" style="padding:4px 0px;" >{$item['formatted_name']}</span>
            </a>
        </li>
      
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

    </ul>
</div>

IPSCONTENT;

		return $return;
}

	function dataLayerTab( $left, $right ) {
		$return = '';
		$return .= <<<IPSCONTENT


<main class="ipsColumns ipsColumns--lines">
	<div class="ipsColumns__secondary i-basis_280">
        {$left}
	</div>
	<div class="ipsColumns__primary i-padding_3" id="table_area">
		{$right}
	</div>
</main>
IPSCONTENT;

		return $return;
}

	function dataLayerTitleContent( $item, $item_key, $type='property', $form='', $table='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsSpanGrid i-border-bottom_3">
    <div class="ipsSpanGrid__4 i-padding-end_3">
        <h2 class='i-flex i-align-items_center i-gap_2'>
            <p data-name-field class='i-font-family_monospace i-font-weight_600 i-font-size_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['formatted_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
            
IPSCONTENT;

if ( $item['custom'] ?? 0 ):
$return .= <<<IPSCONTENT

                <a id="deleteProperty" class='ipsButton ipsButton--tiny ipsButton--negative' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=dataLayer&do=deleteProperty&property_key={$item_key}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
                    <i class='fa-solid fa-circle-minus'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                </a>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </h2>
        <div class="i-margin-top_2">
            <div class="ipsBadges i-margin-bottom_2">
                
IPSCONTENT;

if ( $type === 'property' ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$badgeStyle = $item['enabled'] ? 'positive' : 'negative';
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( $item['pii'] ):
$return .= <<<IPSCONTENT

                    <span class='ipsBadge ipsBadge--intermediary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_pii', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( $item['page_level'] ):
$return .= <<<IPSCONTENT

                    <span class='ipsBadge ipsBadge--style2'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=dataLayer&tab=pageContext", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_page_context', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></span>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    <span class='ipsBadge ipsBadge--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badgeStyle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $item['enabled'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_enabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
                    
IPSCONTENT;

if ( $item['custom'] ?? 0 ):
$return .= <<<IPSCONTENT

                    <span class='ipsBadge ipsBadge--neutral'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </div>
            <p>{$item['description']}</p>
        
IPSCONTENT;

if ( $item['custom'] ?? 0 ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$value = $item['value'] ?? 'undefined';
$return .= <<<IPSCONTENT

            <p>
IPSCONTENT;

$htmlsprintf = array($value); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_note_dec', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( $item['pii'] ?? 0 ):
$return .= <<<IPSCONTENT

            <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_pii_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>
    </div>
    <div class="ipsSpanGrid__8">
        <h2 class="ipsFieldRow__section">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
        {$form}
        <br>
    </div>
</div>
<br>


IPSCONTENT;

if ( $table ):
$return .= <<<IPSCONTENT

    <h3 class="ipsTitle ipsTitle--h4 i-margin-bottom_2">
        
IPSCONTENT;

if ( $type==='property' ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_events_using_property', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_events_properties', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </h3>
    {$table}

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

<p class="i-margin_3">
    
IPSCONTENT;

if ( $type === 'property' ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_no_events_use_property', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_no_events_here', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function dataStoreChange( $downloadUrl, $checkUrl, $error=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-text-align_center">
	<p class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datastore_change_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<div class="i-padding_3">
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $downloadUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datastore_change_download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a> <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $checkUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
	
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

		<p class="i-color_warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datastore_change_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function formWrapper( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div id="dataLayerContent" >
    {$form}
</div>
IPSCONTENT;

		return $return;
}

	function handlers( $handlers=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="i-margin_3" >
    <h2 class="i-margin-bottom_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
    <div class="ipsType i-margin-bottom_3 i-padding-bottom_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
    <div>
        
IPSCONTENT;

if ( empty($handlers) ):
$return .= <<<IPSCONTENT

        <p class="i-padding-block_3">
            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_custom_handler_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

        </p>
        <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=dataLayer&do=addHandler", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_custom_handler_create', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

        
IPSCONTENT;

foreach ( $handlers as $handler ):
$return .= <<<IPSCONTENT

        <div>
            
IPSCONTENT;

$dataLayerKey = \IPS\Member::loggedIn()->language()->addToStack('datalayer_custom_title_prefix') . ' ' . $handler->datalayer_key;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "trees", "core", 'admin' )->row( $handler->url(), $handler->id, $handler->name, false, $handler->buttonHtml, $dataLayerKey, NULL, NULL, FALSE, $handler->enabled );
$return .= <<<IPSCONTENT

        </div>
        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>
</div>

IPSCONTENT;

		return $return;
}

	function reputationLeaderboardRebuild(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox'>
	<div class="i-padding_3">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_leaderboard_rebuild_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		<p>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=membersettings&controller=reputation&do=rebuildLeaderboard&process=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_leaderboard_rebuild_run_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function reputationLike( $blurb ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsLikeRep'>
	<a class='ipsButton ipsButton_like ipsButton--secondary'><i class='fa-solid fa-heart'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	<span class='ipsLike_contents'>{$blurb}</span>
</div>
<br>
<span class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rep_system_like', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function reputationNormal( $pos,$neg ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsLikeRep'>
	
IPSCONTENT;

if ( $pos ):
$return .= <<<IPSCONTENT

		<a class='ipsButton ipsButton_rep ipsButton_repUp'><i class='fa-solid fa-arrow-up'></i></a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $neg ):
$return .= <<<IPSCONTENT

		<a class='ipsButton ipsButton_rep ipsButton_repDown'><i class='fa-solid fa-arrow-down'></i></a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $pos and $neg ):
$return .= <<<IPSCONTENT

		<span class='ipsReputation_count i-link-color_inherit i-color_soft'><i class='fa-solid fa-heart i-font-size_-1'></i> 0</span>
	
IPSCONTENT;

elseif ( $pos ):
$return .= <<<IPSCONTENT

		<span class='ipsReputation_count i-link-color_inherit i-color_positive'><i class='fa-solid fa-heart i-font-size_-1'></i> 5</span>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<span class='ipsReputation_count i-link-color_inherit i-color_negative'><i class='fa-solid fa-heart i-font-size_-1'></i> -5</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<br>
<span class="i-color_soft">
	
IPSCONTENT;

if ( $pos and $neg ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rep_system_both', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( $pos ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rep_system_positive', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rep_system_negative', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</span>
IPSCONTENT;

		return $return;
}

	function searchDecay( $days, $factor ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_decay_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<input type="number" name="search_decay[0]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $days, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsField_short">

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_decay_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<input type="number" name="search_decay[1]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $factor, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" step="0.1" class="ipsInput ipsField_short">

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_decay_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<div class="ipsFieldRow_inlineCheckbox">
	&nbsp;
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	&nbsp;
	<input type="checkbox" class="ipsInput ipsInput--toggle" data-control="unlimited" name="search_decay[2]" id="search_decay-unlimitedCheck" value="0" 
IPSCONTENT;

if ( !$factor ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby="search_decay_label">
	<label for="search_decay-unlimitedCheck" id="search_decay_label" class="ipsField_unlimited">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_decay_unlimited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
</div>
IPSCONTENT;

		return $return;
}

	function verifySetupProtection( $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_setup_protection_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input name="verify_setup_protection[0]" type="number" class="ipsInput ipsField_tiny" min="1" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_setup_protection_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input name="verify_setup_protection[1]" type="number" class="ipsInput ipsField_tiny" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_setup_protection_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}