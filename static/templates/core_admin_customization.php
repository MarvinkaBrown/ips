<?php
namespace IPS\Theme;
class class_core_admin_customization extends \IPS\Theme\Template
{	function designerCoreForm( $theme, $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox" id='elTemplateEditor' data-controller='core.admin.customization.designerCore'>
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function designerModeToggle(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding-top_2">
	<a class="ipsButton ipsButton--inherit ipsButton--small" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=toggleDesignerMode", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

if ( ! \IPS\Settings::i()->theme_designer_mode ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_designer_mode_enable_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_designer_mode_disable_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
		<i class="fa-solid fa-code"></i> 
IPSCONTENT;

if ( ! \IPS\Settings::i()->theme_designer_mode ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_designer_mode_enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_designer_mode_disable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</a>
</div>
IPSCONTENT;

		return $return;
}

	function diff( $skinSet, $diff ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p class="ipsMessage ipsMessage--info">
IPSCONTENT;

$sprintf = array($skinSet->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_theme_set_diff_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
<div class="ipsBox">
	
IPSCONTENT;

foreach ( $diff['templates'] as $app => $appData ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $appData as $location => $locationData ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $locationData as $group => $groupData ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $groupData as $name => $template ):
$return .= <<<IPSCONTENT

					<div class="acpBlock_title">
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['template_app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['template_location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['template_group'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['template_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $template['added'] === true ):
$return .= <<<IPSCONTENT

						<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_theme_set_template_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $template['deleted'] === true ):
$return .= <<<IPSCONTENT

						<span class='ipsBadge ipsBadge--warning'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_theme_set_template_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					
IPSCONTENT;

if ( isset($template['oldHumanVersion']) AND isset($template['newHumanVersion']) ):
$return .= <<<IPSCONTENT

						<table class="ipsTable diff restrict_height">
						<tr>
							<th><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['oldHumanVersion'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></th>
							<th><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['newHumanVersion'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></th>
						</tr>
						</table>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div>
						{$template['diff']}
					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

foreach ( $diff['css'] as $app => $appData ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $appData as $location => $locationData ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $locationData as $group => $pathData ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $pathData as $name => $css ):
$return .= <<<IPSCONTENT

					<div class="acpBlock_title">
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css['css_app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css['css_location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css['css_path'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css['css_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $css['added'] === true ):
$return .= <<<IPSCONTENT

						<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_theme_set_css_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $css['deleted'] === true ):
$return .= <<<IPSCONTENT

						<span class='ipsBadge ipsBadge--warning'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_theme_set_css_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
					</div>
					<div>
						{$css['diff']}
					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function diffExportWrapper( $html ) {
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
">
	<head>
		<meta charset="utf-8">
		<title>Diff Export</title>
		<link href="http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet">
		<style type="text/css">
		/* ACP */
		html {
			background: #f9f9f9;
			/*background: #f7f7f7;*/
			/*background: #f9f9f9;*/
			min-height: 100%;
			position: relative;
		}
		
		body {
			font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
			font-size: 13px;
			line-height: 18px;
			color: #404040;	
			height: 100%;
		}
		
		a {
			color: #166693;
			text-decoration: none;
		}
		
			a:hover {
				color: #cd3816;
			}
		
		.i-background_dark {
			background: #394248;
		}
		
		/* BLOCKS */
		.acpBlock_title {
			padding: 15px;
			margin: 0;
			background: #f0f0f0;
			color: #151515;
			font-size: 16px;
			font-weight: normal;
		}
		
		/* Badges */
		.ipsBadge {
			height: 17px;
			line-height: 17px;
			padding: 0 8px;
			border-radius: 10px;
			font-size: 9px;
			text-transform: uppercase;
			font-weight: 500;
			display: inline-block;
			color: #fff;
			vertical-align: bottom;
			font-weight: bold;
			text-shadow: none;
		}
		
		.ipsBadge.ipsBadge--medium {
			height: 18px;
			line-height: 18px;
			font-size: 11px;
		} 
		
		.ipsBadge.ipsBadge--large {
			height: 23px;
			line-height: 23px;
			padding: 0 10px;
		}
		
		/* Styles */
		.ipsBadge--new, .ipsBadge--style1 {
			background: #323232;
		}
		
		.ipsBadge--style2 {
			background: #d42b39;
		}
		
		.ipsBadge--warning, .ipsBadge--style3 {
			background: #834250;
		}
		
		.ipsBadge--positive, .ipsBadge--style4 {
			background: #68a72f;
		}
		
		.ipsBadge--negative, .ipsBadge--style5 {
			background: #a72f35;
		}
		
		.ipsBadge--neutral, .ipsBadge--style6 {
			background: #b3b3b3;
		}
		
		.ipsBadge--intermediary, .ipsBadge--style7 {
			background: #cbb641;
		}

		/* Table */
		.ipsTable {
			width: 100%;
			border-collapse: collapse;
		}
		
		.ipsTable th {
			text-align: start;
			background: #f3f3f3;
			padding: 15px 10px;
			font-size: 13px;
		}
			
			.ipsTable th a {
				color: inherit;
			}
			
		.ipsTable th, .ipsTable td {
			vertical-align: middle;
		}
		
		.ipsTable td {
			padding: 7px;
			border-bottom: 1px solid rgba(0,0,0,0.01);
		}

		/* Diff */
		table.diff {
			width: 100%;
		}
		
		table.diff td, table.diff th {
			width: 50%;
			max-width: 500px;
			overflow-x: auto;
		}
		
		table.diff td {
			padding: 10px;
			vertical-align: top;
			white-space: pre;
			white-space: pre-wrap;
			font-family: monospace;
		}
		
		.diffDeleted {
			background:rgb(255,224,224);
		}
		
		.diffInserted {
			background:rgb(224,255,224);
		}
		
		/* Messages */
		.ipsMessage {
			padding: 15px;
			padding-inline-start: 45px;
			border-radius: 2px;
			position: relative;
			margin-bottom: 10px;
			color: #fff;
		}
		
			.ipsMessage:before {
				font-family: var(--i-font-awesome);
				-moz-osx-font-smoothing: grayscale;
				-webkit-font-smoothing: antialiased;
				text-rendering: auto;
				font-weight: 900;
				position: absolute;
				top: 15px;
				inset-inline-start: 15px;
				font-size: 20px;
			}
		
		.ipsMessage--error {
			background: #b52b38;
		}
		
			.ipsMessage--error:before {
				content: '\\f06a';
			}
		
		.ipsMessage--success {
			background: #53902f;
		}
		
			.ipsMessage--success:before {
				content: '\\f00c';
			}
		
		.ipsMessage--warning {
			background: #c48712;
		}
		
			.ipsMessage--warning:before {
				content: '\\f071';
			}
		
		.ipsMessage--info, .ipsMessage--information {
			background: #447a9a;
		}
		
			.ipsMessage--info:before, .ipsMessage--information:before {
				content: '\\f05a';
			}
		
		.ipsMessage_code {
			padding: 7px;
			display: inline-block;
			background: rgba(0,0,0,0.2);
			border-radius: 3px;
			float: right;
			margin-top: -7px;
			margin-inline-end: -7px;
			color: rgba(255,255,255,0.8);
		}
		</style>
	</head>
	<body>
	{$html}
	</div>
	</body>
</html>

IPSCONTENT;

		return $return;
}

	function email( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--email" action="
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
'>
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


	<div class='ipsBox'>
		<div class='i-padding_3'>
			<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emailtpl_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			<p>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emailtpl_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
		
			<hr class='ipsHr'>
		
			<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--email'>
				
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
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function emailFrame( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<iframe src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" style='width: 100%; height: 100%; min-height: 500px; border: 0' class='ipsLoading'></iframe>
IPSCONTENT;

		return $return;
}

	function emoticons( $sets ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="">
	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'editor', 'emoticons_add' ) ):
$return .= <<<IPSCONTENT

		<ul class="ipsButtons ipsButtons--main i-padding_2 i-background_2">
			<li>
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=editor&controller=emoticons&do=add", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--secondary ipsButton--small" data-ipsDialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticons_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
					<i class="fa-solid fa-plus-circle"></i> <span data-role="title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticons_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</a>
			</li>
		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<form accept-charset='utf-8' action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=editor&controller=emoticons&do=edit", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post" data-controller="core.admin.customization.emoticons" id="emoticonsManagement">
		<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<ul data-role="setList">
			
IPSCONTENT;

foreach ( $sets as $setKey => $emoticons ):
$return .= <<<IPSCONTENT

				<li data-emoticonSet="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					<div class='i-flex i-flex-wrap_wrap i-align-items_center i-gap_2 i-padding_2'>
						<h2 class='ipsTitle ipsTitle--h3 i-flex_11'>
							
IPSCONTENT;

$val = "{$setKey}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</h2>
						<ul class='ipsButtons'>
							<li>
								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=editor&controller=emoticons&do=editTitle&key=$setKey", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticons_edit_groupname', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticons_edit_groupname', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--small'><i class='fa-solid fa-pencil'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticons_edit_groupname', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'editor', 'emoticons_delete' ) ):
$return .= <<<IPSCONTENT

								<li>
									<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=editor&controller=emoticons&do=deleteSet&key=$setKey" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmsubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticons_delete_set_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticons_delete_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--small i-color_negative'><i class='fa-solid fa-xmark-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticons_delete_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					</div>
					<ul class='ipsGrid i-gap_2 i-padding_2 i-basis_180 i-background_2' data-role="emoticonsList" data-emoticonGroup="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

foreach ( $emoticons as $emo ):
$return .= <<<IPSCONTENT

							<li id="emo_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-emoticonID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
								<div class='cEmoticons_box'>
									<div class='i-padding_2 i-text-align_center' data-role='dragHandle'>
										
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'editor', 'emoticons_delete' ) ):
$return .= <<<IPSCONTENT

											<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=editor&controller=emoticons&do=delete&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-delete data-delete-warning="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticons_delete_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-deleterow="#emo_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">&times;</a>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

try{
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $emo['image_2x'] ):
$return .= <<<IPSCONTENT

												<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Emoticons", $emo['image'] )->url;
$return .= <<<IPSCONTENT
" srcset="
IPSCONTENT;

$return .= \IPS\File::get( "core_Emoticons", $emo['image_2x'] )->url;
$return .= <<<IPSCONTENT
 2x" alt='' class='ipsImage' 
IPSCONTENT;

if ( $emo['width'] AND $emo['width'] ):
$return .= <<<IPSCONTENT
 width='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['width'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' height='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['height'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Emoticons", $emo['image'] )->url;
$return .= <<<IPSCONTENT
" alt='' class='ipsImage'>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

} catch( \Exception $ex ){
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$sprintf = array($emo['image']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticon_invalid', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

 } 
$return .= <<<IPSCONTENT

									</div>						
									<div class='cEmoticons_input'>
										<input type="hidden" name="emo[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][set]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-emoticon-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
										<input value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['typed'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" name="emo[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $emo['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][name]" data-role="emoticonTyped" class="i-font-weight_500 i-color_hard">
										
IPSCONTENT;

if ( $emo['image_2x'] ):
$return .= <<<IPSCONTENT

											<span class='cEmoticons_hd i-font-size_-3 i-color_soft' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emoticon_hd', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip><i class='fa-solid fa-check'></i> HD</span>
										
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
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'editor', 'emoticons_edit' ) ):
$return .= <<<IPSCONTENT

			<div class="ipsSubmitRow">
				<button class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</form>
</div>
IPSCONTENT;

		return $return;
}

	function formLogo( $url, $type='img' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" 
IPSCONTENT;

if ( $type == 'img' ):
$return .= <<<IPSCONTENT
class="ipsThumb_large"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 />
IPSCONTENT;

		return $return;
}

	function groupLink( $id, $name ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=groups&id=$id", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

		return $return;
}

	function langDescription( $lang ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( mb_strtolower( (string) $lang->author_name ) != 'invision power services' and ( $lang->author_name != '' OR $lang->author_url != '' ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $lang->author_name != ''  ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$sprintf = array($lang->author_name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $lang->author_url ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->basicUrl( $lang->author_url, TRUE, $lang->author_url, TRUE, TRUE, TRUE );
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

	function langRowAdditional( $lang, $hasBeenCustomized ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsTree_row_cells">
	<span class="ipsTree_row_cell">
		
IPSCONTENT;

if ( $hasBeenCustomized ):
$return .= <<<IPSCONTENT

			<span class="i-color_soft"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_has_been_customized', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

elseif ( $lang->author_name == 'Invision Power Services, Inc.' or $lang->id == 1 ):
$return .= <<<IPSCONTENT

			<span class="i-color_positive"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invision', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

	function langRowTitle( $lang ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="i-color_hard i-font-weight_600">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> <span class='i-font-size_-1 i-font-weight_normal i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang->version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function langString( $value, $key, $lang, $js ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="core.admin.core.langString" data-saveURL='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=languages&controller=languages&do=translateWord&key={$key}&lang={$lang}&js={$js}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=languages&controller=languages&do=translateWord&key={$key}&lang={$lang}&js={$js}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" >
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function layoutPermissionBlurb( $groups ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3'>
	<div>
		<div>
			
IPSCONTENT;

if ( \count($groups) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$htmlsprintf = array( \IPS\Member::loggedIn()->language()->formatList($groups) );$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'layout_permission_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'layout_permission_none_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>

IPSCONTENT;

		return $return;
}

	function media( $media, $theme ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.admin.customization.media' data-theme-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $theme->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class='cMedia_manager ipsBox ipsPull'>
		<div class='cMedia_manager__side'>
			<div id='elMedia_sidebar'>
				<div data-role='mediaSidebar'>
					<div data-role='itemInformation'>
						<!-- <div class='cMedia_preview i-margin-bottom_3' data-role='itemPreview'></div> -->
						<div class=''>
							<h3 class='ipsTitle ipsTitle--h4' data-role='itemFilename'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_filename', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<input type='text' class='ipsInput ipsInput--text ipsInput--wide' value='' data-role='itemTag' readonly onclick="this.select()">
							<p class='i-font-size_-1 i-color_soft i-margin-top_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_tag_desc_core', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						</div>

						<ul class="cMedia_manager__fileInfo">
							<!-- <li>
								<h3 class='ipsTitle ipsTitle--h6'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_media_url', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<p class='i-font-size_1' data-role='itemUrl'></p>
								<p class='i-font-size_-1 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_url_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
							</li> -->
							<li>
								<h3 class='ipsTitle ipsTitle--h6'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_uploaded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<p class='i-color_soft' data-role='itemUploaded'></p>
							</li>
							<li>
								<h3 class='ipsTitle ipsTitle--h6'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_size', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<p class='i-color_soft' data-role='itemFilesize'></p>
							</li>
							<li data-role='itemDimensionsRow'>
								<h3 class='ipsTitle ipsTitle--h6'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_dims', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<p class='i-color_soft' data-role='itemDimensions'></p>
							</li>
						</ul>

						<ul class="i-margin-top_2 ipsButtons ipsButtons--fill">
							<li><a href='#' class='ipsButton ipsButton--small ipsButton--secondary' data-role='replaceFile' data-baseUrl="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=media&do=replace&set_id={$theme->id}&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-arrow-right-arrow-left"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replace_media_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							<li data-action='deleteSelected'><a href='#' class='ipsButton ipsButton--negative ipsButton--small'><i class="fa-solid fa-trash-can"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_resources_delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						</ul>
					</div>
					<div data-role='multipleItems'>
						<div class='i-padding_3 i-font-size_2 i-color_soft i-text-align_center' data-role='multipleItemsMessage'></div>
						<div data-action='deleteSelected'><a href='#' class='ipsButton ipsButton--negative ipsButton--small ipsButton--wide'><i class="fa-solid fa-trash-can"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_resources_delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>
					</div>
				</div>
			</div>			
		</div>
		<div class='cMedia_manager__main'>
			<div class='cMedia__managerToolbar'>
				<input type='search' class='ipsInput ipsInput--text' data-role='mediaSearch' id='elMedia_searchField' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_search_media', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
				<ul class='ipsButtons ipsButtons--fill ipsButtons--media i-flex_11 i-align-items_stretch'>
					<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=media&do=upload&set_id={$theme->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-remoteSubmit data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_add_media', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' data-role='uploadButton'><i class='fa-solid fa-cloud-arrow-up'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_add_media', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					<!-- <li class='ipsHide' data-action='deleteSelected'><a href='#' class='ipsButton ipsButton--negative ipsButton--small'><i class="fa-solid fa-trash-can"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_resources_delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li> -->
				</ul>
			</div>
			<div class='i-background_2' data-role="fileListing" id='elMedia_fileList' data-showing='root'>
				<style data-role="searchStyleTag"></style>
				<ul class='cMedia_manager__items'>
					
IPSCONTENT;

foreach ( $media as $id => $data ):
$return .= <<<IPSCONTENT

						{$data}
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function mediaFileListing( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li data-role='mediaItem' data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['resource_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-uploaded='
IPSCONTENT;

$val = ( $item['resource_added'] instanceof \IPS\DateTime ) ? $item['resource_added'] : \IPS\DateTime::ts( $item['resource_added'] );$return .= (string) $val->localeDate();
$return .= <<<IPSCONTENT
' data-path='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['resource_path'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-filename='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['resource_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-url='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
?_cb=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['resource_added'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-fileType='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['file_type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
    <div class='cMedia_item cMedia_itemImage'>
        <figure class='ipsThumb'>
            
IPSCONTENT;

if ( $item['file_type'] == 'image' ):
$return .= <<<IPSCONTENT

                <img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
?_cb=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['resource_added'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="" loading="lazy">
            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                <i></i>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </figure>
        <div class="cMedia_content">
            <div class='cMedia_filename'><p class='ipsTruncate_1'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['resource_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p></div>
            <!-- <div class='cMedia_meta'>
IPSCONTENT;

$val = ( $item['resource_added'] instanceof \IPS\DateTime ) ? $item['resource_added'] : \IPS\DateTime::ts( $item['resource_added'] );$return .= $val->html(FALSE);
$return .= <<<IPSCONTENT
</div> -->
        </div>
    </div>
</li>
IPSCONTENT;

		return $return;
}

	function previewTemplateLink(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span data-role="viewTemplate" class='ipsButton ipsButton--inherit ipsButton--tiny'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_template_view_template', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function resourceDisplay( $resource ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $resource['resource_filename'] )->url;
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" data-ipsLightbox>
IPSCONTENT;

if ( \in_array( mb_substr( $resource['resource_filename'], mb_strrpos( $resource['resource_filename'], '.' ) + 1 ), array_merge( array('svg'), \IPS\Image::supportedExtensions() ) ) ):
$return .= <<<IPSCONTENT
<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $resource['resource_filename'] )->url;
$return .= <<<IPSCONTENT
" class="ipsImage" />
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $resource['resource_filename'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function resourceName( $resource ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $resource['resource_filename'] )->url;
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $resource['resource_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function resourceTag( $tag ) {
		$return = '';
		$return .= <<<IPSCONTENT

<code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
IPSCONTENT;

		return $return;
}

	function templateConflict( $skinSet, $conflicts, $templates, $css, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $formClass='', $attributes=array(), $sidebar='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' data-controller='core.admin.templates.conflict' data-normalURL="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&id={$skinSet->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ajaxURL="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=ajax&id={$skinSet->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" action="
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

foreach ( $conflicts['template'] as $key => $data ):
$return .= <<<IPSCONTENT

		<div class="acpBlock_title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_path'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		<table class="ipsTable diff restrict_height">
			<tr>
				<th><span data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-conflict-name="old">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_conflict_old_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></th>
				<th><span data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		{$data['diff']}
		</div>
		<div class="i-background_2 i-padding_3">
			<div class='ipsSpanGrid'>
				<div class='ipsSpanGrid__6'>
					<span class='ipsButton ipsButton--primary' data-conflict-name="old">
						
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $collection as $name => $input ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $name == 'conflict_' . $data['conflict_id'] ):
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
				<div class='ipsSpanGrid__6'>
					<span class='ipsButton ipsButton--primary' data-conflict-name="new">
						
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $collection as $name => $input ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $name == 'conflict_' . $data['conflict_id'] ):
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

		
IPSCONTENT;

foreach ( $conflicts['css'] as $key => $data ):
$return .= <<<IPSCONTENT

		<div class="acpBlock_title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_path'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		<table class="ipsTable diff restrict_height">
			<tr>
				<th><span data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-conflict-name="old">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_conflict_old_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></th>
				<th><span data-conflict-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
$return .= \IPS\Theme\Template::htmlspecialchars( $data['conflict_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-padding_3">
			
IPSCONTENT;

if ( isset( $data['large'] ) and $data['large'] ):
$return .= <<<IPSCONTENT

				<div class="ipsMessage ipsMessage--info">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_confict_view_too_large', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $data['diff'] ) ):
$return .= <<<IPSCONTENT

				{$data['diff']}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="i-background_2 i-padding_3">
			<div class='ipsSpanGrid'>
				<div class='ipsSpanGrid__6'>
					<span class='ipsButton ipsButton--primary' data-conflict-name="old">
						
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $collection as $name => $input ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $name == 'conflict_' . $data['conflict_id'] ):
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
				<div class='ipsSpanGrid__6'>
					<span class='ipsButton ipsButton--primary' data-conflict-name="new">
						
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $collection as $name => $input ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $name == 'conflict_' . $data['conflict_id'] ):
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

	function templateConflictLarge( $one, $two, $type='css' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$id = md5(mt_rand());
$return .= <<<IPSCONTENT

<div class="ipsSpanGrid">
	<div class="ipsSpanGrid__6">
		<textarea name="editor_one" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_one" data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="editor">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $one, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
	</div>
	<div class="ipsSpanGrid__6">
		<textarea name="editor_two" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_two" data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="editor">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $two, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
	</div>
</div>

IPSCONTENT;

		return $return;
}

	function templateEditor( $theme, $templateNames, $template, $current ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=saveTemplate&id={$theme->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="POST" id="editorForm">
	<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_type" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_key" value="
IPSCONTENT;

if ( $current['type'] == 'templates' ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['TemplateKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['CssKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_app" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_location" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_group" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['group'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_name" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['template'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="t_item_id" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['item_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
	<div id='elTemplateEditor' data-normalURL="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&id={$theme->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ajaxURL="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=ajax&id={$theme->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
		<div class='ipsColumns'>
			<div class='ipsColumns__secondary i-basis_280' data-role="fileList">
				<div class='cTemplateControls' id='elTemplateEditor_fileListControls'>
					<div class="i-flex ipsJS_show">
						<input type='text' data-role="templateSearch" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_templates', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
						<a href='#elTemplateFilterMenu_menu' id='elTemplateFilterMenu' class='ipsButton ipsButton--inherit ipsButton--icon ipsButton--small' data-ipsMenu data-ipsMenu-selectable><i class="fa-solid fa-filter"></i> &nbsp;<i class='fa-solid fa-caret-down'></i></a>
					</div>
					<ul id='elTemplateFilterMenu_menu' role='menu' class='ipsMenu ipsMenu_selectable ipsMenu_auto ipsMenu_withStem ipsHide'>
						<li class='ipsMenu_item ipsMenu_itemChecked' data-ipsMenuValue='outofdate'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_outofdate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item ipsMenu_itemChecked' data-ipsMenuValue='modified'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_modified', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item ipsMenu_itemChecked' data-ipsMenuValue='inherited'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_inherited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item ipsMenu_itemChecked' data-ipsMenuValue='unique'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_unique', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item ipsMenu_itemChecked' data-ipsMenuValue='unmodified'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_unmodified', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					</ul>
					<ul id='elTemplateEditor_newItemMenu_menu' role='menu' class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide'>
						<li class='ipsMenu_item'><a role='menuitem' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=cssForm&id={$theme->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_add_css', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_new_css', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item'><a  role='menuitem' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templateForm&id={$theme->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_add_html', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_new_html', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					</ul>
				</div>
				<div class='i-background_1'>
					<i-tabs class='ipsTabs ipsTabs--stretch acpFormTabBar' id='elTemplateEditor_typeTabs' data-ipsTabBar data-ipsTabBar-contentArea='#elTemplateEditor_fileList' data-ipsTabBar-updateURL='false'>
						<div role="tablist">
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$theme->_id}&t_type={$current['type']}&t_type=templates", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' aria-selected="
IPSCONTENT;

if ( $current['type'] == 'templates' ):
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

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=ajax&id={$theme->_id}&do=loadMenu&t_type=templates", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-type='templates'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_templates', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$theme->_id}&t_type={$current['type']}&t_type=css", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' aria-selected="
IPSCONTENT;

if ( $current['type'] == 'css' ):
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

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=ajax&id={$theme->_id}&do=loadMenu&t_type=css", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-type='css'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_css', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

					</i-tabs>
					<section id='elTemplateEditor_fileList'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "customization", "core", 'admin' )->templateEditorMenu( $theme, $templateNames, $current );
$return .= <<<IPSCONTENT

					</section>
				</div>
				<div class='i-background_2 i-padding_3' id='elTemplateEditor_newButton'>
					<a href='#elTemplateEditor_newItemMenu_menu' id='elTemplateEditor_newItemMenu' class='ipsButton ipsButton--secondary ipsButton--small ipsButton--wide' data-ipsMenu data-ipsMenu-above>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp;<i class='fa-solid fa-caret-down'></i></a>
				</div>
			</div>
			<div class='ipsColumns__primary'>
				<div class='cTemplateControls'>
					<ul class='ipsList ipsList--inline' id='elTemplateEditor_panelToolbar'>
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
                                
IPSCONTENT;

if ( $theme->parent() ):
$return .= <<<IPSCONTENT

                                <li class='ipsMenu_item' data-ipsMenuValue='diffparent'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_show_parent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</li>
						<li class='
IPSCONTENT;

if ( $current['type'] == 'css' ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							<a href='#' id='elTemplateEditor_variables' class='ipsButton ipsButton--inherit ipsButton--small' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_edit_variables', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-content="#elTemplateEditor_variablesDialog" data-ipsDialog-size='narrow'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_variables', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
						<li class='
IPSCONTENT;

if ( $current['type'] == 'templates' ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							<a href='#' id='elTemplateEditor_attributes' class='ipsButton ipsButton--inherit ipsButton--small' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_edit_css', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=deleteTemplate&t_type={$current['type']}&id={$theme->_id}&t_item_id={$current['item_id']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit ipsButton--small 
IPSCONTENT;

if ( $template['InheritedValue'] == 'original' ):
$return .= <<<IPSCONTENT
ipsButton--disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action="revert">
IPSCONTENT;

if ( $template['InheritedValue'] == 'custom' ):
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
							<span data-role='loading' class='ipsHide'><i class='ipsLoadingIcon'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</li>
					</ul>
				</div>
				<div id='elTemplateEditor_outerPanelWrap'>
					<div id='elTemplateEditor_panelWrap'>
						<i-tabs id='elTemplateEditor_tabbar' class='ipsTabs acpFormTabBar' data-ipsTabBar data-ipsTabBar-contentArea='#elTemplateEditor_panels'>
							<div role='tablist'>
								
IPSCONTENT;

if ( $current['type'] == 'templates' ):
$return .= <<<IPSCONTENT

									<a href='#' class='ipsTabs__tab' id='tab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['TemplateKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' role="tab" data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['TemplateKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['template'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <span data-action='closeTab'><i class='fa-solid fa-xmark'></i></span></a>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<a href='#' class='ipsTabs__tab' id='tab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['jsDataKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' role="tab" data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['jsDataKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['css_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <span data-action='closeTab'><i class='fa-solid fa-xmark'></i></span></a>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</i-tabs>
						<section data-role="templatePanelWrap" id='elTemplateEditor_panels'>
							
IPSCONTENT;

if ( $current['type'] == 'templates' ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "customization", "core", 'admin' )->templateEditorHtmlPane( $theme, $templateNames, $template, $current );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "customization", "core", 'admin' )->templateEditorCssPane( $theme, $templateNames, $template, $current );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</section>
					</div>
				</div>
			</div>
		</div>

		<div id='elTemplateEditor_variablesDialog' class='ipsHide'>
			<div>
				<div class='i-padding_3'>
					<div class='ipsMessage ipsMessage--info'> 
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'template_variables_save_warning_msg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
					<p class='ipsType_textBlock'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_variables_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
					<br>
					<textarea class='ipsInput ipsInput--text ipsInput--fullWidth' data-role='variables' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_variables', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></textarea>
					<input type='hidden' name='_variables_fileid' value=''>
					<input type='hidden' name='_variables_type' value=''>
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

		<div id='elTemplateEditor_attributesDialog' class='ipsHide'>
			<div>
				<div class='i-padding_3'>
					<p class='ipsType_textBlock'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_css_attributes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
					<br>
					<input class='ipsInput ipsInput--text ipsInput--fullWidth' data-role='variables' placeholder="e.g. media='screen'">
					<input type='hidden' name='_variables_fileid' value=''>
					<input type='hidden' name='_variables_type' value=''>
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
<ul class='ipsList ipsList--inline i-color_soft i-font-size_-1' id='elTemplateEditor_info'>
	<li class='cTemplateState_outofdate'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_outofdate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	<li class='cTemplateState_changed'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_modified', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	<li class='cTemplateState_inherit'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_inherited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
	<li class='cTemplateState_custom'> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_template_unique', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
</ul>
IPSCONTENT;

		return $return;
}

	function templateEditorAddForm( $formHTML, $type ) {
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

	function templateEditorCssPane( $theme, $templateNames, $template, $current ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-role="templatePanel">
	<div data-role="templatePanel" data-fileid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['jsDataKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-app="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-location="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-group="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['group'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['template'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-type='css' data-itemID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['item_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-inherited-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['InheritedValue'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<textarea  name="editor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['jsDataKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id="editor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['jsDataKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="editor">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['css_content'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function templateEditorHtmlPane( $theme, $templateNames, $template, $current ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsTabs__panel' role="tabpanel" id='ipsTabs_elTemplateEditor_tabbar_tab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['TemplateKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' data-role="templatePanel" data-fileid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['TemplateKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-app="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-location="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-group="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['group'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['template'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-type='templates' data-itemID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['item_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-inherited-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['InheritedValue'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input data-role="variables" type="text" name="variables_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['TemplateKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text ipsInput--fullWidth" value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['template_data'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'skin_set_template_templatevars', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
	<textarea name="editor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['TemplateKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id="editor_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['TemplateKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="editor">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $template['template_content'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
</div>
IPSCONTENT;

		return $return;
}

	function templateEditorMenu( $theme, $templateNames, $current ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='cTemplateList' data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>

IPSCONTENT;

foreach ( $templateNames as $app => $data ):
$return .= <<<IPSCONTENT

<li 
IPSCONTENT;

if ( $app == $current['app'] ):
$return .= <<<IPSCONTENT
class="cTemplateList_activeBranch"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class="cTemplateList_inactiveBranch"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-app="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$theme->_id}&t_type={$current['type']}&t_app={$app}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action="toggleBranch">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
<ul>
    
IPSCONTENT;

foreach ( $templateNames[$app] as $location => $data ):
$return .= <<<IPSCONTENT

    <li 
IPSCONTENT;

if ( $location == $current['location'] ):
$return .= <<<IPSCONTENT
class="cTemplateList_activeBranch"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class="cTemplateList_inactiveBranch"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-location="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
    <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$theme->_id}&t_type={$current['type']}&t_app={$app}&t_location={$location}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action="toggleBranch">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
    <ul>
        
IPSCONTENT;

foreach ( $templateNames[$app][$location] as $group => $data ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( $current['type'] == 'css' AND $group == '.' ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

foreach ( $data as $id => $css ):
$return .= <<<IPSCONTENT

        <li 
IPSCONTENT;

if ( $css['css_name'] == $current['template'] && !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
class="cTemplateList_activeNode"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
        <a data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$theme->_id}&t_type={$current['type']}&t_app={$app}&t_location={$location}&t_group={$group}&t_name=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( urlencode($css['css_name']), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action="openFile" data-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css['css_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-inherited-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css['InheritedValue'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css['jsDataKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-itemID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css['css_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $css['css_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
        </li>
        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

        <li 
IPSCONTENT;

if ( $location == $current['location'] AND $group == $current['group'] ):
$return .= <<<IPSCONTENT
class="cTemplateList_activeBranch"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class="cTemplateList_inactiveBranch"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-group="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
        <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$theme->_id}&t_type={$current['type']}&t_app={$app}&t_location={$location}&t_group={$group}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action="toggleBranch">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
        <ul>
            
IPSCONTENT;

foreach ( $templateNames[$app][$location][$group] as $name => $data ):
$return .= <<<IPSCONTENT

            <li 
IPSCONTENT;

if ( $name == $current['template'] && !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
class="cTemplateList_activeNode"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
            <a data-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $current['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$theme->_id}&t_type={$current['type']}&t_app={$app}&t_location={$location}&t_group={$group}&t_name=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( urlencode($name), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action="openFile" data-name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-inherited-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['InheritedValue'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['jsDataKey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-itemID='
IPSCONTENT;

if ( $current['type'] == 'css' ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['css_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['template_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
            </li>
            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        </ul>
        </li>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

    </ul>
    </li>
    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function templateEditorSimple( $theme, $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox" id='elTemplateEditor' data-controller='core.admin.templates.simple'>
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function themeDescription( $theme ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( mb_strtolower( $theme->author_name ) != 'invision power services' and ( $theme->author_name != '' OR $theme->author_url != '' ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $theme->author_name != ''  ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$sprintf = array($theme->author_name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $theme->author_url ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->basicUrl( $theme->website(), TRUE, $theme->website(), TRUE, TRUE, TRUE );
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

	function themeEditingMessage( $theme, $message ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$message} <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=endEditing&id={$theme->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm class="ipsButton ipsButton--secondary ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_editing_cancel_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function themeRowTitle( $theme ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-flex i-gap_2 i-align-items_center">
	<div class="i-flex_11 i-flex i-gap_2">
		<h4 class="i-font-size_2 i-color_hard i-font-weight_600">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $theme->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
		<span class='i-font-weight_500 i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $theme->version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	</div>
	<!-- <div class="ipsTree_row_cells">
		<span class="ipsTree_row_cell">
			
IPSCONTENT;

if ( !$theme->isCustomized() ):
$return .= <<<IPSCONTENT

				<span class="i-color_positive"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invision', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="i-color_soft"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'theme_has_been_customized', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</span>
	</div>
	-->
</div>
IPSCONTENT;

		return $return;
}

	function viewTemplate( $masterTemplateEncoded ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	<pre class='i-font-family_monospace ipsScrollbar ipsTemplate_box'>
		{$masterTemplateEncoded}
	</pre>
</div>
IPSCONTENT;

		return $return;
}

	function vseBadge( $themeId ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=launchvse&id=$themeId" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="i-link-color_inherit" target='_blank' rel="noopener" data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_theme_launch_vse_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_theme_launch_vse', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}}