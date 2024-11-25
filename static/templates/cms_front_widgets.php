<?php
namespace IPS\Theme;
class class_cms_front_widgets extends \IPS\Theme\Template
{	function Blocks( $content, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$content}


IPSCONTENT;

		return $return;
}

	function Categories( $url, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$catClass = '\IPS\cms\Categories' . \IPS\cms\Databases\Dispatcher::i()->databaseId;
$return .= <<<IPSCONTENT


IPSCONTENT;

$categories = $catClass::roots();
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $categories ) ):
$return .= <<<IPSCONTENT

	<div class='ipsWidget__header'>
		<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('show','categories'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsWidget__header-secondary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_show_categories_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
	<div class='ipsSideMenu ipsSideMenu--truncate ipsCategoriesMenu'>
		<ul class='ipsSideMenu__list'>
			
IPSCONTENT;

foreach ( $categories as $category ):
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'>
						<strong class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
						<span>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\cms\Records::contentCount( $category ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</a>
					
IPSCONTENT;

if ( $category->hasChildren() ):
$return .= <<<IPSCONTENT

						<ul class="ipsSideMenu__list">
							
IPSCONTENT;

$counter = 0;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $category->children() as $idx => $subcategory ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$counter++;
$return .= <<<IPSCONTENT

								<li>
									
IPSCONTENT;

if ( $counter >= 5 ):
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item'><span class='i-color_soft i-font-size_-2'>
IPSCONTENT;

$pluralize = array( \count( $category->children() ) - 4 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></a>
										
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'>
											<span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
											<span>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\cms\Records::contentCount( $subcategory ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
										</a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function Codemirror( $content, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsWidget__content ipsWidget__padding'>
	{$content}
</div>

IPSCONTENT;

		return $return;
}

	function Database( $database, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\cms\Databases\Dispatcher::i()->setDatabase( "$database->id" )->run();
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function DatabaseFilters( $database, $category, $form, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$sprintf = array($category->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_DatabaseFilters_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
	{$form}
</div>


IPSCONTENT;

		return $return;
}

	function DatabaseNavigation( $database, $categories, $currentContainer=null, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $database->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
    <nav class="ipsWidget__nav" data-navType='database' data-navId='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $database->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='cms.front.blocks.navigation'>
        
IPSCONTENT;

if ( \is_array( $categories ) ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

foreach ( $categories as $category ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "cms" )->navigationLine( $category, null, $currentContainer );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "cms" )->navigationLine( $categories, $database->_title, $currentContainer );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </nav>
</div>
IPSCONTENT;

		return $return;
}

	function databaseNavigationItems( $items ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $items as $item ):
$return .= <<<IPSCONTENT

  <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped( 'title' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function Editor( $content, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsWidget__content 
IPSCONTENT;

if ( isset($orientation) and $orientation == 'vertical' ):
$return .= <<<IPSCONTENT
i-padding_3
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	{$content}
</div>

IPSCONTENT;

		return $return;
}

	function FolderNavigation( $folders, $rootId, $pages=array(), $title=null, $currentContainer=null, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $title ):
$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='ipsWidget__content'>
    <nav class="ipsWidget__nav" data-navType='folder' data-navId='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rootId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='cms.front.blocks.navigation'>
        
IPSCONTENT;

if ( \is_array( $folders ) ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

foreach ( $folders as $folder ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "cms" )->navigationLine( $folder, null, $currentContainer );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "cms" )->navigationLine( $folders, $title, $currentContainer );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( $rootId == 0 AND \count( $pages ) ):
$return .= <<<IPSCONTENT

            <div>
                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "cms" )->folderNavigationItems( $pages );
$return .= <<<IPSCONTENT

            </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </nav>
</div>
IPSCONTENT;

		return $return;
}

	function folderNavigationItems( $items ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $items as $item ):
$return .= <<<IPSCONTENT

  <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function navigationLine( $container, $title=null, $currentContainer=null ) {
		$return = '';
		$return .= <<<IPSCONTENT

<details 
IPSCONTENT;

if ( $container->_id == $currentContainer ):
$return .= <<<IPSCONTENT
open="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-container="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<summary>
IPSCONTENT;

if ( $title ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</summary>
	
IPSCONTENT;

if ( $container->hasChildren( 'view', null, false ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $container->children( 'view', null, false ) as $child ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "cms" )->navigationLine( $child, null, $currentContainer );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div data-role='containerItems'></div>
</details>
IPSCONTENT;

		return $return;
}

	function pagebuilderoembed( $video, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-margin-inline_auto'>{$video}</div>
IPSCONTENT;

		return $return;
}

	function pagebuildertext( $text, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>{$text}</div>
IPSCONTENT;

		return $return;
}

	function pagebuilderupload( $images, $captions, $urls, $maxHeight, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( ! \is_array( $images ) ):
$return .= <<<IPSCONTENT

	<figure class="ipsFigure ipsFigure--contain" 
IPSCONTENT;

if ( $maxHeight ):
$return .= <<<IPSCONTENT
style="--_height: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px;"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		
IPSCONTENT;

if ( isset( $urls[0] ) ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $urls[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsFigure__main' aria-label="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $captions[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='ipsFigure__main'>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $images, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading="lazy">
		
IPSCONTENT;

if ( isset( $urls[0] ) ):
$return .= <<<IPSCONTENT

			</a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $captions[0] ) ):
$return .= <<<IPSCONTENT

			<figcaption class="ipsFigure__footer i-text-align_center">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $captions[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</figcaption>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</figure>


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$carouselID = 'page-builder-image-widget_' . mt_rand();
$return .= <<<IPSCONTENT

	<ul class='ipsCarousel ipsCarousel--page-builder-image-widget' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0">
		
IPSCONTENT;

foreach ( $images as $id => $image ):
$return .= <<<IPSCONTENT

			<li>
				<figure class='ipsFigure ipsFigure--contain' style="--_backdrop: url('
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'); 
IPSCONTENT;

if ( $maxHeight ):
$return .= <<<IPSCONTENT
--_height: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px;
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

if ( isset( $urls[ $id ] ) ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $urls[ $id ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsFigure__main' aria-label="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $captions[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class='ipsFigure__main'>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading="lazy">
					
IPSCONTENT;

if ( isset( $urls[ $id ] ) ):
$return .= <<<IPSCONTENT

						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $captions[ $id ] ) ):
$return .= <<<IPSCONTENT

						<figcaption class="ipsFigure__footer i-text-align_center">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $captions[ $id ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</figcaption>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</figure>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function RecordFeed( $records, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $records )  ):
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

$carouselID = 'widget--cms-record-feed_' . mt_rand();
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
 ipsData--cms-record-feed' 
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

$return .= \IPS\cms\Theme::i()->getTemplate( "listing", "cms", 'database' )->recordRow( null, null, $records, $layout );
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

	function Rss( $items, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $items )  ):
$return .= <<<IPSCONTENT

	<h3 class='ipsWidget__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		<div class='ipsWidget__content'>
			<i-data>
				<ul class='ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsData--widget-rss'>
					
IPSCONTENT;

foreach ( $items as $item ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1" target="_blank" rel="noopener"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
							<div class="ipsData__content">
								<div class='ipsData__main'>
									<div class='ipsData__title'>
										<h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
									</div>
									<div class='ipsData__meta'>
IPSCONTENT;

$val = ( $item['date'] instanceof \IPS\DateTime ) ? $item['date'] : \IPS\DateTime::ts( $item['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
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

	function Wysiwyg( $content, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsWidget__content ipsWidget__padding ipsRichText' data-controller='core.front.core.lightboxedImages'>
	{$content}
</div>

IPSCONTENT;

		return $return;
}}