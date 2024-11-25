<?php
namespace IPS\Theme;
class class_gallery_front_submit extends \IPS\Theme\Template
{	function categorySelector( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('choose_category') );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='i-padding_3'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	{$form}

IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function chooseAlbum( $category, $createAlbumForm, $canCreateAlbum, $maximumAlbums, $existingAlbumForm ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='cGalleryChooseAlbum_wrap' data-controller='gallery.front.submit.chooseCategory'>
	<div data-role='chooseAlbumType' class='cGalleryChooseAlbum_list'>
		<ul>
			
IPSCONTENT;

if ( $category->allow_albums != 2 ):
$return .= <<<IPSCONTENT

				<li>
					<a href='#' class='cGalleryChooseAlbum_listItem' data-type='category'>
						<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'use_no_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						<p class='i-color_soft'>
IPSCONTENT;

$sprintf = array($category->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_directly_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
					</a>
					<form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=submit&category={$category->_id}&noAlbum=1", null, "gallery_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsHide'>
						<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'use_no_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-right'></i></button>	
					</form>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<li>
				<a href='#' class='cGalleryChooseAlbum_listItem' data-type='createAlbum' 
IPSCONTENT;

if ( !$canCreateAlbum || !$createAlbumForm ):
$return .= <<<IPSCONTENT
data-disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_new_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<p class='i-color_soft'>
						
IPSCONTENT;

if ( $createAlbumForm ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_new_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( !$canCreateAlbum ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cannot_create_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$sprintf = array($maximumAlbums); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'used_max_albums', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
				</a>
			</li>
			<li>
				<a href='#' class='cGalleryChooseAlbum_listItem' data-type='existingAlbum' 
IPSCONTENT;

if ( !$existingAlbumForm ):
$return .= <<<IPSCONTENT
data-disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'choose_existing_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<p class='i-color_soft'>
						
IPSCONTENT;

if ( $existingAlbumForm ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'choose_existing_album_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_existing_albums', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
				</a>
			</li>
		</ul>
	</div>

	
IPSCONTENT;

if ( $createAlbumForm ):
$return .= <<<IPSCONTENT

		<div data-role='createAlbumForm' hidden>
			{$createAlbumForm}
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $existingAlbumForm ):
$return .= <<<IPSCONTENT

		<div data-role='existingAlbumForm' hidden>
			{$existingAlbumForm}
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function chooseCategory( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='gallery.front.submit.chooseCategory' data-preselected-album="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->album, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='cGallerySubmit_chooseCategory'>
	<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--choose-category" action="
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
 data-ipsForm data-role='categoryForm'>
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

		<div class='cGallerySubmit_albumChoice'>
			{$elements['']['image_category']->html()}
		</div>
		<div class="i-padding_3 i-border-top_3 i-text-align_center ipsJS_hide" data-role="continueCategory">
			
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

				{$button}
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function container( $container ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	
IPSCONTENT;

if ( $container['album'] ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$sprintf = array($container['album']->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_submit_prefix_album', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$sprintf = array($container['category']->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_submit_prefix_cat', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function createAlbum( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--create-album" action="
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

	<ul class='cGalleryDialog_formPanel'>
		
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \in_array( $inputName, array( 'album_name', 'album_description' ) ) ):
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

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		<li class='ipsFieldRow' id='elAlbumExtrasRow'>
			<ul class='ipsButtons ipsButtons--fill'>
				
IPSCONTENT;

if ( isset( $elements['']['set_album_owner'] ) && \count( $elements['']['set_album_owner']->options['options'] ) > 1 ):
$return .= <<<IPSCONTENT

					<li>
						<a href='#elAlbumCreate_owner_menu' id='elAlbumCreate_owner' class="ipsButton ipsButton--inherit ipsButton--small" data-ipsMenu data-ipsMenu-appendTo='#elAlbumExtrasRow' data-ipsMenu-closeOnClick='false'><i class="fa-solid fa-user"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_create_owner', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( ( isset( $elements['']['album_type'] ) && \count( $elements['']['album_type']->options['options'] ) > 1 ) OR ( isset( $elements['']['album_submit_type'] ) && \count( $elements['']['album_submit_type']->options['options'] ) > 1 ) ):
$return .= <<<IPSCONTENT

					<li>
						<a href='#elAlbumCreate_privacy_menu' id='elAlbumCreate_privacy' class="ipsButton ipsButton--inherit ipsButton--small" data-ipsMenu data-ipsMenu-appendTo='#elAlbumExtrasRow' data-ipsMenu-closeOnClick='false'><i class="fa-solid fa-lock"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_create_privacy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $elements['']['album_sort_options'] ) || isset( $elements['']['album_allow_comments'] ) || isset( $elements['']['album_allow_reviews'] ) || isset( $elements['']['album_use_comments'] ) || isset( $elements['']['album_use_reviews'] ) ):
$return .= <<<IPSCONTENT

					<li><a href='#elAlbumCreate_features_menu' id='elAlbumCreate_features' class="ipsButton ipsButton--inherit ipsButton--small" data-ipsMenu data-ipsMenu-appendTo='#elAlbumExtrasRow' data-ipsMenu-closeOnClick='false'><i class="fa-solid fa-comments"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_create_features', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</li>
	</ul>
	<div class='ipsSubmitRow cGalleryDialog_submitBar'>
		<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
	<div id='elAlbumCreate_features_menu' class='ipsMenu ipsMenu_wide ipsHide'>
		<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--album-features'>
			
IPSCONTENT;

foreach ( array( 'album_sort_options', 'album_allow_comments', 'album_allow_reviews', 'album_use_comments', 'album_use_reviews' ) as $inputName ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $elements[''][ $inputName ] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \is_object( $elements[''][ $inputName ] )  ):
$return .= <<<IPSCONTENT

						{$elements[''][ $inputName ]->rowHtml($form)}
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						{$elements[''][ $inputName ]}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
	<div id='elAlbumCreate_privacy_menu' class='ipsMenu ipsMenu_wide ipsHide'>
		<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--album-privacy'>
			
IPSCONTENT;

foreach ( array( 'album_type', 'album_allowed_access', 'album_submit_type', 'album_submit_access_members', 'album_submit_access_groups' ) as $inputName ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $elements[''][ $inputName ] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \is_object( $elements[''][ $inputName ] )  ):
$return .= <<<IPSCONTENT

						{$elements[''][ $inputName ]->rowHtml($form)}
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						{$elements[''][ $inputName ]}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
	<div id='elAlbumCreate_owner_menu' class='ipsMenu ipsMenu_wide ipsHide'>
		<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--album-owner'>
			
IPSCONTENT;

foreach ( array( 'set_album_owner', 'album_owner' ) as $inputName ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $elements[''][ $inputName ] ) ):
$return .= <<<IPSCONTENT

					{$elements[''][ $inputName ]->rowHtml()}
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function existingAlbumForm( $category, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$existingAlbums = \IPS\gallery\Album::loadForSubmit( $category );
$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
 data-ipsForm data-ipsFormSubmit data-controller='gallery.front.submit.existingAlbums'>
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

	<ul id='elGallerySubmit_albumChooser' class='ipsScrollbar'>
		
IPSCONTENT;

foreach ( $elements['']['existing_album']->options['options'] as $optionID => $option ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$album = $existingAlbums[ $optionID ];
$return .= <<<IPSCONTENT

			<li>
				<input id="existing_album_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $optionID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" type='radio' name='existing_album' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $optionID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $optionID == $elements['']['existing_album']->value ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<div class='i-flex i-gap_2'>
					<span class="ipsThumb i-flex_00 i-basis_40 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_img_album_limit'] && $album->count_imgs >= \IPS\Member::loggedIn()->group['g_img_album_limit'] ):
$return .= <<<IPSCONTENT
i-opacity_4
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

if ( $album->coverPhoto('small') ):
$return .= <<<IPSCONTENT

							<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->coverPhoto( 'small' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
					<div class='i-flex_11 cGallerySubmit_albumInfo'>
						<p><strong class="i-font-size_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></p>
						<ul class='ipsList ipsList--inline i-color_soft'>
							<li>
IPSCONTENT;

$pluralize = array( $album->count_imgs ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_images', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_img_album_limit'] ):
$return .= <<<IPSCONTENT

								<li>
									
IPSCONTENT;

if ( $album->count_imgs >= \IPS\Member::loggedIn()->group['g_img_album_limit'] ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_full_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$pluralize = array( ( \IPS\Member::loggedIn()->group['g_img_album_limit'] - $album->count_imgs ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_more_images_album', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					</div>
					<label for="existing_album_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $optionID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><span class="ipsInvisible">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></label>
				</div>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
	<div class='ipsSubmitRow cGalleryDialog_submitBar'>
		<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'choose_selected_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
</form>

IPSCONTENT;

		return $return;
}

	function postingInformation( $guestPostBeforeRegister, $modQueued, $continueUrl, $guestEmailError=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-text-align_center i-padding_3'>
	<form accept-charset='utf-8' class="ipsForm" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $continueUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsForm>
		
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_guest_post_normal_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<p class='i-font-size_2 i-margin-top_3'>
				
IPSCONTENT;

if ( $guestPostBeforeRegister ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_guest_post_pbr_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_post_sign_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
			
IPSCONTENT;

if ( $modQueued ):
$return .= <<<IPSCONTENT

				<p class='i-color_warning i-margin-top_3'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_guest_post_mod_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</p>					
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $guestPostBeforeRegister ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="guest_email_submit" value="1">
				<input type="email" name="guest_email" value="" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text i-margin-top_3" required autocomplete="email">
				<br><span class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest_email_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

if ( $guestEmailError ):
$return .= <<<IPSCONTENT

					<p class="i-color_warning">
IPSCONTENT;

$val = "{$guestEmailError}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<input type="hidden" name="_pi" value="1">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<ul class='ipsButtons ipsButtons--main'>
				<li>
					<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</li>
			</ul>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<input type="hidden" name="_pi" value="1">
			<h2 class='i-color_warning'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mod_queue_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$warnings = \IPS\Member::loggedIn()->warnings( 1, NULL, 'mq' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $warnings ) and \IPS\Member::loggedIn()->mod_posts > time() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $warnings ) ):
$return .= <<<IPSCONTENT

					<p class="i-font-size_2 i-margin-top_3">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_this_will_be_moderated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<p class="i-font-size_2 i-margin-top_3">
					
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( \IPS\Member::loggedIn()->mod_posts )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restriction_ends', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<ul class='ipsButtons'>
				
IPSCONTENT;

foreach ( $warnings as $idx => $warning ):
$return .= <<<IPSCONTENT

					<li>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit' data-ipsDialog data-ipsDialog-size='narrow'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_warning_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<li>
					<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</form>
</div>
IPSCONTENT;

		return $return;
}

	function processing( $redirector ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_4'>
	<p class='i-font-size_2 i-text-align_center i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'saving_images_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

	{$redirector}
</div>
IPSCONTENT;

		return $return;
}

	function uploadImages( $form, $category ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='elGallerySubmit' enctype="multipart/form-data">
	<div class='cGallerySubmit_uploadImages' data-controller='gallery.front.submit.uploadImages'>
	
IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--error i-margin-bottom_3">
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
		
IPSCONTENT;

foreach ( $form->hiddenValues as $k => $v ):
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


		<div class='ipsHide'>
			{$form->elements['']['credit_all']->html()}
			{$form->elements['']['copyright_all']->html()}
			{$form->elements['']['tags_all']->html()}
			{$form->elements['']['prefix_all']->html()}
			{$form->elements['']['images_order']->html()}
			{$form->elements['']['images_info']->html()}
			
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
{$form->elements['']['nsfw_all']->html()}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT
{$form->elements['']['images_autofollow_all']->html()}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>

		<div id='elGallerySubmit_imageUploader' class=''>
			{$form->elements['']['images']->html( $form )}
			
IPSCONTENT;

if ( $form->elements['']['images']->error ):
$return .= <<<IPSCONTENT

				<br>
				<span class="i-color_warning">
IPSCONTENT;

$val = "{$form->elements['']['images']->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function wrapper( $container, $images, $club, $allImagesForm ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club AND \is_array( $container ) AND !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $container['category'] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div data-controller='gallery.front.submit.wrapper' data-role="submitWrapper" class='
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	<div class='cGalleryDialog'>
		<div class='cGalleryDialog_inner'>
			<div class='cGalleryDialog_primary'>
				<div class='cGalleryDialog_title'>
					<h1 class='ipsDialog_title'>
						<span data-role='dialogTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_gallery_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						<div data-role="containerInfo">
							
IPSCONTENT;

if ( \is_array( $container ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "submit", "gallery" )->container( $container );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</h1>
					<a href='#' data-action='closeDialog' class='cGalleryDialog_close'>&times;</a>
				</div>
				<div class='cGalleryDialog_container 
IPSCONTENT;

if ( \is_array( $container ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="container">
					
IPSCONTENT;

if ( !\is_array( $container ) ):
$return .= <<<IPSCONTENT

						{$container}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class='cGalleryDialog_imageForm 
IPSCONTENT;

if ( \is_string( $container ) ):
$return .= <<<IPSCONTENT
 ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( \IPS\gallery\Image::moderateNewItems( \IPS\Member::loggedIn(), ( is_array( $container ) ) ? $container['category'] : NULL, FALSE ) ):
$return .= <<<IPSCONTENT
 cGalleryDialog_content_moderated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="images">					
					<div class="cGalleryDialog_imageFormAlign">
						<div id='elGallerySubmit_toolBar'>
							<ul class='ipsButtons'>
								<li>
									<a href='#' class='ipsButton ipsButton--soft ipsButton--small' id='elCopyrightMenu' data-ipsMenu data-ipsMenu-appendTo='#elGallerySubmit_toolBar' data-ipsMenu-closeOnClick='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_copyright_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								</li>
								<li>
									<a href='#' class='ipsButton ipsButton--soft ipsButton--small' id='elCreditMenu' data-ipsMenu data-ipsMenu-appendTo='#elGallerySubmit_toolBar' data-ipsMenu-closeOnClick='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_credit_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								</li>
								<li class='
IPSCONTENT;

if ( !isset( $allImagesForm->elements['']['image_tags'] ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 cGalleryTagsButton'>
									<a href='#' class='ipsButton ipsButton--soft ipsButton--small' id='elTagsMenu' data-ipsMenu data-ipsMenu-appendTo='#elGallerySubmit_toolBar' data-ipsMenu-closeOnClick='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_tags_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								</li>
								
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT

								<li>
									<a href='#' class='ipsButton ipsButton--soft ipsButton--small' id='elNSFW' data-ipsMenu data-ipsMenu-appendTo='#elGallerySubmit_toolBar' data-ipsMenu-closeOnClick='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nsfw_set_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

									<li>
										<a href='#' class='ipsButton ipsButton--soft ipsButton--small' id='elNotifyOption' data-ipsMenu data-ipsMenu-appendTo='#elGallerySubmit_toolBar' data-ipsMenu-closeOnClick='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gal_notification_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<li>
									<a href='#' id='elFileTypes' class='i-margin-start_auto ipsButton ipsButton--inherit ipsButton--small' data-ipsMenu>
										<i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'allowed_file_types_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i>
									</a>
								</li>
							</ul>
							
							<div id='elFileTypes_menu' class='ipsMenu ipsMenu_wide ipsMenu--padding ipsHide'>
								<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'attach_allowed_types', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<div class='i-margin-top_2 i-color_soft i-font-size_-2' data-role="allowedTypes"></div>
							</div>
						</div>
						<div data-role='imageForm' class='i-padding_3'>
							
IPSCONTENT;

if ( \is_array( $container ) ):
$return .= <<<IPSCONTENT

								{$images}
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
			</div>
			<div class='cGallerySubmit_imageDetails' data-role="imageDetails">
				<form id='form_imageDetails' accept-charset='utf-8' method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $allImagesForm->action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" enctype="multipart/form-data">
					<div class='i-text-align_center i-color_soft i-font-size_2 i-opacity_4 i-margin-top_3' data-role='submitHelp'>
						<i class='fa-regular fa-image i-font-size_6 i-color_soft'></i>
						<br><br> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_help_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
				</form>
			</div>
		</div>
		<div class='ipsSubmitRow cGallerySubmit_bottomBar 
IPSCONTENT;

if ( !\is_array( $container ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
			<span data-role='imageErrors' class='ipsHide i-color_negative'><i class='fa-solid fa-triangle-exclamation'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'files_had_errors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			<button type='submit' class='ipsButton ipsButton--negative' data-action="closeDialog" form="elGalleryClose">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			<button type='submit' class='ipsButton ipsButton--primary' data-role='submitForm' form="elGallerySubmit" disabled>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_all_images', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</div>

	<div class='ipsMenu ipsMenu_wide ipsMenu--padding ipsHide' id='elCopyrightMenu_menu'>
		<h3 class='ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copyright', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<p class='i-margin-bottom_3 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_copyright_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		{$allImagesForm->elements['']['image_copyright']->html()}
		<button class='ipsButton ipsButton--soft ipsButton--small ipsButton--wide i-margin-top_3' data-role='saveInfo'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>

	<div class='ipsMenu ipsMenu_wide ipsMenu--padding ipsHide' id='elCreditMenu_menu'>
		<h3 class='ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<p class='i-margin-bottom_3 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		{$allImagesForm->elements['']['image_credit_info']->html()}
		<button class='ipsButton ipsButton--soft ipsButton--small ipsButton--wide i-margin-top_3' data-role='saveInfo'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>

	<div class='ipsMenu ipsMenu_wide ipsMenu--padding ipsHide' id='elTagsMenu_menu'>
		<h3 class='ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<p class='i-margin-bottom_3 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tags_all_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<div data-role="globalTagsField">
IPSCONTENT;

if ( isset( $allImagesForm->elements['']['image_tags'] ) ):
$return .= <<<IPSCONTENT
{$allImagesForm->elements['']['image_tags']->html()}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
		<button class='ipsButton ipsButton--soft ipsButton--small ipsButton--wide i-margin-top_3' data-role='saveInfo'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>

    
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT

    <div class='ipsMenu ipsMenu_wide ipsMenu--padding ipsHide' id='elNSFW_menu'>
        {$allImagesForm->elements['']['image_nsfw']->html()} <label for='check_image_nsfw'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nsfw_enable_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
        <button class='ipsButton ipsButton--soft ipsButton--small ipsButton--wide i-margin-top_3' data-role='saveInfo'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
    </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

		<div class='ipsMenu ipsMenu_wide ipsMenu--padding ipsHide' id='elNotifyOption_menu'>
			{$allImagesForm->elements['']['image_auto_follow']->html()} <label for='check_image_auto_follow'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_image_comments_notification', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
			<button class='ipsButton ipsButton--soft ipsButton--small ipsButton--wide i-margin-top_3' data-role='saveInfo'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div data-role="defaultImageDetailsForm" class='ipsHide'>
		<div class='cGallerySubmit_details'>
			<div class="i-padding_3">
				<div class='cGallerySubmit_preview ipsThumb'></div>
			</div>
			<ul class='ipsForm ipsForm--vertical ipsForm--gallery-submit'>
				<li class='ipsFieldRow'>
					<label class='ipsFieldRow__label' for='image_details_DEFAULT'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsFieldRow__content'>
						{$allImagesForm->elements['']['image_title_DEFAULT']->html()}
						<p class='i-color_warning ipsHide' data-errorField='image_title'></p>
					</div>
				</li>
				<li class='ipsFieldRow'>
					<label class='ipsFieldRow__label' for='image_description_DEFAULT'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_description', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsFieldRow__content'>
						<div data-role="imageDescriptionEditor" class='ipsHide'>
							{$allImagesForm->elements['']['filedata__image_description_DEFAULT']->html()}
							<div class='i-text-align_end i-font-size_-2 i-margin-top_2'><a href='#' data-role="imageDescriptionUseTextarea">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'use_plain_editor', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>
							<p class='i-color_warning ipsHide' data-errorField='image_description'></p>
						</div>
						<div data-role="imageDescriptionTextarea">
							{$allImagesForm->elements['']['image_textarea_DEFAULT']->html()}
							<div class='i-text-align_end i-font-size_-2 i-margin-top_2'><a href='#' data-role="imageDescriptionUseEditor">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'use_rte_editor', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>
							<p class='i-color_warning ipsHide' data-errorField='image_textarea'></p>
						</div>
					</div>
				</li>

				<li class='ipsFieldRow cGalleryTagsField 
IPSCONTENT;

if ( !isset( $allImagesForm->elements['']['image_tags_DEFAULT'] ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					<label class='ipsFieldRow__label' for='image_tags_default'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsFieldRow__content'>
						
IPSCONTENT;

if ( isset( $allImagesForm->elements['']['image_tags_DEFAULT'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= preg_replace( '/data-ipsAutocomplete(?!\-)/', '', $allImagesForm->elements['']['image_tags_DEFAULT']->html() );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<p class='i-color_warning ipsHide' data-errorField='image_tags'></p>
					</div>
				</li>

				<li class='ipsFieldRow'>
					<label class='ipsFieldRow__label' for='image_credit_DEFAULT'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_credit_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsFieldRow__content'>
						{$allImagesForm->elements['']['image_credit_info_DEFAULT']->html()}
						<p class='i-color_warning ipsHide' data-errorField='image_credit'></p>
					</div>
				</li>
				<li class='ipsFieldRow'>
					<label class='ipsFieldRow__label' for='image_copyright_DEFAULT'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_copyright', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsFieldRow__content'>
						{$allImagesForm->elements['']['image_copyright_DEFAULT']->html()}
						<p class='i-color_warning ipsHide' data-errorField='image_copyright'></p>
					</div>
				</li>
				<li class='ipsFieldRow ipsHide cGalleryMapField'>
					<label class='ipsFieldRow__label' for='image_gps_show_DEFAULT'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_gps_show', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsFieldRow__content'>
						{$allImagesForm->elements['']['image_gps_show_DEFAULT']->html()}
						<div class='ipsFieldRow__desc'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_gps_show_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
						<p class='i-color_warning ipsHide' data-errorField='image_gps_show'></p>
					</div>
				</li>
				<li class='ipsFieldRow ipsHide cGalleryThumbField'>
					<label class='ipsFieldRow__label' for='image_thumbnail_DEFAULT'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_thumbnail', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					<div class='ipsFieldRow__content'>
						{$allImagesForm->elements['']['image_thumbnail_DEFAULT']->html()}
						<p class='i-color_warning ipsHide' data-errorField='image_thumbnail'></p>
					</div>
				</li>
				
IPSCONTENT;

foreach ( $allImagesForm->elements[''] as $k => $v ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !\in_array( $k, array( 'image_title_DEFAULT', 'image_tags_DEFAULT', 'image_gps_show_DEFAULT', 'image_thumbnail_DEFAULT', 'image_textarea_DEFAULT', 'image_description_DEFAULT', 'filedata__image_description_DEFAULT', 'image_credit_info_DEFAULT', 'image_copyright_DEFAULT', 'image_auto_follow_DEFAULT', 'image_credit_info', 'image_copyright', 'image_auto_follow', 'image_tags', 'image_nsfw' ) ) ):
$return .= <<<IPSCONTENT

						<li class='ipsFieldRow'>
							
IPSCONTENT;

$langString = str_replace( '_DEFAULT', '', $k );
$return .= <<<IPSCONTENT

							<label class='ipsFieldRow__label' for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$langString}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
							<div class='ipsFieldRow__content'>
								{$v->html()}
							</div>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<li class='ipsSubmitRow'>
					<button type='button' class='ipsButton ipsButton--secondary ipsButton--wide' data-role='saveDetails'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_go_back', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					<p class='ipsHide' data-role='savedMessage'>
						<i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_info_saved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
				</li>
			</ul>
		</div>
	</div>
	<div data-role="editedImageDetailsForm" class='ipsHide'></div>
</div>
IPSCONTENT;

		return $return;
}}