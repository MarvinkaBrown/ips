<?php
namespace IPS\Theme;
class class_core_admin_applications extends \IPS\Theme\Template
{	function applicationWrapper( $tree, $lang ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox i-margin-top_2' data-ips-template="applicationWrapper">
	<div class='ipsBox__header'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	{$tree}
</div>
IPSCONTENT;

		return $return;
}

	function appRowAdditional( $app ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsTree_row_cells">
	<span class="ipsTree_row_cell">
		
IPSCONTENT;

if ( \in_array( $app->directory, \IPS\IPS::$ipsApps ) ):
$return .= <<<IPSCONTENT

			<span class="i-color_positive"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invision', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<span class="i-color_issue"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</span>
</div>
IPSCONTENT;

		return $return;
}

	function appRowDescription( $application ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $application->_disabledMessage ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $application->_disabledMessage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\in_array( $application->directory, \IPS\IPS::$ipsApps ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $application->website ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$title = $application->author ?: $application->website();
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->basicUrl( $application->website(), TRUE, $title, false, TRUE, TRUE );
$return .= <<<IPSCONTENT


IPSCONTENT;

elseif ( $application->author ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$sprintf = array($application->author); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
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

	function appRowTitle( $app ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="i-color_hard i-font-weight_600">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> &middot; <span class='i-font-size_-1 i-font-weight_normal i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function codeHookEditor( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--code-hooks" action="
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

	
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

				<div class="i-padding_3 i-background_2">
					<p class='ipsMessage ipsMessage--error'>
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	<section class='i-background_2 i-padding_2' id='elCodeHookEditor' data-controller='core.admin.system.codeHook'>
		<div class="ipsColumns">
			<div class="ipsColumns__secondary i-basis_280">
				
IPSCONTENT;

$return .= array_pop( $sidebar );
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsColumns__primary'>
				
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

						{$input->html()}
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</div>

		<div class="i-background_2 i-text-align_center i-margin-top_2">
			
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

				{$button}
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	</section>
</form>
IPSCONTENT;

		return $return;
}

	function codeHookSidebar( $data ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="cHookEditor_sidebar ipsScrollbar ipsSideMenu">
		
IPSCONTENT;

foreach ( $data as $className => $constructs ):
$return .= <<<IPSCONTENT

			<h3 class='i-background_3 i-padding_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $className, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
			<br>
			
IPSCONTENT;

if ( isset( $constructs['properties'] ) ):
$return .= <<<IPSCONTENT

				<h4 class='ipsSideMenu_title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'plugin_hook_properties', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				<ul class="ipsSideMenu_list">
					
IPSCONTENT;

foreach ( $constructs['properties'] as $property ):
$return .= <<<IPSCONTENT

						<li class="ipsSideMenu_item" data-signature='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $property->signature, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-codeToInject='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $property->codeToInject, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							<a href='#'>
							
IPSCONTENT;

if ( $property->isPublic() ):
$return .= <<<IPSCONTENT

								<i class="fa-regular fa-circle" title="public"></i>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="fa-solid fa-circle" title="protected"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $property->isStatic() ):
$return .= <<<IPSCONTENT

								static
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							$
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $property->getName(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</a>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $constructs['methods'] ) ):
$return .= <<<IPSCONTENT

				<h4 class='ipsSideMenu_title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'plugin_hook_methods', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				<ul class="ipsSideMenu_list">
					
IPSCONTENT;

foreach ( $constructs['methods'] as $method ):
$return .= <<<IPSCONTENT

						<li class="ipsSideMenu_item" data-signature='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->signature, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-codeToInject='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->codeToInject, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							<a href='#'>
							
IPSCONTENT;

if ( $method->isPublic() ):
$return .= <<<IPSCONTENT

								<i class="fa-regular fa-circle" title="public"></i>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="fa-solid fa-circle" title="protected"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $method->isFinal() ):
$return .= <<<IPSCONTENT

								final
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $method->isStatic() ):
$return .= <<<IPSCONTENT

