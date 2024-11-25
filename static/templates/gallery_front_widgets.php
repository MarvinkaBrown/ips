<?php
namespace IPS\Theme;
class class_gallery_front_widgets extends \IPS\Theme\Template
{	function albums( $albums, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $albums )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget-gallery-albums_' . mt_rand();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class='ipsWidget__content'>
		<i-data>
			<ul class="ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--widget-gallery-albums" 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $albums as $album ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						<figure class='ipsData__image'>
							
IPSCONTENT;

if ( $album->asNode()->coverPhoto('masked') ):
$return .= <<<IPSCONTENT

								<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->asNode()->coverPhoto('masked'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</figure>
						<div class="ipsData__content">
							<div class='ipsData__main'>
								<h2 class='ipsData__title'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
								
IPSCONTENT;

if ( \in_array( $layout, array("table", "grid", "featured")) ):
$return .= <<<IPSCONTENT

									<div class="ipsData__desc ipsRichText">{$album->description}</div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
							<div class="ipsData__extra">
								<ul class='ipsData__stats'>
									<li data-statType='images'>
										<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->count_imgs, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $album->count_imgs ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_img_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
										<span class='ipsData__stats-label'>
IPSCONTENT;

$pluralize = array( $album->count_imgs ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_img_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
									</li>
									
IPSCONTENT;

if ( $album->use_comments && $album->comments > 0 ):
$return .= <<<IPSCONTENT

										<li data-statType='album_comments'>
											<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $album->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
											<span class='ipsData__stats-label'>
IPSCONTENT;

$pluralize = array( $album->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
										</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $album->allow_comments && $album->count_comments > 0 ):
$return .= <<<IPSCONTENT

										<li data-statType='image_comments'>
											<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->count_comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $album->count_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_image_comments_s', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
											<span class='ipsData__stats-label'>
IPSCONTENT;

$pluralize = array( $album->count_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_image_comments_s', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
										</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
								<div class="ipsData__last">
									<div class="ipsData__last-text">
										<div class="ipsData__last-primary">
IPSCONTENT;

$sprintf = array($album->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
										<div class="ipsData__last-secondary">
IPSCONTENT;

$val = ( $album->mapped('updated') instanceof \IPS\DateTime ) ? $album->mapped('updated') : \IPS\DateTime::ts( $album->mapped('updated') );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
									</div>
								</div>
							</div>
						</div>
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

	function galleryStats( $stats, $latestImage, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_galleryStats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
	<ul class='ipsList ipsList--stats ipsList--stacked ipsList--border ipsList--fill'>
		<li>
			<strong class='ipsList__label'>
IPSCONTENT;

$pluralize = array( $stats['totalImages'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_images_front_v', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</strong>
			<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $stats['totalImages'] );
$return .= <<<IPSCONTENT
</span>
		</li>
		
IPSCONTENT;

if ( $stats['totalComments'] ):
$return .= <<<IPSCONTENT

			<li>
				<strong class='ipsList__label'>
IPSCONTENT;

$pluralize = array( $stats['totalComments'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_total_comments_v', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</strong>
				<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $stats['totalComments'] );
$return .= <<<IPSCONTENT
</span>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $stats['totalAlbums'] ):
$return .= <<<IPSCONTENT

			<li>
				<strong class='ipsList__label'>
IPSCONTENT;

$pluralize = array( $stats['totalAlbums'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_albums_v', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</strong>
				<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $stats['totalAlbums'] );
$return .= <<<IPSCONTENT
</span>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
	
IPSCONTENT;

if ( isset($orientation) and $orientation == 'vertical' && $latestImage ):
$return .= <<<IPSCONTENT

		<hr class='ipsHr ipsHr--none'>
		<div class='i-padding_1'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "gallery" )->latestImage( $latestImage );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function imageFeed( $images, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $images )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget-gallery-image-feed_' . mt_rand();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class='ipsWidget__content' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<i-data>
			<ul class="ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--widget-gallery-image-feed" 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $images as $idx => $image ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "gallery" )->imageRow( $image );
$return .= <<<IPSCONTENT

				
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

	function imageRow( $image, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsData__item">
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<figure class="ipsData__image">
		
IPSCONTENT;

if ( $image->small_file_name ):
$return .= <<<IPSCONTENT

			<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="" loading="lazy">
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image, FALSE );
$return .= <<<IPSCONTENT

	</figure>
	<div class="ipsData__content">
		<div class="ipsData__main">
			<h4 class="ipsData__title">
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			</h4>
		</div>
		
IPSCONTENT;

if ( $image->directContainer()->allow_comments && $image->comments ):
$return .= <<<IPSCONTENT

			<div class="ipsData__extra">
				<ul class="ipsData__stats" data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $image->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'>
					<li>
						<i class='fa-solid fa-comment'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</li>
				</ul>
				<div class="ipsData__last">
					<div class="ipsData__last-text">
						<div class="ipsData__last-primary">
IPSCONTENT;

$htmlsprintf = array($image->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
						<div class="ipsData__last-secondary">
IPSCONTENT;

$val = ( $image->mapped('date') instanceof \IPS\DateTime ) ? $image->mapped('date') : \IPS\DateTime::ts( $image->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
					</div>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function latestImage( $latestImage, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elGalleryStatsLatest' class='cGalleryWidget' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<h6 class='ipsTitle ipsTitle--h6 ipsTitle--padding'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'latest_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h6>
	<figure class='ipsFigure'>	
		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $latestImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($latestImage->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
			
IPSCONTENT;

if ( $latestImage->small_file_name ):
$return .= <<<IPSCONTENT

				<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $latestImage->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $latestImage->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class="ipsFigure__icon"></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</a>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $latestImage );
$return .= <<<IPSCONTENT

		<figcaption class='ipsFigure__footer'>
			<div class='ipsFigure__title'>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $latestImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($latestImage->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $latestImage->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</a>
			</div>
			<div class="i-flex i-justify-content_space-between">
				<div>
IPSCONTENT;

$htmlsprintf = array($latestImage->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $latestImage->mapped('date') instanceof \IPS\DateTime ) ? $latestImage->mapped('date') : \IPS\DateTime::ts( $latestImage->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
				
IPSCONTENT;

if ( $latestImage->directContainer()->allow_comments && $latestImage->comments > 0 ):
$return .= <<<IPSCONTENT

					<div data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $latestImage->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'>
						<i class='fa-solid fa-comment'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $latestImage->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</figcaption>
	</figure>
</div>

IPSCONTENT;

		return $return;
}

	function recentComments( $comments, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $comments )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget-gallery-recent-comments_' . mt_rand();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class='ipsWidget__content'>
		<i-data>
			<ul class='ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--widget-gallery-recent-comments' 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $comments as $comment ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						<figure class="ipsData__image">
							
IPSCONTENT;

if ( $comment->item()->masked_file_name ):
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $comment->item()->masked_file_name )->url;
$return .= <<<IPSCONTENT
' loading="lazy" alt=''>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $comment->item(), FALSE );
$return .= <<<IPSCONTENT

						</figure>
						<div class="ipsData__content">
							<div class='ipsData__main'>
								<h4 class='ipsData__title'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($comment->item()->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
								<div class='ipsData__desc'>{$comment->truncated()}</div>
							</div>
							<div class="ipsData__last">
								<div class="ipsData__last-text">
									<div class="ipsData__last-primary">
IPSCONTENT;

$htmlsprintf = array($comment->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
									<div class="ipsData__last-secondary"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">{$comment->dateLine()}</a></div>
								</div>
							</div>
						</div>
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

	function recentImageReviews( $comments, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $comments )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget-gallery-recent-reviews_' . mt_rand();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class='ipsWidget__content'>
		<i-data>
			<ul class='ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--widget-gallery-recent-reviews' 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $comments as $comment ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						<figure class='ipsData__image'>
							
IPSCONTENT;

if ( $comment->item()->masked_file_name ):
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $comment->item()->masked_file_name )->url;
$return .= <<<IPSCONTENT
' loading="lazy" alt=''>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $comment->item(), FALSE );
$return .= <<<IPSCONTENT

						</figure>
						<div class="ipsData__content">
							<div class='ipsData__main'>
								<div class='ipsData__title'>
									<h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($comment->item()->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $comment->rating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT

								</div>
								<p class='ipsData__meta'>
									
IPSCONTENT;

$htmlsprintf = array($comment->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								</p>
								<div class='ipsData__desc ipsRichText ipsTruncate_4 i-margin-top_2'>
									{$comment->truncated()}
								</div>
							</div>
						</div>
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
}}