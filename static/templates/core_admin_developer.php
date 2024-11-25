<?php
namespace IPS\Theme;
class class_core_admin_developer extends \IPS\Theme\Template
{	function blockFooter( $app ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-text-align_center i-margin-top_3">
    <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=developer&controller=details&appKey={$app}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_check_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function dashboard( $app ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_2 i-margin-bottom_1'>
    <h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
    <div class='i-border-bottom_2 i-padding-bottom_1'>
        
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->author, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $app->website ):
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->website, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square-square" title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->website, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>
    <div class='ipsClearix i-padding-top_2'>
        <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=developer&controller=versions&appKey={$app->directory}&do=addVersion", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--tiny i-float_end'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'versions_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'app_details_app_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$sprintf = array($app->version, $app->long_version); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'app_version_string', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

    </div>
</div>
<div class='ipsBox i-padding_2 i-margin-bottom_1' data-role="devCenterIssues">
    <div class='ipsClearfix'>
        <div class='elBlockTitle i-padding_1 i-flex i-align-items_center i-justify-content_space-between i-border-bottom_3'>
            <div>
                <h2 class='i-font-size_2 i-font-weight_600 i-color_hard'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'devscan_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
            </div>
            <div>
                <span class='i-color_warning ipsHide' data-iconType="critical"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <span class='i-color_issue ipsHide' data-iconType="recommended"><i class="fa-solid fa-circle-info"></i></span>
            </div>
        </div>
        <div data-role="devCenterIssues_content"></div>
    </div>
</div>
<div class='ipsGrid ipsGrid--2'>
    
IPSCONTENT;

foreach ( array( 'admin', 'front' ) as $location ):
$return .= <<<IPSCONTENT

    <section class='ipsWidget'>
        <header class='ipsWidget__header i-flex i-align-items_center'>
            <div class="i-flex_11">
IPSCONTENT;

$val = "menu__core_developer_{$location}_modules{$location}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
            <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=developer&controller=modules&appKey={$app->directory}&location={$location}&do=moduleForm", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--icon ipsButton--tiny ipsButton--inherit' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modules_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip><i class='fa-solid fa-plus-circle'></i></a>
        </header>
        <i-data>
            <ol class="ipsData ipsData--table ipsData--compact ipsData--dashboard">
                
IPSCONTENT;

foreach ( $app->modules( $location ) as $module ):
$return .= <<<IPSCONTENT

                    <li class='ipsData__item'>
                        <div class='ipsData__main'>
                            
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $module->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                        </div>
                        <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=developer&controller=modules&appKey={$app->directory}&location={$location}&do=addController&module_key={$module->key}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--icon ipsButton--tiny ipsButton--inherit i-flex_00' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'modules_add_controller', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip><i class='fa-solid fa-plus-circle'></i></a>
                    </li>
                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            </ol>
        </i-data>
    </section>
    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function duplicateSearchKeywords( $strings, $app ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-font-size_-1">
    <pre class="prettyprint lang-txt">

    
IPSCONTENT;

foreach ( $strings as $string ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $string, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
</pre>
</div>

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "developer", "core" )->blockFooter( $app );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function furlRowDescription( $data, $topLevel='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
    
IPSCONTENT;

if ( $topLevel ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topLevel, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['friendly'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $data['alias'] ) and $data['alias'] ):
$return .= <<<IPSCONTENT
<span class='i-font-style_italic i-font-size_-2 i-color_soft'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['alias'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<div class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['real'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

		return $return;
}

	function missingSearchKeywords( $strings, $app ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $strings as $group => $missing ):
$return .= <<<IPSCONTENT

<div class="i-font-size_-1 i-margin-top_2">
    <div class='i-font-weight_600'>
IPSCONTENT;

$val = "devscan__search_keywords_{$group}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
    <pre class="prettyprint lang-txt">

    
IPSCONTENT;

foreach ( $missing as $string ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $string, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
</pre>
</div>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "developer", "core" )->blockFooter( $app );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function missingStrings( $strings, $app ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $strings as $group => $missing ):
$return .= <<<IPSCONTENT

<div class="i-font-size_-1 i-margin-top_2">
    <div class='i-font-weight_600'>
IPSCONTENT;

$val = "devscan__strings_{$group}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
    <pre class="prettyprint lang-txt">

    
IPSCONTENT;

foreach ( $missing as $string ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $string, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
</pre>
</div>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "developer", "core" )->blockFooter( $app );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function missingTemplates( $templates, $app ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-font-size_-1">
    <div class='i-font-weight_600'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'devscan__emailtpl', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
    <pre class="prettyprint lang-txt">

    
IPSCONTENT;

foreach ( $templates as $template ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
</pre>
</div>

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "developer", "core" )->blockFooter( $app );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}