								static
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $method->isAbstract() ):
$return .= <<<IPSCONTENT

								abstract
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !$method->getNumberOfParameters() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->getName(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
()
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( !$method->getNumberOfRequiredParameters() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->getName(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
( 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->getNumberOfParameters(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 )
								
IPSCONTENT;

elseif ( $method->getNumberOfParameters() != $method->getNumberOfRequiredParameters() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->getName(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
( 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->getNumberOfRequiredParameters(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 [, 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $method->getNumberOfParameters() - $method->getNumberOfRequiredParameters(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ] )
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->getName(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
( [ 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->getNumberOfParameters(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ] )
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
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

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function details( $application, $lastUpgrade ) {
		$return = '';
		$return .= <<<IPSCONTENT

<table class='ipsTable'>
	<tr>
		<td class="field_title">
			<strong class="title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'app_details_app_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		</td>
		<td class="field_field">
			
IPSCONTENT;

$val = "__app_{$application->directory}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</td>
	</tr>
	<tr>
		<td class="field_title">
			<strong class="title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'app_details_app_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		</td>
		<td class="field_field">
			
IPSCONTENT;

$sprintf = array($application->version, $application->long_version); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'app_version_string', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		</td>
	</tr>
	<tr>
		<td class="field_title">
			<strong class="title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'app_details_app_author', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		</td>
		<td class="field_field">
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $application->author, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $application->website ):
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $application->website, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square-square" title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $application->website, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</td>
	</tr>
	<tr>
		<td class="field_title">
			<strong class="title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'app_details_app_installed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		</td>
		<td class="field_field">
			
IPSCONTENT;

$val = ( $application->added instanceof \IPS\DateTime ) ? $application->added : \IPS\DateTime::ts( $application->added );$return .= $val->html();
$return .= <<<IPSCONTENT

		</td>
	</tr>
	
IPSCONTENT;

if ( $lastUpgrade !== NULL ):
$return .= <<<IPSCONTENT

	<tr>
		<td class="field_title">
			<strong class="title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'app_details_app_updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		</td>
		<td class="field_field">
			
IPSCONTENT;

$val = ( $lastUpgrade instanceof \IPS\DateTime ) ? $lastUpgrade : \IPS\DateTime::ts( $lastUpgrade );$return .= $val->html();
$return .= <<<IPSCONTENT

		</td>
	</tr>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</table>

IPSCONTENT;

		return $return;
}

	function enhancements( $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='acpEnhancements'>
	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enhancements_ips', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<br>

	<ul class='ipsGrid i-basis_320 i-margin-bottom_4'>
		
IPSCONTENT;

foreach ( $rows[1] as $key => $data ):
$return .= <<<IPSCONTENT

			<li class='ipsBox acpEnhancement 
IPSCONTENT;

if ( $data['enabled'] == 1 ):
$return .= <<<IPSCONTENT
acpEnhancement_enabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				<div class='acpEnhancement__title'>
					<h3>
IPSCONTENT;

$val = "{$data['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					
IPSCONTENT;

if ( $data['enabled'] == 1 ):
$return .= <<<IPSCONTENT

						<span class='ipsBadge ipsBadge--icon ipsBadge--positive' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-check'></i></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $data['icon'] != "" ):
$return .= <<<IPSCONTENT
<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "enhancements/{$data['icon']}", "core", 'admin', false );
$return .= <<<IPSCONTENT
' class='acpEnhancement_logo' alt="" loading="lazy">
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( $data['description'] ):
$return .= <<<IPSCONTENT

					<div class='ipsRichText'>
						<p>
IPSCONTENT;

$val = "{$data['description']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<ul class='acpEnhancement_buttons ipsButtons ipsButtons--fill'>
					<li>
						
IPSCONTENT;

if ( $data['enabled'] == 1 ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=enhancements&do=enableToggle&id={$key}&status=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit' data-keyAction='t'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=enhancements&do=enableToggle&id={$key}&status=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' data-keyAction='t'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
					
IPSCONTENT;

if ( $data['config'] && $data['enabled'] == 1 ):
$return .= <<<IPSCONTENT

						<li>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=enhancements&do=edit&id={$key}&_new=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enhancements_configure', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>

	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enhancements_thirdparty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<br>

	<ul class='ipsGrid i-basis_320'>
		
IPSCONTENT;

foreach ( $rows[0] as $key => $data ):
$return .= <<<IPSCONTENT

			<li class='ipsBox acpEnhancement 
IPSCONTENT;

if ( $data['enabled'] == 1 ):
$return .= <<<IPSCONTENT
acpEnhancement_enabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				<div class='acpEnhancement__title'>
					<h3>
IPSCONTENT;

$val = "{$data['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					
IPSCONTENT;

if ( $data['enabled'] == 1 ):
$return .= <<<IPSCONTENT

						<span class='ipsBadge ipsBadge--icon ipsBadge--positive' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-check'></i></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $data['icon']  ):
$return .= <<<IPSCONTENT
<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "enhancements/{$data['icon']}", "{$data['app']}", 'admin', false );
$return .= <<<IPSCONTENT
' class='acpEnhancement_logo' alt="" loading="lazy">
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( $data['description'] ):
$return .= <<<IPSCONTENT

					<div class='ipsRichText'>
						<p>
IPSCONTENT;

$val = "{$data['description']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<ul class='acpEnhancement_buttons ipsButtons ipsButtons--fill'>
					<li>
						
IPSCONTENT;

if ( $data['enabled'] == 1 ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=enhancements&do=enableToggle&id={$key}&status=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=enhancements&do=enableToggle&id={$key}&status=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
					
IPSCONTENT;

if ( $data['config'] && $data['enabled'] == 1 ):
$return .= <<<IPSCONTENT

						<li>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=enhancements&do=edit&id={$key}&_new=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enhancements_configure', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function enhancementsGoogleMapsApi( $k ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="i-padding_3" id="googleApi_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/googlemaps_{$k}" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="ipsUserPhoto ipsUserPhoto--tiny">
		<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "enhancements/google_maps/{$k}.png", "core", 'admin', false );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	</a>
	<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/googlemaps_{$k}" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
		
IPSCONTENT;

$val = "googlemaps_api_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</a>
</li>
IPSCONTENT;

		return $return;
}

	function enhancementsGoogleMapsKeyRestrictions( $public, $websiteUrl, $data ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="i-padding_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_create_restrictions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
<li class="ipsFieldRow ipsFieldRow_yesNo">
	<div class="ipsFieldRow__label">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
	<div class="ipsFieldRow__content">
		<div data-ipsCopy>
			<code class="prettyprint lang-sql" data-role="copyTarget">
IPSCONTENT;

if ( $public ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_public', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_secret_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</code>
			<button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny ipsHide" data-role="copyButton" data-clipboard-text="
IPSCONTENT;

if ( $public ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_public', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_secret_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>	
	</div>
</li>
<li class="ipsFieldRow ipsFieldRow_yesNo">
	<div class="ipsFieldRow__label">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_app_restrict', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
	<div class="ipsFieldRow__content">
		
IPSCONTENT;

if ( $public ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_app_restrict_public', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\CIC or !isset( $_SERVER['SERVER_ADDR'] ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_app_restrict_secret_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$sprintf = array($_SERVER['SERVER_ADDR']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_app_restrict_secret_ip', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>

IPSCONTENT;

if ( $public ):
$return .= <<<IPSCONTENT

	<li class="ipsFieldRow ipsFieldRow_yesNo">
		<div class="ipsFieldRow__label">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_web_restrict', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsFieldRow__content">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_web_restrict_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			<div class="i-margin-top_2" data-ipsCopy>
				<code class="prettyprint lang-sql" data-role="copyTarget">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $websiteUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
				<button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny ipsHide" data-role="copyButton" data-clipboard-text="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $websiteUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</div>
	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<li class="ipsFieldRow ipsFieldRow_yesNo">
	<div class="ipsFieldRow__label">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_api_restrict', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
	<div class="ipsFieldRow__content">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_maps_api_key_api_restrict_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		<ul>
			
IPSCONTENT;

if ( $public ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $data['googlemaps'] ):
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'googlemaps_api_jsapi', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
					<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'googlemaps_api_staticapi', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $data['googleplacesautocomplete'] ):
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'googlemaps_api_places', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'googlemaps_api_geocodeapi', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function enhancementsLabel( $langKey ) {
		$return = '';
		$return .= <<<IPSCONTENT

<strong>
IPSCONTENT;

$val = "{$langKey}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
<span class="i-color_soft">
IPSCONTENT;

$val = "{$langKey}_desc"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function menuManagerHeader(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsMessage ipsMessage--warning i-margin-bottom_2 i-font-weight_600'>
    <div class="i-flex i-justify-content_space-between i-align-items_center i-flex-wrap_wrap i-gap_1">
        <div class="i-flex_91">
            <i class="fa-solid fa-triangle-exclamation ipsMessage__icon"></i>
            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'menu_manager_publish_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

        </div>
        <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=menu&do=publish" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small' data-confirm> <i class="fa-solid fa-circle-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'menu_manager_publish', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
    </div>
</div>
IPSCONTENT;

		return $return;
}

	function menuPreviewWrapper( $html, $title=NULL ) {
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

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeMeta(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT

	</head>
	<body class="ipsApp i-padding_3 ipsLayout_container" id='elMenuManagerPreview_body'>
		<div id="ipsLayout_header">
			{$html}
		</div>
		<main>
			<div id="ipsLayout_contentArea">
				<div id="ipsLayout_contentWrapper">					
					<div id="ipsLayout_mainArea">
						<p class='i-text-align_center i-opacity_6 i-margin-top_4'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'menu_manager_preview_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</p>
					</div>
				</div>
			</div>
		</main>
		
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

	function schemaConflict( $name, $diff ) {
		$return = '';
		$return .= <<<IPSCONTENT


<p class="ipsMessage ipsMessage--error">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_conflict_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

<div class="ipsBox">
	<div class="acpBlock_title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_conflict_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	<table class="ipsTable diff">
		<tr>
			<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_conflict_schema', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
			<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_conflict_local', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
		</tr>
	</table>
	{$diff}
	<div class="i-background_2 i-padding_3">
		<div class='ipsSpanGrid'>
			<div class='ipsSpanGrid__6'>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=developer&controller=schema&appKey=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->appKey ) ? htmlspecialchars( \IPS\Widget\Request::i()->appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT
&do=resolveSchemaConflicts&local=0&_name=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&csrfKey=
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' data-delete-confirm="">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_conflict_choose', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
			<div class='ipsSpanGrid__6'>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=developer&controller=schema&appKey=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->appKey ) ? htmlspecialchars( \IPS\Widget\Request::i()->appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT
&do=resolveSchemaConflicts&local=1&_name=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&csrfKey=
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_conflict_choose', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function versionFormField(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsSpanGrid'>
	<div class='ipsSpanGrid__6'>
		<input type='text' name='app_versions[0]' id='app_version_human' required aria-required='true' placeholder="1.0.0" size="10" class="ipsInput ipsInput--text">
		<div class='ipsFieldRow__desc'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_app_version_ph', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</div>
	<div class='ipsSpanGrid__6'>
		<input type='text' name='app_versions[1]' id='app_version_long' required aria-required='true' placeholder="100000" size="8" maxlength="8" class="ipsInput ipsInput--text">
		<div class='ipsFieldRow__desc'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_app_versionl_ph', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</div>
</div>

IPSCONTENT;

		return $return;
}}