<?php
namespace IPS\Theme;
class class_cms_admin_blocks extends \IPS\Theme\Template
{	function embedCode( $block, $embedKey ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="ipsMessage ipsMessage--info">
    	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
	<div data-controller='cms.admin.pages.embed'>
		<div class="i-margin-top_4">
			<div class='ipsTitle ipsTitle--h4'>1. 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_1_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			<p class="i-margin-block_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_1_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<div data-ipsCopy>
				<label class="ipsInputLabel i-font-weight_500 i-margin-top_3 i-margin-bottom_2"><input type='checkbox' class="ipsInput" id='elEmbedInherit' checked><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_1_toggle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></label>
				<textarea rows='2' spellcheck='false' id="embed_block" class='ipsInput ipsInput--fluid i-font-family_monospace' data-role='blockCode' readonly>&lt;div id='block_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $embedKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-blockID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $block->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsExternalWidget' data-inheritStyle='true'&gt;&lt;/div&gt;</textarea>
				<div class="i-padding-block_2">
					<button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="copyButton" data-clipboard-target="#embed_block">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</div>						
			</div>
		</div>		
		<div class="i-margin-top_4">
			<div class='ipsTitle ipsTitle--h4'>2. 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_2_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			<p class="i-margin-block_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'embed_2_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<div data-ipsCopy>
				<textarea rows='2' spellcheck='false' id="embed_loader" class='ipsInput ipsInput--fluid i-font-family_monospace' readonly>&lt;script type='text/javascript' src='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "applications/cms/interface/external/external.js", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' id='ipsWidgetLoader'&gt;&lt;/script&gt;</textarea>
				<div class="i-padding-block_2">
					<button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="copyButton" data-clipboard-target="#embed_loader">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</div>
			</div>				
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function pluginSelector( $plugins, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $formClass='', $attributes=array(), $sidebar='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox'>
	<form accept-charset='utf-8' action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post">
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
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div class='i-padding_3 i-background_dark'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_block_plugin_select', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		<div class='acpFormTabContent'>
			<div class="ipsTabs__panel">
				<ul class='ipsForm ipsForm--horizontal ipsForm--plugin-selector'>
					
IPSCONTENT;

foreach ( $plugins as $app => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $data as $key => $obj ):
$return .= <<<IPSCONTENT

							<li class='ipsFieldRow'>
								<div class='ipsFieldRow__label'>
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $obj->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									<span class='ipsBadge 
IPSCONTENT;

if ( $obj->type === 'feed' ):
$return .= <<<IPSCONTENT
ipsBadge--style2
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsBadge--style7
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "content_block_type_{$obj->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</div>
								<div class='ipsFieldRow__content'>
									<input id='elField_block_plugin' type="radio" role="radio" name="block_plugin" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $obj->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									<div class='ipsFieldRow__desc'>
										{$obj->description}
									</div>
								</div>
							</li>
							
IPSCONTENT;

if ( $obj->type === 'feed' ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

foreach ( $obj->contentTypes as $key => $lang  ):
$return .= <<<IPSCONTENT

									<li class='ipsFieldRow'>
										<div class='ipsFieldRow__label'>
											&nbsp;
										</div>
										<div class='ipsFieldRow__content'>
											<input id='elField_block_plugin' type="radio" role="radio" name="contentType_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
__
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $obj->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

											<div class='ipsFieldRow__desc'>
												@todo this should be hidden until parent radio selected
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

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</div>
			<div class="ipsSubmitRow">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->button( 'next', 'submit', null, 'ipsButton ipsButton--primary' );
$return .= <<<IPSCONTENT

			</div>
		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function previewTemplateLink( $plugin ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span data-role="viewTemplate" data-plugin="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $plugin, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--tiny'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_block_view_template', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function rowHtml( $block ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $block->category ):
$return .= <<<IPSCONTENT

    <span class="ipsBadge ipsBadge--new">
IPSCONTENT;

$sprintf = array($block->key); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_block_list_key', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
    
IPSCONTENT;

if ( $pages = $block->getPages() ):
$return .= <<<IPSCONTENT

        <div class="i-flex i-gap_1 i-flex-wrap_wrap i-margin-top_2 i-link-color_inherit">
            <span class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_block_list_pages', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
            <ul class="ipsList ipsList--csv">
                
IPSCONTENT;

foreach ( $pages as $page ):
$return .= <<<IPSCONTENT

                    <li class="i-color_hard"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $page->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank">
IPSCONTENT;

$val = "cms_page_{$page->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            </ul>
        </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}