<?php
namespace IPS\Theme;
class class_core_admin_trees extends \IPS\Theme\Template
{	function row( $url,$id,$title,$hasChildren=FALSE,$buttons=array(),$description='',$icon=NULL,$draggablePosition=NULL,$root=FALSE,$toggleStatus=NULL,$locked=NULL,$badge=NULL,$titleHtml=FALSE,$descriptionHtml=FALSE,$acceptsChildren=TRUE,$canBeRoot=TRUE, $additionalRowHtml=NULL, $lockedLang=NULL, $supportsFeatureColour=false, $featureColor=null ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsTree_row 
IPSCONTENT;

if ( !$root and $draggablePosition !== NULL ):
$return .= <<<IPSCONTENT
ipsTree_sortable
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $hasChildren ):
$return .= <<<IPSCONTENT
ipsTree_parent
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( !$canBeRoot ):
$return .= <<<IPSCONTENT
ipsTree_noRoot
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $root ):
$return .= <<<IPSCONTENT
ipsTree_open ipsTree_noToggle ipsTree_root
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $acceptsChildren ):
$return .= <<<IPSCONTENT
ipsTree_acceptsChildren
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-nodeid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-keyNavBlock data-keyAction='right'>
	
IPSCONTENT;

if ( !$root and $draggablePosition !== NULL ):
$return .= <<<IPSCONTENT

		<div class='ipsTree_drag ipsDrag'>
			<i class='ipsTree_dragHandle ipsDrag_dragHandle fa-solid fa-bars ipsJS_show' data-ipsTooltip data-ipsTooltip-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reorder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></i>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $icon !== NULL and $icon instanceof \IPS\File ):
$return .= <<<IPSCONTENT

		<img class="ipsTree__thumb" src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
	
IPSCONTENT;

elseif ( $icon !== NULL and $icon instanceof \IPS\Http\Url ):
$return .= <<<IPSCONTENT

		<img class="ipsTree__thumb" src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsTree_align'>
		
IPSCONTENT;

