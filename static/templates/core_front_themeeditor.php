<?php
namespace IPS\Theme;
class class_core_front_themeeditor extends \IPS\Theme\Template
{	function editorPanel( $category ) {
		$return = '';
		$return .= <<<IPSCONTENT

<editor-panel id='panel__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-hidden='true' inert>
    <button type="button" data-panel-nav="back" data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_back', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
    <div class='theme-editor__scroll'>
        <header>
            <h2 data-panel-name>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
		</header>
		
IPSCONTENT;

if ( $category->hasChildren( null, null, false ) ):
$return .= <<<IPSCONTENT

		    <nav class='theme-editor__nav'>
		        
IPSCONTENT;

foreach ( $category->children( null, null, false ) as $child ):
$return .= <<<IPSCONTENT

		            
IPSCONTENT;

if ( $child->hasContents() ):
$return .= <<<IPSCONTENT

		            <button aria-controls='panel__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-panel-nav type='button' data-on-click="panelNavigation"><i class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->icon(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</button>
		            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		    </nav>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $category->hasSettings() and $category->hasColors() ):
$return .= <<<IPSCONTENT

		    <editor-tabs>
		        <button type='button' aria-controls='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__settings' aria-expanded='true' data-ipscontrols><i class='fa-solid fa-sliders'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_panel_settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		        <button type='button' aria-controls='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__colors' aria-expanded='false' data-ipscontrols><i class='fa-solid fa-palette'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_panel_colors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		    </editor-tabs>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $category->hasSettings() ):
$return .= <<<IPSCONTENT

		    <editor-tab-content id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__settings' data-ips-hidden-group='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		        
IPSCONTENT;

foreach ( $category->settings() as $setting ):
$return .= <<<IPSCONTENT

		            {$setting->editorHtml()}
		        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		    </editor-tab-content>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $category->hasColors() ):
$return .= <<<IPSCONTENT

		    <editor-tab-content id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__colors' data-ips-hidden-group='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $category->hasSettings() ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		        
IPSCONTENT;

foreach ( $category->colors() as $color ):
$return .= <<<IPSCONTENT

		            {$color->editorHtml()}
		        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		    </editor-tab-content>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</editor-panel>

IPSCONTENT;

