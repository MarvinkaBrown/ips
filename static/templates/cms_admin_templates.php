<?php
namespace IPS\Theme;
class class_cms_admin_templates extends \IPS\Theme\Template
{	function addForm( $formHTML, $type ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	{$formHTML}
</div>
IPSCONTENT;

		return $return;
}

	function editor( $templates, $current ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsTabs__panel' id='ipsTabs_elTemplateEditor_tabbar_tab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' role="tabpanel" aria-labelledby="tab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="templatePanel" data-fileid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-location="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-group="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-itemID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-inherited-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->_inherited, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input data-role="group" type="text" name="group_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsJS_hide ipsInput ipsInput--text' value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input data-role="variables" type="text" name="variables_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text ipsInput--fullWidth" value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->params, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'skin_set_template_templatevars', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
	<input data-role="title" type="text" name="title_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsJS_hide ipsInput ipsInput--text ipsInput--fullWidth" value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_editor_fields_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
	<textarea name="description_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsJS_hide" data-role="description">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
	<textarea name="editor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id="editor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="editor">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->content, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
</div>
IPSCONTENT;

		return $return;
}

	function menu( $templates, $current, $request ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='cTemplateList' data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

foreach ( $templates as $type => $data ):
$return .= <<<IPSCONTENT

	<li data-node="top" data-location="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<details data-ipsdetails 
IPSCONTENT;

if ( $type == $request['t_location'] ):
$return .= <<<IPSCONTENT
open
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-node="top" data-location="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<summary>
IPSCONTENT;

$val = "content_template_type_{$type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</summary>
			<i-details-content>
				<ul>
					
IPSCONTENT;

if ( isset( $templates[ $type ] ) and \is_array( $templates[ $type ] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $templates[ $type ] as $group => $childTemplates ):
$return .= <<<IPSCONTENT

							<li data-group="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								
IPSCONTENT;

if ( $type == 'database' and ! \in_array( $group, array_values( \IPS\cms\Templates::$databaseDefaults ) ) ):
$return .= <<<IPSCONTENT

									<a class="ipsCms_templateOptions" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=databaseTemplateGroupOptions&group={$group}&t_location={$type}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" id="elTemplateEditor_options_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog><i class="fa-solid fa-gear"></i></a>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<details data-ipsdetails 
IPSCONTENT;

if ( $request['t_group'] == $group ):
$return .= <<<IPSCONTENT
open
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-group="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									<summary>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\cms\Templates::readableGroupName( $group ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</summary>
									<i-details-content>
										<ul>
											
IPSCONTENT;

foreach ( $childTemplates as $key => $child ):
$return .= <<<IPSCONTENT

												<li 
IPSCONTENT;

if ( $request['t_key'] == $child->key && !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
class="cTemplateList_activeNode"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
													<a data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-location="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&t_location={$child->location}&t_group={$child->group}&t_key={$child->key}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action="openFile" data-group="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-inherited-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->_inherited, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-itemID="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
												</li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									</i-details-content>
								</details>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</i-details-content>
		</details>
	</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function templateConflict( $conflicts, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $formClass='', $attributes=array(), $sidebar='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' data-controller='core.admin.templates.conflict' action="
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
 data-ipsForm class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
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
>
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


	<p class="ipsMessage ipsMessage--error">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_conflict_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
	<div class="ipsBox">
		
IPSCONTENT;

foreach ( $conflicts as $cid => $data ):
$return .= <<<IPSCONTENT

		<div class="i-padding_3 i-background_dark">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_group'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		<table class="ipsTable diff restrict_height">
			<tr>
				<th><span data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-conflict-name="old">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_conflict_old_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></th>
				<th><span data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-conflict-name="new">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_conflict_new_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </span></th>
			</tr>
		</table>
		<div data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		{$data['diff']}
		</div>
		<div class="i-background_2 i-padding_3">
			<div class='ipsFluid i-basis_300'>
				<div>
					<span class='ipsButton ipsButton--primary' data-conflict-name="old">
						
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $collection as $name => $input ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $name == 'conflict_' . $cid ):
$return .= <<<IPSCONTENT

									<input id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="old" checked="checked" />
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_conflict_use_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
				</div>
				<div>
					<span class='ipsButton ipsButton--primary' data-conflict-name="new">
						
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $collection as $name => $input ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $name == 'conflict_' . $cid ):
$return .= <<<IPSCONTENT

									<input id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'  type="radio" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="new" />
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_conflict_use_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
				</div>
			</div>
		</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	<div class="ipsSubmitRow">
		
IPSCONTENT;

$return .= implode( '', $actionButtons);
$return .= <<<IPSCONTENT

	</div>
</form>
IPSCONTENT;

		return $return;
}

	function templates( $templates, $current, $request ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=save", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="POST" id="editorForm">
	<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_location" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_key" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_id" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
	<div id='elTemplateEditor' class="ipsTemplateEditor ipsBox" data-controller='cms.admin.templates.main' data-normalURL="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ajaxURL="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=ajax", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
		<div class='ipsTemplateEditor__columns'>
			<div class='ipsTemplateEditor__listColumn' data-role="fileList" data-controller='cms.admin.templates.fileList'>
				<div class='cTemplateControls' id='elTemplateEditor_fileListControls'>
					<div class="cTemplateControls__input">
						<input type='text' data-role="templateSearch" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_templates', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
						<a href='#elTemplateFilterMenu_menu' id='elTemplateFilterMenu' class='cTemplateControls__filter' data-ipsMenu data-ipsMenu-selectable><i class="fa-solid fa-filter"></i> <i class='fa-solid fa-caret-down'></i></a>
					</div>
					<ul id='elTemplateFilterMenu_menu' role='menu' class='ipsMenu ipsMenu_selectable ipsMenu_auto ipsMenu_withStem ipsHide'>
						<li class='ipsMenu_item ipsMenu_itemChecked' data-ipsMenuValue='custom'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_theme_template_custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item ipsMenu_itemChecked' data-ipsMenuValue='unmodified'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_theme_template_default', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					</ul>
					<ul id='elTemplateEditor_newItemMenu_menu' role='menu' class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide'>
						<li class='ipsMenu_item'><a role='menuitem' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=addTemplate&type=block", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_block_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_block', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item'><a role='menuitem' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=addTemplate&type=page", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_page_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item'><a role='menuitem' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=addTemplate&type=database", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_database_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_database', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item'><a role='menuitem' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=addTemplate&type=css", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_css_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_css', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item'><a role='menuitem' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=addTemplate&type=js", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_js_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_add_template_js', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					</ul>
				</div>
				<i-tabs class='ipsTabs ipsTabs--stretch acpFormTabBar' id='elTemplateEditor_typeTabs' data-ipsTabBar data-ipsTabBar-contentArea='#elTemplateEditor_fileList' data-ipsTabBar-updateURL='false'>
					<div role="tablist">
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=manage&t_type=templates", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" aria-selected="
IPSCONTENT;

if ( $current->type == 'templates' ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-tabURL='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=ajax&do=loadMenu&t_type=templates", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-type='templates'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_html', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=manage&t_type=css", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" aria-selected="
IPSCONTENT;

if ( $current->type == 'css' ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-tabURL='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=ajax&do=loadMenu&t_type=css", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-type='css'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_css', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=manage&t_type=js", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" aria-selected="
IPSCONTENT;

if ( $current->type == 'js' ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-tabURL='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=ajax&do=loadMenu&t_type=js", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-type='js'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_js', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

				</i-tabs>
				<section id='elTemplateEditor_fileList'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "templates", "cms", 'admin' )->menu( $templates, $current, $request );
$return .= <<<IPSCONTENT

				</section>
				<ul id='elTemplateEditor_info'>
					<li class='cTemplateState_changed'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_templates_modified', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
					<li class='cTemplateState_custom'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_templates_custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
					<li class='cTemplateState_inherit'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_templates_inherit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
				</ul>
				<div id='elTemplateEditor_newButton'>
					<a href='#elTemplateEditor_newItemMenu_menu' id='elTemplateEditor_newItemMenu' class='ipsButton ipsButton--secondary ipsButton--small ipsButton--wide' data-ipsMenu data-ipsMenu-above>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp;<i class='fa-solid fa-caret-down'></i></a>
				</div>
			</div>
			<div class='ipsTemplateEditor__codeColumn'  data-controller='cms.admin.templates.fileEditor'>
				<div class='cTemplateControls'>
					<ul class='ipsTemplateEditor__editorToolbar' id='elTemplateEditor_panelToolbar'>
						<li>
							<a href='#elTemplateEditor_preferences_menu' id='elTemplateEditor_preferences' class='ipsButton ipsButton--inherit ipsButton--small' data-ipsMenu data-ipsMenu-selectable><i class='fa-solid fa-gear'></i> <i class='fa-solid fa-caret-down'></i></a>
							<ul id='elTemplateEditor_preferences_menu' class='ipsMenu ipsMenu_selectable ipsMenu_auto ipsMenu_withStem ipsHide'>
								<li class='ipsMenu_item' data-ipsMenuValue='wrap'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_wrap', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								<li class='ipsMenu_item' data-ipsMenuValue='lines'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_show_line', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								<li class='ipsMenu_item' data-ipsMenuValue='diff'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_show_original', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							</ul>
						</li>
						<li>
							<a href='#' id='elTemplateEditor_variables' class='ipsButton ipsButton--inherit ipsButton--small' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_editor_fields_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-content="#elTemplateEditor_attributesDialog" data-ipsDialog-size='narrow'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_attributes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
						<li>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=delete&t_location={$current->location}&t_key={$current->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit ipsButton--small 
IPSCONTENT;

if ( $current->user_edited == 'original' or $current->user_edited === 0 ):
$return .= <<<IPSCONTENT
ipsButton--disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action="revert">
IPSCONTENT;

if ( $current->user_created == 1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
						</li>
						<li>
							<button type='submit' class='ipsButton ipsButton--primary ipsButton--small' data-action="save">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
						</li>					
						<li>
							<span data-role='loading' class='ipsHide'><i class='ipsLoadingIcon'></i>  &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</li>
					</ul>
				</div>
				<i-tabs id='elTemplateEditor_tabbar' class='ipsTabs acpFormTabBar' data-ipsTabBar data-ipsTabBar-contentArea='#elTemplateEditor_panels'>
					<div role='tablist'>
						<a href='#' class='ipsTabs__tab' id='tab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' role="tab" aria-controls="ipsTabs_elTemplateEditor_tabbar_tab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="true" data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-location='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-group="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <span data-action='closeTab'><i class='fa-solid fa-xmark'></i></span></a>
					</div>
				</i-tabs>
				<section data-role="templatePanelWrap" id='elTemplateEditor_panels' data-haseditor="
IPSCONTENT;

if ( $current->id ):
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

$return .= \IPS\Theme::i()->getTemplate( "templates", "cms", 'admin' )->editor( $templates, $current );
$return .= <<<IPSCONTENT

				</section>
			</div>
		</div>

		<div id='elTemplateEditor_attributesDialog' class='ipsHide'>
			<div data-controller='cms.admin.templates.variablesDialog'>
				<div class='i-padding_3'>
					<div class='ipsMessage ipsMessage--information'> 
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'template_variables_save_warning_msg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
					<p class='ipsType_textBlock' id='elTemplateEditor_attributes_title'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_editor_fields_params', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
					<input class='ipsInput ipsInput--text ipsInput--fullWidth' data-role='variables' placeholder="&#36;foo=''">
					<p class='ipsType_textBlock' id='elTemplateEditor_title_title'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_editor_fields_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
					<input class='ipsInput ipsInput--text ipsInput--fullWidth' data-role='title'>
					<p class='ipsType_textBlock' id='elTemplateEditor_group_title'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_editor_fields_container', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
					
IPSCONTENT;

foreach ( array('block','page', 'database') as $type  ):
$return .= <<<IPSCONTENT

						<select id="elContainer_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_hidden" class='ipsHide' name="group" data-role="group" data-container-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

foreach ( \IPS\cms\Templates::getGroups( $type ) as $groupName ):
$return .= <<<IPSCONTENT

							<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $groupName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\cms\Templates::readableGroupName( $groupName ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</select>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					<p class='ipsType_textBlock ipsHide'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_editor_fields_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
					<textarea class='ipsHide ipsInput ipsInput--text ipsInput--fullWidth' data-role='description'></textarea>
					<input type='hidden' name='_variables_fileid' value=''>
					<input type='hidden' name='_variables_location' value=''>
				</div>
				<div class='i-padding_3 i-background_3 i-text-align_end'>
					<input type='submit' class='ipsButton ipsButton--primary' value='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
				</div>
			</div>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function viewTemplate( $template ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_template_editor_fields_params', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</strong> <span class='i-font-family_monospace'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['template_params'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	<pre data-controller='core.global.editor.customtags' class='i-font-family_monospace ipsScrollbar ipsTemplate_box' data-control="codemirror" data-mode="htmlmixed">
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['template_content'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</pre>
</div>
IPSCONTENT;

		return $return;
}}