if ( $icon !== NULL and !( $icon instanceof \IPS\File ) and !( $icon instanceof \IPS\Http\Url ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( mb_substr( $icon, 0, 7) === 'ipsFlag' ):
$return .= <<<IPSCONTENT

				<span class="ipsTree__icon"><i class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></span>
			
IPSCONTENT;

elseif ( mb_strpos( $icon, '<span' ) !== false ):
$return .= <<<IPSCONTENT

			    <span class="ipsTree__icon">{$icon}</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="ipsTree__icon"><i class="
IPSCONTENT;

if ( mb_substr( $icon, 0, 3 ) !== 'fa-' ):
$return .= <<<IPSCONTENT
fa-solid fa-
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i></span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsTree_rowData'>
			<div class="ipsTree_title">
				
IPSCONTENT;

if ( $featureColor ):
$return .= <<<IPSCONTENT
<span class="ipsTree_swatch" style="--i-featured:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $featureColor, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $hasChildren ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'root' => $id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

if ( !$titleHtml ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							{$title}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$titleHtml ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						{$title}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>

			<!-- additionalRowHtml start -->
			{$additionalRowHtml}
			<!-- additionalRowHtml end -->

			
IPSCONTENT;

if ( $description ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !$descriptionHtml ):
$return .= <<<IPSCONTENT

					<div class="ipsTree_description i-color_soft i-margin-top_1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class="ipsTree_description i-color_soft i-margin-top_1">{$description}</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $badge ):
$return .= <<<IPSCONTENT

			<span class="ipsBadge ipsBadge--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( !empty($badge[2]) ):
$return .= <<<IPSCONTENT
{$badge[2]}
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$badge[1]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $toggleStatus !== NULL ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $locked ):
$return .= <<<IPSCONTENT

				<span class='ipsBadge 
IPSCONTENT;

if ( $toggleStatus ):
$return .= <<<IPSCONTENT
ipsBadge--positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsBadge--negative
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-cursor_not-allowed' 
IPSCONTENT;

if ( $lockedLang ):
$return .= <<<IPSCONTENT
data-ipsTooltip title='
IPSCONTENT;

$val = "{$lockedLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

if ( $lockedLang ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle-info"></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'locked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \is_array($toggleStatus)  ):
$return .= <<<IPSCONTENT

					<span data-ipsStatusToggle>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'enableToggle', 'status' => '0', 'id' => $id, 'root' => \IPS\Request::i()->root ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( !$toggleStatus['status'] ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'turn_offline', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsTooltip data-state="enabled" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_disable_row', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsBadge ipsBadge--positive'>
							
IPSCONTENT;

$val = "{$toggleStatus['enabled_lang']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</a>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'enableToggle', 'status' => '1', 'id' => $id, 'root' => \IPS\Request::i()->root ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $toggleStatus['status'] ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'turn_offline', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsTooltip data-state="disabled" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_enable_row', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsBadge ipsBadge--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $toggleStatus['disabled_badge'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

$val = "{$toggleStatus['disabled_lang']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</a>
					</span>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<span data-ipsStatusToggle>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'enableToggle', 'status' => '0', 'id' => $id, 'root' => \IPS\Request::i()->root ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( !$toggleStatus ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-state="enabled" data-ipsTooltip title='
IPSCONTENT;

$sprintf = array($title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_disable_row', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsTree_toggleEnable ipsBadge ipsBadge--positive'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</a>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'enableToggle', 'status' => '1', 'id' => $id, 'root' => \IPS\Request::i()->root ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $toggleStatus ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-state="disabled" data-ipsTooltip title='
IPSCONTENT;

$sprintf = array($title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_enable_row', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsTree_toggleDisable ipsBadge ipsBadge--negative'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</a>
					</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsTree_controls'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( $buttons );
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>

IPSCONTENT;

if ( !$hasChildren ):
$return .= <<<IPSCONTENT

	<ol class='ipsTree ipsTree_node'></ol>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function rows( $rows, $uniqid, $root=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<ol class='ipsTree ipsTree_node'>
		
IPSCONTENT;

foreach ( $rows as $id => $row ):
$return .= <<<IPSCONTENT

			<li id="sortable-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uniqid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="node" class='mjs-nestedSortable-collapsed'>
				{$row}
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
	
	</ol>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function template( $url,$title,$root,$rootParent,$rows,$rootButtons=array(),$lockParents=FALSE,$protectRoots=FALSE,$searchable=FALSE,$pagination=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $rootButtons ):
$return .= <<<IPSCONTENT

	<!-- Primary buttons -->
	<div class='i-flex i-justify-content_end'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->buttons( $rootButtons, $url );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class='ipsBox ipsBox--tree' data-ips-template="template">
	
	
IPSCONTENT;

if ( ( \IPS\Widget\Request::i()->root and !\IPS\Widget\Request::i()->noshowroot ) or ($searchable && !empty( $rows ))  ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			<div class="ipsButtonBar__start">
				
IPSCONTENT;

if ( ( \IPS\Widget\Request::i()->root and !\IPS\Widget\Request::i()->noshowroot ) ):
$return .= <<<IPSCONTENT

				<!-- Back to root buttons -->
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary ipsButton--small'><i class="fa-solid fa-arrow-left"></i> 
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

if ( $rootParent !== NULL ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \is_object( $rootParent ) ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'root' => $rootParent->_id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit ipsButton--small'><i class="fa-solid fa-arrow-left"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rootParent->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'root' => $rootParent ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit ipsButton--small'><i class="fa-solid fa-arrow-left"></i></a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $pagination ):
$return .= <<<IPSCONTENT

					{$pagination}
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsButtonBar__end">
				
IPSCONTENT;

if ( $searchable && !empty( $rows ) ):
$return .= <<<IPSCONTENT

					<!-- Search -->
					<div class="acpTable_search">
						<i class="fa-solid fa-magnifying-glass"></i>
						<input type='text' id='tree_search' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_prefix_nofield', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class='ipsTree_wrapper' data-ipsTree 
IPSCONTENT;

if ( $lockParents ):
$return .= <<<IPSCONTENT
data-ipsTree-lockParents
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $protectRoots ):
$return .= <<<IPSCONTENT
data-ipsTree-protectRoots
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsTree-url='
IPSCONTENT;

if ( \IPS\Widget\Request::i()->root ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'root' => \IPS\Request::i()->root ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsTree-searchable='#tree_search'>
		{$root}
		
IPSCONTENT;

if ( empty( $rows ) ):
$return .= <<<IPSCONTENT

			<div class='ipsEmptyMessage'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<form accept-charset='utf-8' action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'root' => \IPS\Request::i()->root ?: 0, 'do' => 'reorder' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-role="treeListing">
				<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				<div class='ipsTree_rows'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "trees", "core" )->rows( $rows, mt_rand(), true );
$return .= <<<IPSCONTENT

				</div>
			</form>
			
IPSCONTENT;

if ( $searchable ):
$return .= <<<IPSCONTENT

				<div data-role="treeResults"></div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				{$pagination}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}}