if ( $category->hasChildren( null, null, false ) ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

foreach ( $category->children( null, null, false ) as $child ):
$return .= <<<IPSCONTENT

        {$child->editorHtml()}
    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function settingCheckbox( $setting ) {
		$return = '';
		$return .= <<<IPSCONTENT

<theme-setting data-setting='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->refresh ):
$return .= <<<IPSCONTENT
data-setting-refresh
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <div class="theme-editor__align-setting">
        <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__checkbox'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
        <button class='theme-editor__revert' type='button' data-revert='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->value() == $setting->defaultValue() ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-on-click="revertSetting"><i class="fa-solid fa-clock-rotate-left"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
        <input type='text' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->value(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-default='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->defaultValue(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
        <input id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__checkbox' type='checkbox' data-range-output='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->value() == 1 ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-on-change="syncInputsWithPseudo">
    </div>
    
IPSCONTENT;

if ( $desc = $setting->desc ):
$return .= <<<IPSCONTENT

        <small>{$desc}</small>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</theme-setting>
IPSCONTENT;

		return $return;
}

	function settingColor( $setting ) {
		$return = '';
		$return .= <<<IPSCONTENT

<theme-setting data-setting='light__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-type='color' 
IPSCONTENT;

if ( $setting->refresh ):
$return .= <<<IPSCONTENT
data-setting-refresh
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <input type='text' name='light__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->value()['light'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-default="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->default['light'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" hidden data-on-change="settingHasBeenChanged">
    <button class="theme-editor__swatch" type="button" aria-controls='panel__colorSelector' data-panel-nav data-color-tool='swatchPicker' data-controls='light__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style='--swatch: var(--light__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
);' data-on-click="panelNavigation">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</button>
    <button class='theme-editor__revert' type='button' data-revert='light__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->value()['light'] == $setting->default['light'] ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-on-click="revertSetting"><i class="fa-solid fa-clock-rotate-left"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
</theme-setting>
<theme-setting data-setting='dark__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-type='color' 
IPSCONTENT;

if ( $setting->refresh ):
$return .= <<<IPSCONTENT
data-setting-refresh
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <input type='text' name='dark__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->value()['dark'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-default="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->default['dark'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" hidden data-on-change="settingHasBeenChanged">
    <button class="theme-editor__swatch" type="button" aria-controls='panel__colorSelector' data-panel-nav data-color-tool='swatchPicker' data-controls='dark__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style='--swatch: var(--dark__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
);' data-on-click="panelNavigation">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</button>
    <button class='theme-editor__revert' type='button' data-revert='dark__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->value()['dark'] == $setting->default['dark'] ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-on-click="revertSetting"><i class="fa-solid fa-clock-rotate-left"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
</theme-setting>
IPSCONTENT;

		return $return;
}

	function settingImage( $setting ) {
		$return = '';
		$return .= <<<IPSCONTENT

<theme-setting data-setting='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->refresh ):
$return .= <<<IPSCONTENT
data-setting-refresh
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
    <button class='theme-editor__revert' type='button' data-revert='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->value() == $setting->defaultValue() ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-on-click="revertSetting"><i class="fa-solid fa-clock-rotate-left"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
    <div class='theme-editor__input-file'>
        <input type='file' accept='image/*' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-default="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->defaultValue(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-on-change="settingHasBeenChanged">
        <div data-file-preview="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
            
IPSCONTENT;

if ( $value = $setting->value() ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( !empty( \trim( $value ) ) ):
$return .= <<<IPSCONTENT

                    <img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt=''>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>
		<button type="button" data-file-preview-delete="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-on-click="deleteLogoImage"><i class="fa-regular fa-trash-can"></i><span class='sr-only'>Delete</span></button>
	</div>
    
IPSCONTENT;

if ( $desc = $setting->desc ):
$return .= <<<IPSCONTENT

        <small>{$desc}</small>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</theme-setting>
IPSCONTENT;

		return $return;
}

	function settingRange( $setting ) {
		$return = '';
		$return .= <<<IPSCONTENT

<theme-setting data-setting='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->refresh ):
$return .= <<<IPSCONTENT
data-setting-refresh
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <div class="theme-editor__align-setting">
        <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__num'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
        <button class='theme-editor__revert' type='button' data-revert='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->value() == $setting->defaultValue() ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-on-click="revertSetting"><i class="fa-solid fa-clock-rotate-left"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
        <input type='number' inputmode='numeric' data-range-output='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__num' value='{$setting->value()}' 
IPSCONTENT;

if ( isset( $setting->data['min'] ) ):
$return .= <<<IPSCONTENT
min="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->data['min'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( isset( $setting->data['max'] ) ):
$return .= <<<IPSCONTENT
max="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->data['max'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( isset( $setting->data['step'] ) ):
$return .= <<<IPSCONTENT
step="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->data['step'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-on-change="syncInputsWithPseudo">
    </div>
    <input type='range' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='{$setting->value()}' 
IPSCONTENT;

if ( isset( $setting->data['min'] ) ):
$return .= <<<IPSCONTENT
min="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->data['min'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( isset( $setting->data['max'] ) ):
$return .= <<<IPSCONTENT
max="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->data['max'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( isset( $setting->data['step'] ) ):
$return .= <<<IPSCONTENT
step="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->data['step'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-default="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->defaultValue(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-on-input="changingRangeInput" data-on-change="settingHasBeenChanged">
    
IPSCONTENT;

if ( $desc = $setting->desc ):
$return .= <<<IPSCONTENT

        <small>{$desc}</small>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</theme-setting>
IPSCONTENT;

		return $return;
}

	function settingSelect( $setting ) {
		$return = '';
		$return .= <<<IPSCONTENT

<theme-setting data-setting='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->refresh ):
$return .= <<<IPSCONTENT
data-setting-refresh
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <div class="theme-editor__align-setting">
        <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
        <button class='theme-editor__revert' type='button' data-revert='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->value() == $setting->defaultValue() ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-on-click="revertSetting"><i class="fa-solid fa-clock-rotate-left"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
        <select id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-default="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->defaultValue(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-on-change="settingHasBeenChanged">
            
IPSCONTENT;

foreach ( $setting->data['options'] as $option ):
$return .= <<<IPSCONTENT

                <option value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $option[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $option[0] == $setting->value() ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $option[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        </select>
    </div>
    
IPSCONTENT;

if ( $desc = $setting->desc ):
$return .= <<<IPSCONTENT

        <small>{$desc}</small>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</theme-setting>
IPSCONTENT;

		return $return;
}

	function settingText( $setting ) {
		$return = '';
		$return .= <<<IPSCONTENT

<theme-setting data-setting='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->refresh ):
$return .= <<<IPSCONTENT
data-setting-refresh
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    <div class="theme-editor__align-setting">
        <label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
        <button class='theme-editor__revert' type='button' data-revert='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $setting->value() == $setting->defaultValue() ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-on-click="revertSetting"><i class="fa-solid fa-clock-rotate-left"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
    </div>
    <input type='text' spellcheck='false' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='{$setting->value()}' enterkeyhint='done' data-default="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting->defaultValue(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-on-input="changingTextInput" data-on-change="settingHasBeenChanged">
    
IPSCONTENT;

if ( $desc = $setting->desc ):
$return .= <<<IPSCONTENT

        <small>{$desc}</small>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</theme-setting>
IPSCONTENT;

		return $return;
}

	function themeEditorTemplate(  ) {
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

	data-ips-baseurl="
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
"
	data-workspace-color="light"
	data-workspace-size="large"
>
	<head>
		<title>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->prefersColorSchemeLoad(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->favico(  );
$return .= <<<IPSCONTENT

	</head>
	<body data-controller="core.front.core.app">

		<theme-editor>

			<live-preview>
				<iframe src='
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
' id='themeEditorIframe'></iframe>
		 	</live-preview>

			<form class='editor__form' action='
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
?app=core&module=system&controller=themeeditor&do=save' method='post' enctype='multipart/form-data' role="form">
				<editor-buttons>
					<div data-workspace='color'>
						<button value='light' type='button' data-on-click="changeWorkspace">
							<span class='editor-buttons__tooltip'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_light_colors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							<i class="fa-regular fa-lightbulb"></i>	
						</button>
						<button value='dark' type='button' data-on-click="changeWorkspace">
							<span class='editor-buttons__tooltip'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_dark_colors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							<i class="fa-regular fa-moon"></i>	
						</button>
					</div>
					<div data-workspace='size'>
						<button value='small' type='button' data-on-click="changeWorkspace">
							<span class='editor-buttons__tooltip'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_size_small', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							<i class="fa-solid fa-mobile-screen-button"></i>
						</button>
						<button value='medium' type='button' data-on-click="changeWorkspace">
							<span class='editor-buttons__tooltip'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_size_medium', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							<i class="fa-solid fa-tablet-screen-button"></i>
						</button>
						<button value='large' type='button' data-on-click="changeWorkspace" data-active>
							<span class='editor-buttons__tooltip'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_size_large', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							<i class="fa-solid fa-display"></i>
						</button>
					</div>
					<div>
						<button type='button' aria-controls='dialog__customCSS' aria-expanded="false" data-ipscontrols>
							<span class='editor-buttons__tooltip'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_custom_css', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							<i class="fa-solid fa-code"></i>
						</button>
					</div>
					<div class="editor-buttons__save">
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=themeeditor&do=close", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-on-click="closeEditor">
							<span class='editor-buttons__tooltip'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							<i class="fa-solid fa-arrow-right-from-bracket"></i>
							<span class="editor-buttons__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_exit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</a>
						<button type='submit' data-on-click="saveChanges">
							<span class='editor-buttons__tooltip'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							<i class="fa-solid fa-save"></i>
							<span class="editor-buttons__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</button>
					</div>
				</editor-buttons>

				<editor-panels>

					<!-- Start panel -->
					<editor-panel id='panel__start' aria-hidden='false'>
						<div class='theme-editor__scroll'>
							<header>
								<h2 data-panel-name>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
							</header>
							<nav class='theme-editor__nav'>
								<button type='button' aria-controls='dialog__customCSS' aria-expanded="false" data-ipscontrols><i class="fa-solid fa-code"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_custom_css', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								<hr>
								<button aria-controls='panel__scheme' data-panel-nav type='button' data-on-click="panelNavigation"><i class="fa-solid fa-palette"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_palette', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								
IPSCONTENT;

foreach ( \IPS\Theme\Editor\Category::themeEditorCategories() as $category ):
$return .= <<<IPSCONTENT

								    
IPSCONTENT;

if ( $category->hasContents() ):
$return .= <<<IPSCONTENT

								    <button aria-controls='panel__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-panel-nav type='button' data-on-click="panelNavigation"><i class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->icon(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</button>
								    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</nav>
						</div>
					</editor-panel>

					<!-- Color palette -->
					<editor-panel id='panel__scheme' aria-hidden='true' inert>
						<button type="button" data-panel-nav="back" data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_back', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
						<div class='theme-editor__scroll'>
							<header>
								<h2 data-panel-name>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_palette', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
							</header>
							<ul id='content__scheme__settings' class="theme-editor__settings">
								<!-- These can stay hard-coded since they'll never change -->
								<li class='theme-editor__palette'>
									<input type='text' name='light__i-primary' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-primary' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='light__i-primary-relative-l' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-primary-relative-l' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<button type="button" aria-controls='panel__colorSelector' data-panel-nav data-color-tool='colorPicker' data-controls='light__i-primary' data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_primary', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								
									<input type='text' name='light__i-secondary' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-secondary' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='light__i-secondary-relative-l' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-secondary-relative-l' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<button type="button" aria-controls='panel__colorSelector' data-panel-nav data-color-tool='colorPicker' data-controls='light__i-secondary' data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_secondary', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								
									<input type='text' name='light__i-base' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='light__i-base-l' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base-l' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='light__i-base-c' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base-c' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='light__i-base-h' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base-h' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<button type="button" aria-controls='panel__oklch' data-panel-nav data-color-tool='colorPicker' data-controls='light__i-base' data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_base', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								
									<input type='text' name='dark__i-primary' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-primary' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='dark__i-primary-relative-l' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-primary-relative-l' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<button type="button" aria-controls='panel__colorSelector' data-panel-nav data-color-tool='colorPicker' data-controls='dark__i-primary' data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_primary', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								
									<input type='text' name='dark__i-secondary' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-secondary' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='dark__i-secondary-relative-l' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-secondary-relative-l' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<button type="button" aria-controls='panel__colorSelector' data-panel-nav data-color-tool='colorPicker' data-controls='dark__i-secondary' data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_secondary', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								
									<input type='text' name='dark__i-base' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='dark__i-base-l' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base-l' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='dark__i-base-c' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base-c' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<input type='text' name='dark__i-base-h' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base-h' );
$return .= <<<IPSCONTENT
' hidden data-on-change="settingHasBeenChanged">
									<button type="button" aria-controls='panel__oklch' data-panel-nav data-color-tool='colorPicker' data-controls='dark__i-base' data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_base', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								</li>
							</ul>
						</div>
					</editor-panel>

					<!-- 
						Swatch and color picker
						This panel can stay hard-coded
					-->
					<editor-panel id='panel__colorSelector' data-show-tool aria-hidden='true' inert>
						<button type="button" data-panel-nav="back" data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_back', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
						<div class='theme-editor__scroll'>
							<header class='i-flex i-align-items_center i-gap_2'>
								<div data-color-preview></div>
								<div class='i-flex_11'>
									<h2 data-panel-name>
										<span data-active-name></span>
									</h2>
								</div>
							</header>
							<editor-tabs>
								<button type='button' aria-controls='content__colorSelector__swatches' aria-expanded='true' data-ipscontrols><i class="fa-solid fa-swatchbook"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_swatches', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								<button type='button' aria-controls='content__colorSelector__colorPicker' aria-expanded='false' data-ipscontrols><i class="fa-solid fa-eye-dropper"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_color_picker', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
							</editor-tabs>
							<editor-tab-content id='content__colorSelector__swatches' data-ips-hidden-group='swatch-color-picker'>
								<div data-swatches>
									<ul class='theme-editor__swatches' data-swatch-category='base'>
									    
IPSCONTENT;

for ( $i=1; $i<=6; $i++ ):
$return .= <<<IPSCONTENT

										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-base_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)' style='--swatch:var(--i-base_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_base', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 #
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</button></li>
										
IPSCONTENT;

endfor;
$return .= <<<IPSCONTENT

									</ul>
									<ul class='theme-editor__swatches' data-swatch-category='contrast'>
									    
IPSCONTENT;

for ( $i=1; $i<=6; $i++ ):
$return .= <<<IPSCONTENT

										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-base-contrast_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)' style='--swatch:var(--i-base-contrast_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_contrast', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 #
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</button></li>
										
IPSCONTENT;

endfor;
$return .= <<<IPSCONTENT

									</ul>
									<ul class='theme-editor__swatches' data-swatch-category='brand'>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-primary)' style='--swatch:var(--i-primary);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_primary', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-primary-light)' style='--swatch:var(--i-primary-light);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_primary_light', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-primary-dark)' style='--swatch:var(--i-primary-dark);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_primary_dark', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-color_primary)' style='--swatch:var(--i-color_primary);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_primary_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-primary-contrast)' style='--swatch:var(--i-primary-contrast);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_primary_contrast', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
									</ul>
									<ul class='theme-editor__swatches' data-swatch-category='brand'>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-secondary)' style='--swatch:var(--i-secondary);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_secondary', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-secondary-light)' style='--swatch:var(--i-secondary-light);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_secondary_light', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-secondary-dark)' style='--swatch:var(--i-secondary-dark);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_secondary_dark', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-color_secondary)' style='--swatch:var(--i-color_secondary);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_secondary_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
										<li><button class="theme-editor__swatch" type="button" data-swatch value='var(--i-secondary-contrast)' style='--swatch:var(--i-secondary-contrast);' data-on-click="swatchClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_secondary_contrast', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
									</ul>
								</div>
							</editor-tab-content>
							<editor-tab-content id='content__colorSelector__colorPicker' data-ips-hidden-group='swatch-color-picker' hidden>
								<div data-color-picker>
									<color-picker></color-picker>
									<input type='text' id="color-picker-text" data-color-picker-text data-on-change="colorPickerTextBlur">
								</div>
							</editor-tab-content>
						</div>				
					</editor-panel>

					<!-- 
						OKLCH color picker
					-->
					<editor-panel id='panel__oklch' data-show-tool aria-hidden='true' inert>
						<button type="button" data-panel-nav="back" data-on-click="panelNavigation">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_back', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
						<div class='theme-editor__scroll'>
							<header class='i-flex i-align-items_center i-gap_2'>
								<div data-color-preview style="background-color: oklch(var(--if-light, calc(var(--i-base-l) * 20% + 75%)) var(--if-dark, calc(var(--i-base-l) * 20% + 30%)) calc(var(--i-base-c) * 40%) var(--i-base-h))"></div>
								<div class='i-flex_11'>
									<h2 data-panel-name>
										<span data-active-name></span>
									</h2>
								</div>
							</header>
							<editor-tab-content>

								<theme-setting data-setting='light__i-base-h' data-type='number'>
									<label for='light__i-base-h__num'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_hue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
									<input type='number' inputmode='numeric' data-range-output='light__i-base-h' id='light__i-base-h__num' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base-h' );
$return .= <<<IPSCONTENT
' min="0" max="360" data-on-change="syncInputsWithPseudo">
									<input type='range' id='light__i-base-h' name='light__i-base-h' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base-h' );
$return .= <<<IPSCONTENT
' min="0" max="360" data-on-input="changingRangeInput" data-on-change="settingHasBeenChanged">
								</theme-setting>
								<theme-setting data-setting='light__i-base-c' data-type='number'>
									<label for='light__i-base-c__num'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_chroma', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
									<input type='number' inputmode='numeric' data-range-output='light__i-base-c' id='light__i-base-c__num' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base-c' );
$return .= <<<IPSCONTENT
' min="0" max="100" data-on-change="syncInputsWithPseudo">
									<input type='range' id='light__i-base-c' name='light__i-base-c' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base-c' );
$return .= <<<IPSCONTENT
' min="0" max="100" data-on-input="changingRangeInput" data-on-change="settingHasBeenChanged">
								</theme-setting>
								<theme-setting data-setting='light__i-base-l' data-type='number'>
									<label for='light__i-base-l__num'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_lightness', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
									<input type='number' inputmode='numeric' data-range-output='light__i-base-l' id='light__i-base-l__num' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base-l' );
$return .= <<<IPSCONTENT
' min="0" max="100" data-on-change="syncInputsWithPseudo">
									<input type='range' id='light__i-base-l' name='light__i-base-l' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'light__i-base-l' );
$return .= <<<IPSCONTENT
' min="0" max="100" data-on-input="changingRangeInput" data-on-change="settingHasBeenChanged">
								</theme-setting>

								<theme-setting data-setting='dark__i-base-h' data-type='number'>
									<label for='dark__i-base-h__num'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_hue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
									<input type='number' inputmode='numeric' data-range-output='dark__i-base-h' id='dark__i-base-h__num' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base-h' );
$return .= <<<IPSCONTENT
' min="0" max="360" data-on-change="syncInputsWithPseudo">
									<input type='range' id='dark__i-base-h' name='dark__i-base-h' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base-h' );
$return .= <<<IPSCONTENT
' min="0" max="360" data-on-input="changingRangeInput" data-on-change="settingHasBeenChanged">
								</theme-setting>
								<theme-setting data-setting='dark__i-base-c' data-type='number'>
									<label for='dark__i-base-c__num'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_chroma', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
									<input type='number' inputmode='numeric' data-range-output='dark__i-base-c' id='dark__i-base-c__num' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base-c' );
$return .= <<<IPSCONTENT
' min="0" max="100" data-on-change="syncInputsWithPseudo">
									<input type='range' id='dark__i-base-c' name='dark__i-base-c' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base-c' );
$return .= <<<IPSCONTENT
' min="0" max="100" data-on-input="changingRangeInput" data-on-change="settingHasBeenChanged">
								</theme-setting>
								<theme-setting data-setting='dark__i-base-l' data-type='number'>
									<label for='dark__i-base-l__num'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_lightness', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
									<input type='number' inputmode='numeric' data-range-output='dark__i-base-l' id='dark__i-base-l__num' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base-l' );
$return .= <<<IPSCONTENT
' min="0" max="100" data-on-change="syncInputsWithPseudo">
									<input type='range' id='dark__i-base-l' name='dark__i-base-l' value='
IPSCONTENT;

$return .= \IPS\Theme::i()->getParsedCssVariableFromKey( 'dark__i-base-l' );
$return .= <<<IPSCONTENT
' min="0" max="100" data-on-input="changingRangeInput" data-on-change="settingHasBeenChanged">
								</theme-setting>

							</editor-tab-content>
						</div>				
					</editor-panel>

					<!-- Other panels are inserted here dynamically -->
					
IPSCONTENT;

foreach ( \IPS\Theme\Editor\Category::themeEditorCategories() as $category ):
$return .= <<<IPSCONTENT

					{$category->editorHtml()}
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


				</editor-panels>

				<!-- Custom CSS -->
				<dialog class="theme-editor__dialog" id='dialog__customCSS'>
					<header>
						<div>
							<h4>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_custom_css', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
						</div>
						<button type='button' data-revert-custom-css hidden data-on-click="revertCustomCSS">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M75 75L41 41C25.9 25.9 0 36.6 0 57.9L0 168c0 13.3 10.7 24 24 24l110.1 0c21.4 0 32.1-25.9 17-41l-30.8-30.8C155 85.5 203 64 256 64c106 0 192 86 192 192s-86 192-192 192c-40.8 0-78.6-12.7-109.7-34.4c-14.5-10.1-34.4-6.6-44.6 7.9s-6.6 34.4 7.9 44.6C151.2 495 201.7 512 256 512c141.4 0 256-114.6 256-256S397.4 0 256 0C185.3 0 121.3 28.7 75 75zm181 53c-13.3 0-24 10.7-24 24l0 104c0 6.4 2.5 12.5 7 17l72 72c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-65-65 0-94.1c0-13.3-10.7-24-24-24z"/></svg>
							<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_custom_css_revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</button>
						<button type='button' aria-controls="dialog__customCSS" aria-expanded="false" data-ipscontrols>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
							<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_custom_css_done', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</button>
					</header>
                    <ips-code-editor>
			    		<textarea id="customCSS-saved">
IPSCONTENT;

$return .= \IPS\Theme::i()->custom_css;
$return .= <<<IPSCONTENT
</textarea>
			    		<textarea data-ipscodebox data-ipscodebox-allowed-languages="[&quot;ipscss&quot;]" class='theme-editor__customCSS' wrap="off" placeholder='/* Enter custom CSS here */' name="set__customCSS" id="customCSS" spellcheck="false" autocorrect="off" autocapitalize="off" translate="no" aria-label="Custom CSS">
IPSCONTENT;

$return .= \IPS\Theme::i()->custom_css;
$return .= <<<IPSCONTENT
</textarea>
			    	</ips-code-editor>
                    <div id="customCSS-warning" hidden>
                        <i class="fa-solid fa-file-circle-exclamation"></i>
                        <div id="customCSS-warning-message" class='ipsBox'>
                            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_custom_css_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                        </div>
                    </div>
                </dialog>

				<!-- Close confirmation -->
				<dialog class="theme-editor__dialog" id="closeConfirmationDialog" style='width:800px'>
					<header>
						<div>
							<h4>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_unsaved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
							<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_unsaved_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						</div>
					</header>
					<footer>
						<div>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=themeeditor&do=close", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--negative"><i class="fa-solid fa-trash-can"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_nosave', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
						<div>
							<button type="button" class="ipsButton ipsButton--inherit" aria-controls="closeConfirmationDialog" aria-expanded="false" data-ipscontrols><i class="fa-solid fa-paint-roller"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
						</div>
					</footer>
				</dialog>

			</form>
			
		</theme-editor>

		<button type="button" aria-controls="closeConfirmationDialog" aria-expanded="false" data-ipscontrols id="toggleCloseConfirmationDialog" hidden>Toggle confirmation window</button>

		<div class="sr-only" data-controller="core.front.core.colorScheme">
			<button type="button" data-ips-prefers-color-scheme="light">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_light', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			<button type="button" data-ips-prefers-color-scheme="dark">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_dark', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			<button type="button" data-ips-prefers-color-scheme="system">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editor_system', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</ul>		

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT

	</body>
</html>
IPSCONTENT;

		return $return;
}}