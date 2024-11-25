<?php
namespace IPS\Theme;
class class_core_admin_support extends \IPS\Theme\Template
{	function admin( $name, $email, $password ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3'>
	<p>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $password, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</p>
	<p class='ipsMessage ipsMessage--warning'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy_admin_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>
</div>
IPSCONTENT;

		return $return;
}

	function contact( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--contact-support" action="
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
 data-ipsForm data-itemID='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.admin.support.contact'>
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

	
	<div class='i-padding_3'>
		<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--contact-support'>
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \is_object( $input )  ):
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

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
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

	function dashboard( $blocks, $chart, $guidesForm, $featuredGuides=array(), $bulletins=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsColumns' data-controller='core.admin.support.dashboard'>
	<div class='ipsColumns__primary'>
		<div class='ipsBox'>
			<p class='i-padding_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
		<br>
		<div class='ipsBox ipsHide' data-role="summary">
			<h2 class='ipsBox__header' data-role="summaryText"></h2>
			<div class='i-padding_2'>
				<button class='ipsButton ipsButton--inherit ipsButton--small' data-role="checkAgain">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_check_again_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</div>

		<div data-role="tableRows">
			<div class='ipsGrid i-basis_300 ipsGrid--system-health i-margin-top_2'>
				
IPSCONTENT;

foreach ( $blocks as $key => $block ):
$return .= <<<IPSCONTENT

					<div class='ipsBox' data-role='patchworkItem' data-blockid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						<div class='elBlockTitle i-padding_2 i-flex i-align-items_center i-justify-content_space-between i-border-bottom_3'>
							<div>
								<h2 class='i-font-size_2 i-font-weight_600 i-color_hard'>
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $block['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								</h2>
								
IPSCONTENT;

if ( !empty( $block['details'] ) ):
$return .= <<<IPSCONTENT

									<p class='i-font-weight_500 i-color_soft i-font-size_-1'>
										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $block['details'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									</p>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
							<div>
								<span class='i-color_warning ipsHide' data-iconType="critical"><i class="fa-solid fa-triangle-exclamation"></i></span>
								<span class='i-color_issue ipsHide' data-iconType="recommended"><i class="fa-solid fa-circle-info"></i></span>
							</div>
						</div>
						<div class='ipsLoading' data-role="supportBlock"></div>
					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</div>

		<div class='ipsBox i-margin-block_3'>
			<h2 class='ipsBox__header'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health__known_issues', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</h2>
			<div class='i-padding_3'>
				<div class='i-font-size_1'>
					
IPSCONTENT;

if ( \count( $bulletins ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $bulletins as $bulletin ):
$return .= <<<IPSCONTENT

							<div class="ipsMessage ipsMessage--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $bulletin['style'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								<h3 class='ipsMessage__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $bulletin['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
								{$bulletin['body']}
							</div>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health__no_known_issues', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>

		<div class='ipsBox i-margin-block_3'>
			<h2 class='ipsBox__header'>
				<div>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health__system_log_chart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			</h2>
			<div class='elHealthChart'>
				<div class='ipsButtons ipsButtons--fill i-padding_3'>
					<a class='ipsButton ipsButton--inherit' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=systemLogs", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_system_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<a class='ipsButton ipsButton--inherit' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=errorLogs", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_error_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<a class='ipsButton ipsButton--inherit' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=email&do=errorLog", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_email_error_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
				{$chart}
				<p class='i-font-size_1 i-color_soft i-padding_2'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_system_log_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</p>
			</div>
		</div>
	</div>

	<div class='ipsColumns__secondary i-basis_360'>
		<div class='ipsBox'>
			<h2 class='ipsBox__header'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health__tools_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</h2>
			<div class='i-padding_3'>
				<p class='i-font-size_1 i-margin-bottom_3'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_tools_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</p>

				<div class="ipsButtons">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=clearCaches" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--small ipsButton--inherit ipsButton--wide' data-role="clearCaches">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_clear_caches_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=thirdparty" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog class='ipsButton ipsButton--small ipsButton--inherit ipsButton--wide' >
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_customizations_overview', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=debug", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_debug_log_settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--inherit ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_debug_log_settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

if ( !\IPS\CIC ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=phpinfo", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' target='_blank' class='ipsButton ipsButton--small ipsButton--inherit ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_phpinfo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
		<div id='thirdPartyDialog'></div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function finishUtf8Mb4Conversion(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'utf8mb4_converter_conf_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<ol>
		<li>
IPSCONTENT;

$sprintf = array(\IPS\ROOT_PATH); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'utf8mb4_converter_conf_1', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</li>
		<li>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'utf8mb4_converter_conf_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			<pre class="ipsCode">'sql_utf8mb4' => false,</pre>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'utf8mb4_converter_conf_2b', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			<pre class="ipsCode">\$INFO['sql_utf8mb4'] = false;</pre>
		</li>
		<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'utf8mb4_converter_conf_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	</ol>
</div>
<div class="ipsSubmitRow">
	<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=utf8mb4", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'utf8mb4_converter_finish', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function fixConnection( $error ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_connection_fail', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<pre>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>

IPSCONTENT;

		return $return;
}

	function fixDatabase( $queries, $errors, $version ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_4 i-font-size_2">
	
IPSCONTENT;

if ( $errors === NULL ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_changes_to_make', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_changes_to_make_errors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $queries as $i => $query ):
$return .= <<<IPSCONTENT

		<pre class="prettyprint lang-sql cSupportQuery">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $query, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
		
IPSCONTENT;

if ( $errors !== NULL and isset( $errors[ $i ] ) ):
$return .= <<<IPSCONTENT

			<span class='i-color_warning'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $errors[ $i ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
<div class="ipsSubmitRow">
	
IPSCONTENT;

if ( $errors === NULL ):
$return .= <<<IPSCONTENT

		<form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=getBlock&block=mysql&fix=1&run=1&_upgradeVersion={$version}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post" style="display:inline">
			<input type="submit" class="ipsButton ipsButton--intermediate" value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_changes_run', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
		</form>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=getBlock&block=mysql&fix=1&_upgradeVersion={$version}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post" style="display:inline">
		<input type="submit" name="run" class="ipsButton ipsButton--secondary" value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_check_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function fixMd5( $modifiedFiles, $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="core.admin.support.md5">
	<div class="
IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="initialScreen">
		<div class='i-padding_4 i-font-size_2'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'md5_fail_help_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			<ul>
				
IPSCONTENT;

foreach ( $modifiedFiles as $file ):
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'md5_fail_help_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>
		<div class="i-background_2 i-padding_3 i-text-align_center">
			<div class="ipsJS_show">
				<a href="#" class="ipsButton ipsButton--primary" data-action="downloadDelta"><i class="fa-solid fa-cloud-arrow-down"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'md5_fail_download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/client_area" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="ipsButton ipsButton--secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_download_full', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
			<div class="ipsJS_hide">
				<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/client_area" );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary" target="_blank" rel="noopener">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_go_to_clientarea', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</div>
	<div data-role="downloadForm" 
IPSCONTENT;

if ( !$form->error ):
$return .= <<<IPSCONTENT
class="ipsHide"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		{$form}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function fixMd5Download( $downloadUrl ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'md5_fail_download_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<div class="i-text-align_center i-margin-bottom_1">
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $downloadUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--large"><i class="fa-solid fa-cloud-arrow-down"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'md5_fail_download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'md5_fail_download_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>
<div class="ipsSubmitRow">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_check_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>

IPSCONTENT;

		return $return;
}

	function fixRepeatLogs( $logs ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health__logs_repeats_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<i-data>
	<ul class="ipsData ipsData--table ipsData--compact i-margin-top_3">
		<li class='ipsData__item i-background_3'>
			<div class="ipsData__main">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health__repeat_logs_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			<div class='ipsData__stats'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health__repeat_logs_count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		</li>
		
IPSCONTENT;

foreach ( $logs as $message => $count ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<div class="ipsData__main">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $message, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
				<div class='ipsData__stats'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $count, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
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

	function guideSearchForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='elCoppaForm' class='i-text-align_center' data-ipsForm>
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

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Text ):
$return .= <<<IPSCONTENT

				<div class="acpSearch">
					<i class="fa-solid fa-magnifying-glass"></i>
					{$input->html()}
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

	&nbsp;&nbsp;<button type='submit' class='ipsButton ipsButton--large ipsButton--primary ipsHide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
</form>
IPSCONTENT;

		return $return;
}

	function healthcheck( $requirements ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $requirements['requirements'] as $k => $_requirements ):
$return .= <<<IPSCONTENT

	<div class="i-margin-bottom_1">
		<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$sprintf = array($k); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'requirements_header', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--requirements i-margin-top_1">
				
IPSCONTENT;

foreach ( $_requirements as $item ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<span class="
IPSCONTENT;

if ( $item['success'] ):
$return .= <<<IPSCONTENT
i-color_positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-color_warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							<i class="fa 
IPSCONTENT;

if ( $item['success'] ):
$return .= <<<IPSCONTENT
fa-check
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-times
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"></i>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['message'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</span>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $requirements['advice'] ) and \count( $requirements['advice'] ) ):
$return .= <<<IPSCONTENT

	<div class="i-margin-bottom_1">
		<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'advice', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--requirements-advice i-margin-top_1">
				
IPSCONTENT;

foreach ( $requirements['advice'] as $item ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<span class="">
							<i class="fa-solid fa-circle-info"></i>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</span>
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

		return $return;
}

	function hookedClasses( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function message( $message, $type ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->message( $message, $type );
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function methodIssues( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="i-padding_2">
    <div class="ipsMessage ipsMessage--warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'advise_removal_of_php8_incompatible_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
    <div class="acpOverflow_scroll_x" >
        {$table}
    </div>
</div>
IPSCONTENT;

		return $return;
}

	function patchAvailable( $updates ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_check_patches', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<div class="i-margin-top_1">
	<ul>
		
IPSCONTENT;

foreach ( $updates as $issue ):
$return .= <<<IPSCONTENT

			<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $issue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function queryFormTemplate( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 i-margin-bottom_1" action="
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

	<div class="ipsSpanGrid">
		<div class="ipsSpanGrid__10">
			
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
		<div class="ipsSpanGrid__2">
			<button type="submit" class="ipsButton ipsButton--primary ipsButton--large ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'run_query', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function recovery( $apps, $theme ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="i-background_2 i-padding_3">
	
IPSCONTENT;

if ( \count( $apps ) ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recovery_apps_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<ul>
		
IPSCONTENT;

foreach ( $apps as $app ):
$return .= <<<IPSCONTENT

			<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

if ( $theme ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recovery_theme_restored', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recovery_end_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<pre>define( 'RECOVERY_MODE', TRUE );</pre>
</div>
IPSCONTENT;

		return $return;
}

	function redis( $info, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_4 i-font-size_2'>
	
IPSCONTENT;

if ( isset( $info['used_memory_human'] ) AND ( isset( $info['total_system_memory'] ) OR isset( $info['maxmemory'] ) ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$percentage = ( $info['used_memory'] > 0 ) ? ceil( 100 / ( $info['maxmemory'] ?: $info['total_system_memory'] ) * $info['used_memory'] ) : 0;
$return .= <<<IPSCONTENT

		<div class="i-position_sticky-top i-padding_2 i-text-align_center">
			<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'redis_space_used_bar', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			<p class="i-color_soft"><span data-role="percentage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $percentage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>% (<span data-role="number">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $info['used_memory_human'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>/
IPSCONTENT;

if ( $info['maxmemory'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $info['maxmemory_human'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $info['total_system_memory_human'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
)</p>
			<progress class="ipsProgress" value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $percentage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' max='100'></progress>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<br>
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function redisEnabledBadge( $status ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $status === TRUE ):
$return .= <<<IPSCONTENT

<span class='ipsBadge ipsBadge--positive'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

<span class='ipsBadge ipsBadge--negative'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function supportBlockList( $listItems ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul>
	
IPSCONTENT;

if ( \count( $listItems ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$count = 1;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $listItems as $idx => $listItem ):
$return .= <<<IPSCONTENT

			<li class='
IPSCONTENT;

if ( $count < \count( $listItems ) ):
$return .= <<<IPSCONTENT
i-border-bottom_3
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

$count++;
$return .= <<<IPSCONTENT

				<div class='i-padding-block_2 i-padding-inline_2 i-flex i-align-items_center i-gap_1 
IPSCONTENT;

if ( $listItem['critical'] ):
$return .= <<<IPSCONTENT
i-color_negative i-font-weight_bold
IPSCONTENT;

elseif ( $listItem['advice'] ):
$return .= <<<IPSCONTENT
i-color_issue
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>

					
IPSCONTENT;

if ( $listItem['critical'] ):
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-triangle-exclamation i-flex_00"></i>
					
IPSCONTENT;

elseif ( $listItem['advice'] ):
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-circle-info i-flex_00"></i>
					
IPSCONTENT;

elseif ( $listItem['success'] ):
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-check i-flex_00"></i>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-circle-info i-flex_00"></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
					<p class='i-flex_11'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $listItem['detail'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
				
					
IPSCONTENT;

if ( !empty( $listItem['link'] ) ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $listItem['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !isset( $listItem['skipDialog'] ) ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

if ( !empty( $listItem['dialogTitle'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$listItem['dialogTitle']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'self_service', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsButton ipsButton--inherit ipsButton--small i-flex_00" 
IPSCONTENT;

if ( isset($listItem['dialogSize']) ):
$return .= <<<IPSCONTENT
data-ipsDialog-size="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $listItem['dialogSize'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

elseif ( !empty( $listItem['element'] ) ):
$return .= <<<IPSCONTENT

						<a href="#
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $listItem['element'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-content='#
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $listItem['element'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog-title="
IPSCONTENT;

if ( !empty( $listItem['dialogTitle'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$listItem['dialogTitle']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'self_service', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--small i-flex_00">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

if ( !empty( $listItem['learnmore'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_learn_more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

elseif ( !empty( $listItem['link'] ) OR !empty( $listItem['element'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_fix_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				</div>

				
IPSCONTENT;

if ( !empty( $listItem['element'] ) ):
$return .= <<<IPSCONTENT

					<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $listItem['element'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsHide'>
						<div class='i-padding_4 i-font-size_2'>
							{$listItem['body']}
						</div>
						
IPSCONTENT;

if ( empty( $listItem['learnmore'] ) ):
$return .= <<<IPSCONTENT

						<div class="i-background_2 i-padding_3 i-text-align_center">
							
IPSCONTENT;

if ( !empty( $listItem['button'] ) ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $listItem['button']['href'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $listItem['button']['css'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$listItem['button']['lang']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_check_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<li>
			<div class="i-padding-block_2 i-padding-inline_2 i-flex i-align-items_center i-color_soft">
				<i class="fa-solid fa-check i-margin-end_icon"></i>
				<p class='i-flex_11'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_no_issues', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function supportPatchWrapper( $updates ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href='#supportPatchAvailable' data-ipsDialog data-ipsDialog-content='#supportPatchAvailable' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'self_service', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health__patch_before_proceed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>

<div class='ipsHide' id='supportPatchAvailable'>
	<div class='i-padding_4 i-font-size_2'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "support", "core", 'admin' )->patchAvailable( $updates );
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsSubmitRow'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&_new=1&patch=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--inherit">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_apply_patch', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function table( $result, $pagination ) {
		$return = '';
		$return .= <<<IPSCONTENT

<br><br>
<div class="ipsOverflow">
	
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

		{$pagination}
		<br><br>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<table class="ipsTable ipsTable_zebra" >
		
IPSCONTENT;

$headers = FALSE;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

while ($row = $result->fetch_assoc() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !$headers ):
$return .= <<<IPSCONTENT

			<tr>
				
IPSCONTENT;

foreach ( $row as $k => $v ):
$return .= <<<IPSCONTENT

					<th>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</th>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</tr>
			
IPSCONTENT;

$headers = TRUE; endif;
$return .= <<<IPSCONTENT

			<tr>
				
IPSCONTENT;

foreach ( $row as $v ):
$return .= <<<IPSCONTENT

					<td>
						<div data-ipsTruncate>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					</td>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</tr>
		
IPSCONTENT;

endwhile;;
$return .= <<<IPSCONTENT

	</table>
	
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

		<br><br>
		{$pagination}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function thirdPartyDisabled( $disabledApps, $restoredDefaultTheme, $disabledAds ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_instruction', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
<i-data>
	<ul class="ipsData ipsData--table ipsData--third-party-disabled i-margin-top_1" data-role="disabledInformation">
		
IPSCONTENT;

if ( \count( $disabledApps ) ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<div class="ipsData__icon i-color_warning i-font-size_2"><i class="fa-solid fa-triangle-exclamation"></i></div>
				<div class="i-color_warning">
					<div data-role="disabledMessage">
IPSCONTENT;

$pluralize = array( \count( $disabledApps ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
					<div data-role="enabledMessage" class='ipsHide'>
IPSCONTENT;

$pluralize = array( \count( $disabledApps ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_apps_reenabled', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
					
IPSCONTENT;

$disabledApps = implode( ',', array_keys( $disabledApps ) );
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=thirdparty" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--tiny i-margin-top_1" data-action="enableThirdPartyPart" data-type="apps">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_apps_enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=applications", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="ipsButton ipsButton--negative ipsButton--tiny i-margin-top_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_apps_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			</li>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
				<div class="i-color_positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_no_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $restoredDefaultTheme ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<div class="ipsData__icon i-color_warning i-font-size_2"><i class="fa-solid fa-triangle-exclamation"></i></div>
				<div class="i-color_warning">
					<div data-role="disabledMessage">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_theme', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_theme_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					</div>
					<div data-role="enabledMessage" class="ipsHide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_theme_reenabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=thirdparty" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--tiny i-margin-top_1" data-action="enableThirdPartyPart" data-type="theme">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_theme_enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="ipsButton ipsButton--negative ipsButton--tiny i-margin-top_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_theme_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			</li>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
				<div class="i-color_positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_no_theme', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $disabledAds ) ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<div class="ipsData__icon i-color_warning i-font-size_2"><i class="fa-solid fa-triangle-exclamation"></i></div>
				<div class="i-color_warning">
					<div data-role="disabledMessage">
IPSCONTENT;

$pluralize = array( \count( $disabledAds ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_ads', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
					<div data-role="enabledMessage" class="ipsHide">
IPSCONTENT;

$pluralize = array( \count( $disabledAds ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_ads_reenabled', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
					
IPSCONTENT;

$disabledAds = implode( ',', $disabledAds );
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=thirdparty" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--tiny i-margin-top_1" data-action="enableThirdPartyPart" data-type="ads">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_ads_enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=promotion&controller=advertisements", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="ipsButton ipsButton--negative ipsButton--tiny i-margin-top_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_ads_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			</li>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
				<div class="i-color_positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_no_ads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function thirdPartyItems( $apps, $themes, $ads ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.admin.support.dashboard'>
	<div class='i-padding_3' data-role="customizationsWrapper">
		<i-data>
			<ul class="ipsData ipsData--table ipsData--third-party-items">
				
IPSCONTENT;

if ( \count( $apps ) ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<div class="ipsData__icon i-color_issue i-font-size_2"><i class="fa-solid fa-circle-info"></i></div>
						<div class="i-color_issue">
							
IPSCONTENT;

$pluralize = array( \count( $apps ), \count( $apps ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_apps_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
						<div class="i-color_positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_no_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $themes ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<div class="ipsData__icon i-color_issue i-font-size_2"><i class="fa-solid fa-circle-info"></i></div>
						<div class="i-color_issue">
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_theme_count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
						<div class="i-color_positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_no_theme', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $ads ) ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<div class="ipsData__icon i-color_issue i-font-size_2"><i class="fa-solid fa-circle-info"></i></div>
						<div class="i-color_issue">
							
IPSCONTENT;

$pluralize = array( \count( $ads ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_ads_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<div class="ipsData__icon i-color_positive i-font-size_2"><i class="fa-solid fa-check"></i></div>
						<div class="i-color_positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_no_ads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>
	<div class="ipsSubmitRow 
IPSCONTENT;

if ( !$themes AND ( !\count( $ads ) or \count( $apps ) ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
		<button href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=thirdparty&enable=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' data-role="disableCustomizations">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'health_disable_customizations', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		<button href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=support&do=thirdparty&enable=1&type=all" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-role="enableCustomizations" class="ipsButton ipsButton--primary ipsHide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'support_third_party_enable_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function toolboxResults( $form, $queries, $results ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$form}

IPSCONTENT;

foreach ( $queries as $k => $query ):
$return .= <<<IPSCONTENT

	{$results[ $k ]}

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}