<?php
namespace IPS\Theme;
class class_cms_admin_media extends \IPS\Theme\Template
{	function fileListing( $url, $item ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $item instanceof \IPS\cms\Media ):
$return .= <<<IPSCONTENT

	<li data-role='mediaItem' data-fileid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-uploaded='
IPSCONTENT;

$val = ( $item->added instanceof \IPS\DateTime ) ? $item->added : \IPS\DateTime::ts( $item->added );$return .= (string) $val->localeDate();
$return .= <<<IPSCONTENT
' data-path='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->full_path, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-filename='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->filename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-url='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
?_cb=
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( time(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-fileType='
IPSCONTENT;

if ( $item->is_image ):
$return .= <<<IPSCONTENT
image
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
file
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		<div class='cMedia_item 
IPSCONTENT;

if ( $item->is_image ):
$return .= <<<IPSCONTENT
cMedia_itemImage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->filename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
			<div class='ipsThumb'>
				
IPSCONTENT;

if ( $item->is_image ):
$return .= <<<IPSCONTENT

					<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
?_cb=
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( time(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="">
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->file_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='cMedia_filename'><p class='ipsTruncate_1'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->filename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p></div>
		</div>
	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function folderRow( $url, $row ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !( $row instanceof \IPS\cms\Media ) ):
$return .= <<<IPSCONTENT

	<li class='ipsTreeList_inactiveBranch' data-role="mediaFolder" data-folderID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'root' => $row->id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
		<ul></ul>				
	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function media( $tree ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$rootButtons = \call_user_func( $tree->getRootButtons );
$return .= <<<IPSCONTENT


<div data-controller='cms.admin.media.main' class='cMedia_manager ipsBox'>
	<div class='ipsColumns ipsColumns--lines'>
		<div class='ipsColumns__secondary i-basis_360 i-background_1'>
			<div class='cMedia__managerToolbar'>
				
IPSCONTENT;

if ( $rootButtons['add_folder'] ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rootButtons['add_folder']['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_add_media_folder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary ipsButton--small ipsButton--wide' data-role='folderButton'><i class="fa-solid fa-folder-plus"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_add_media_folder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<i-tabs class="ipsTabs ipsTabs--stretch acpFormTabBar" id="ipsTabs_mediaSidebar" data-ipsTabBar data-ipsTabBar-contentarea="#elMedia_sidebar" data-ipstabbar-updateurl="false">
				<div role='tablist'>
					<button type="button" class="ipsTabs__tab" id="ipsTabs_mediaSidebar_folders" role="tab" aria-controls="ipsTabs_mediaSidebar_folders_panel" aria-selected="true" data-type="templates">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_folders', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					<button type="button" class="ipsTabs__tab" id='ipsTabs_mediaSidebar_overview' role="tab" aria-controls="ipsTabs_mediaSidebar_overview_panel" aria-selected="false" data-type="css">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_file_overview', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</div>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

			</i-tabs>
			<div id='elMedia_sidebar' class='i-background_1 ipsScrollbar'>
				<div id='ipsTabs_mediaSidebar_folders_panel' class='ipsTabs__panel' role="tabpanel" aria-labelledby="ipsTabs_mediaSidebar_folders">
					
IPSCONTENT;

$roots = \call_user_func( $tree->getRoots );
$return .= <<<IPSCONTENT

					<ul class='ipsTreeList' data-role='folderList'>
						<li class='ipsTreeList_activeBranch ipsTreeList_activeNode' data-role="mediaFolder" data-folderID='0' data-loaded='true'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tree->url->setQueryString( array('root' => 0 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_root_folder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							<ul>
								
IPSCONTENT;

foreach ( $roots as $id => $row ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "media", "cms" )->folderRow( $tree->url, $row );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</ul>
						</li>
					</ul>
				</div>
				<div id='ipsTabs_mediaSidebar_overview_panel' class='ipsTabs__panel i-padding_3' data-role='mediaSidebar' role="tabpanel" aria-labelledby="ipsTabs_mediaSidebar_overview" hidden>
					<div data-role='itemInformation'>
						<div class='cMedia_preview i-margin-bottom_3' data-role='itemPreview'></div>
						<div class=''>
							<strong class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_tag_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							<input type='text' class='ipsInput ipsInput--text ipsInput--fullWidth' value='' data-role='itemTag'>
							<p class='i-font-size_-1 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_tag_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						</div>
						
						<hr class='ipsHr'>

						<ul class='i-margin-top_2'>
							<li class='i-margin-bottom_1'>
								<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_filename', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<p class='i-font-size_1' data-role='itemFilename'></p>
							</li>
                            <li class='i-margin-bottom_1'>
                                <h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_url', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                <p class='i-font-size_1' data-role='itemUrl'></p>
                                <p class='i-font-size_-1 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_url_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                            </li>
							<li class='i-margin-bottom_1'>
								<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_uploaded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<p class='i-font-size_1' data-role='itemUploaded'></p>
							</li>
							<li class='i-margin-bottom_1'>
								<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_size', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<p class='i-font-size_1' data-role='itemFilesize'></p>
							</li>
							<li class='i-margin-bottom_1' data-role='itemDimensionsRow'>
								<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'media_dims', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<p class='i-font-size_1' data-role='itemDimensions'></p>
							</li>
						</ul>

						<hr class='ipsHr'>

						<div class='i-margin-top_2'>
							<a href='#' class='i-font-size_1' data-role='replaceFile' data-baseUrl="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=media&do=replace&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replace_media_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
					</div>
					<div data-role='multipleItems'>
						<div class='i-padding_3 i-font-size_2 i-color_soft i-text-align_center i-margin-top_4' data-role='multipleItemsMessage'></div>
					</div>
				</div>
			</div>			
		</div>
		<div class='ipsColumns__primary'>
			<div class='cMedia__managerToolbar'>
				<ul class='ipsButtons ipsButtons--start ipsButtons--media i-flex_11'>
					
IPSCONTENT;

if ( $rootButtons['add_page'] ):
$return .= <<<IPSCONTENT

						<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=media&do=upload", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-remoteSubmit data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_add_media', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small' data-role='uploadButton'><i class='fa-solid fa-cloud-arrow-up'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_add_media', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li class='ipsHide' data-action='deleteFolder'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=media&do=delete", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-forceReload data-ipsDialog-remoteVerify="false" data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--negative ipsButton--small'><i class='fa-solid fa-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_delete_folder_media', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					<li class='ipsHide' data-action='deleteSelected'><a href='#' class='ipsButton ipsButton--negative ipsButton--small'><i class='fa-solid fa-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_delete_selected_media', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					<li class='ipsResponsive_showPhone'><hr class='ipsHr'></li>
				</ul>
				<input type='search' class='ipsInput ipsInput--text' data-role='mediaSearch' id='elMedia_searchField' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_search_media', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
			</div>
			<div class='i-padding_3 i-background_2 ipsScrollbar' data-role="fileListing" id='elMedia_fileList' data-showing='root'>
				<ul class='ipsGrid ipsGrid--pages-media'>
					
IPSCONTENT;

foreach ( $roots as $id => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "media", "cms" )->fileListing( $tree->url, $data );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</div>
			<div class='i-padding_3 i-background_2 ipsScrollbar ipsHide' data-role="searchResults" id='elMedia_searchResults'></div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}}