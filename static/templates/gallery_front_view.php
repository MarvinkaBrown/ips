<?php
namespace IPS\Theme;
class class_gallery_front_view extends \IPS\Theme\Template
{	function comments( $image ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.front.core.commentFeed, core.front.core.ignoredComments' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $image->isLastPage() ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='comments' class="ipsEntries ipsEntries--comments ipsEntries--gallery-comments">

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery" )->featuredComments( $image->featuredComments(), $image->url()->setQueryString('tab', 'comments')->setQueryString('recommended', 'comments') );
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $image->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

		<div class='ipsButtonBar'>
			<div class='ipsButtonBar__pagination'>{$image->commentPagination( array('tab') )}</div>
			<div class='ipsButtonBar__end'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimodHeader( $image, '#comments' );
$return .= <<<IPSCONTENT
</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div data-role='commentFeed' data-controller='core.front.core.moderation'>
	<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( 'do', 'multimodComment' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
		
IPSCONTENT;

if ( \count( $image->comments( NULL, NULL, 'date', 'asc', NULL, NULL, NULL, NULL, FALSE, isset( \IPS\Widget\Request::i()->showDeleted ) ) ) ):
$return .= <<<IPSCONTENT


				
IPSCONTENT;

$commentCount=0; $timeLastRead = $image->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $image->comments( NULL, NULL, 'date', 'asc', NULL, NULL, NULL, NULL, FALSE, isset( \IPS\Widget\Request::i()->showDeleted ) ) as $comment ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$lined and $timeLastRead and $timeLastRead->getTimestamp() < $comment->mapped('date') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $lined = TRUE and $commentCount ):
$return .= <<<IPSCONTENT

							<hr class="ipsUnreadBar">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$commentCount++;
$return .= <<<IPSCONTENT

					{$comment->html()}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT



		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='ipsEmptyMessage' data-role='noComments'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $image );
$return .= <<<IPSCONTENT

			</form>
	</div>			
	
IPSCONTENT;

if ( $image->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

		<div class='ipsButtonBar ipsButtonBar--bottom'>
			<div class='ipsButtonBar__pagination'>{$image->commentPagination( array('tab') )}</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $image->commentForm() || $image->locked() || \IPS\Member::loggedIn()->restrict_post || \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] || !\IPS\Member::loggedIn()->checkPostsPerDay() ):
$return .= <<<IPSCONTENT

	<div id='replyForm' data-role='replyArea' class='ipsComposeAreaWrapper 
IPSCONTENT;

if ( !$image->canComment() ):
$return .= <<<IPSCONTENT
cTopicPostArea_noSize
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( $image->commentForm() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $image->locked() ):
$return .= <<<IPSCONTENT

				<p class='ipsComposeArea_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_locked_can_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				{$image->commentForm()}
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $image->locked() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "gallery", 'front' )->commentUnavailable( 'image_locked_cannot_comment' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->restrict_post ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "gallery", 'front' )->commentUnavailable( 'restricted_cannot_comment', \IPS\Member::loggedIn()->warnings(5,NULL,'rpa'), \IPS\Member::loggedIn()->restrict_post );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "gallery", 'front' )->commentUnavailable( 'unacknowledged_warning_cannot_post', \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( !\IPS\Member::loggedIn()->checkPostsPerDay() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "gallery", 'front' )->commentUnavailable( 'member_exceeded_posts_per_day' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function image( $image, $commentsAndReviews=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $image->container()->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $image->container() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id='elClubContainer'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div data-controller='gallery.front.view.image
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
,gallery.front.global.nsfw
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-lightboxURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<div class='ipsPull i-margin-bottom_block'>
		<div class='elGalleryHeader'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "gallery" )->imageFrame( $image );
$return .= <<<IPSCONTENT

		</div>
        
IPSCONTENT;

if ( $image->hasPreviousOrNext()  ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$prev = array_reverse( array_slice( $image->fetchNextOrPreviousImages( 9, 'ASC' ), 0, 4 ) );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$next = $image->fetchNextOrPreviousImages( 9, 'DESC' );
$return .= <<<IPSCONTENT

		<div class='cGalleryImageThumbs'>
			<ol class='ipsCarousel ipsCarousel--images ipsCarousel--gallery-image-thumbs' id='gallery-image-thumbs' tabindex="0">
                
IPSCONTENT;

$counter = 1;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $prev as $id => $carouselImage ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$imageWidth=isset( $carouselImage->_dimensions['small'][0] ) ? $carouselImage->_dimensions['small'][0] : 0;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$imageHeight=isset( $carouselImage->_dimensions['small'][1] ) ? $carouselImage->_dimensions['small'][1] : 0;
$return .= <<<IPSCONTENT

		            <li 
IPSCONTENT;

if ( $imageWidth && $imageHeight ):
$return .= <<<IPSCONTENT
style='--i-ratio:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		            	<figure class='ipsFigure'>
			                
IPSCONTENT;

if ( $carouselImage->media  ):
$return .= <<<IPSCONTENT

				                <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($carouselImage->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_video', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
				                    <video data-role="video" preload="metadata" loading="lazy"
IPSCONTENT;

if ( $carouselImage->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $carouselImage->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				                        <source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $carouselImage->original_file_name )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$carouselImage->masked_file_name  ):
$return .= <<<IPSCONTENT
#t=1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselImage->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
				                    </video>
									<i class="ipsFigure__play"></i>
				                </a>
			                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				                <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($carouselImage->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
				                	<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $carouselImage->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselImage->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				                </a>
			                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $carouselImage, FALSE );
$return .= <<<IPSCONTENT

                        </figure>
                    </li>
                    
IPSCONTENT;

$counter++;
$return .= <<<IPSCONTENT

		        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

$imageWidth=isset( $image->_dimensions['small'][0] ) ? $image->_dimensions['small'][0] : 0;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$imageHeight=isset( $image->_dimensions['small'][1] ) ? $image->_dimensions['small'][1] : 0;
$return .= <<<IPSCONTENT

                <li data-active 
IPSCONTENT;

if ( $imageWidth && $imageHeight ):
$return .= <<<IPSCONTENT
style='--i-ratio:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                	<figure class='ipsFigure'>
                        
IPSCONTENT;

if ( $image->media  ):
$return .= <<<IPSCONTENT

                            <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_video', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
                                <video data-role="video" loading="lazy" 
IPSCONTENT;

if ( $image->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 preload="metadata"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                    <source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->original_file_name )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$image->masked_file_name  ):
$return .= <<<IPSCONTENT
#t=1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
                                </video>
								<i class="ipsFigure__play"></i>
                            </a>
                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                            <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
                                <img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
                            </a>
                    	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image, FALSE );
$return .= <<<IPSCONTENT

                    </figure>
                    
IPSCONTENT;

$counter++;
$return .= <<<IPSCONTENT

                </li>

                
IPSCONTENT;

foreach ( $next as $id => $carouselImage ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$imageWidth=isset( $carouselImage->_dimensions['small'][0] ) ? $carouselImage->_dimensions['small'][0] : 0;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$imageHeight=isset( $carouselImage->_dimensions['small'][1] ) ? $carouselImage->_dimensions['small'][1] : 0;
$return .= <<<IPSCONTENT

					<li 
IPSCONTENT;

if ( $imageWidth && $imageHeight ):
$return .= <<<IPSCONTENT
style='--i-ratio:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                    	<figure class='ipsFigure'>
                            
IPSCONTENT;

if ( $carouselImage->media  ):
$return .= <<<IPSCONTENT

                                <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($carouselImage->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_video', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
                                    <video data-role="video" preload="metadata" loading="lazy" 
IPSCONTENT;

if ( $carouselImage->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $carouselImage->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                        <source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $carouselImage->original_file_name )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$carouselImage->masked_file_name  ):
$return .= <<<IPSCONTENT
#t=1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselImage->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
                                    </video>
									<i class="ipsFigure__play"></i>
                                </a>
                            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($carouselImage->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
                                    <img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $carouselImage->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselImage->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
                                </a>
                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $carouselImage, FALSE );
$return .= <<<IPSCONTENT

                        </figure>
                    </li>
                    
IPSCONTENT;

$counter++;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( $counter == 10 ):
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ol>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'gallery-image-thumbs' );
$return .= <<<IPSCONTENT

		</div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

	<section data-role='imageInfo'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", \IPS\Request::i()->app )->imageInfo( $image );
$return .= <<<IPSCONTENT

	</section>

	<div data-role='imageComments'>
	
IPSCONTENT;

if ( $commentsAndReviews ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( ( $image->directContainer()->allow_reviews && $image->container()->allow_reviews ) && ( $image->directContainer()->allow_comments && $image->container()->allow_comments )  ):
$return .= <<<IPSCONTENT

			<a id="replies"></a>
			<h2 hidden>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_feedback', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsBox ipsPull'>
			{$commentsAndReviews}
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

</div> 


IPSCONTENT;

if ( $image->container()->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function imageFrame( $image ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div id='elGalleryImage' class='elGalleryImage' data-role="imageFrame" 
IPSCONTENT;

if ( $image->data ):
$return .= <<<IPSCONTENT
data-imageSizes='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->data, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( $image->media  ):
$return .= <<<IPSCONTENT

		<div class='cGallery_videoContainer'>
			<video data-controller="core.global.core.embeddedvideo" id="elGalleryVideo" data-role="video" controls preload="auto" width="100%" height="100%"
IPSCONTENT;

if ( $image->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->original_file_name )->url;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
				<embed wmode="opaque" autoplay="true" showcontrols="true" showstatusbar="true" showtracker="true" src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->original_file_name )->url;
$return .= <<<IPSCONTENT
" width="480" height="360" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
			</video>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='cGalleryViewImage' data-role='notesWrapper' data-controller='gallery.front.view.notes' data-imageID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT
data-editable
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-notesData='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->_notes_json, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<a href='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_in_lightbox', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsLightbox>
				
IPSCONTENT;

$imageWidth=isset( $image->_dimensions['small'][0] ) ? $image->_dimensions['small'][0] : 0;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$imageHeight=isset( $image->_dimensions['small'][1] ) ? $image->_dimensions['small'][1] : 0;
$return .= <<<IPSCONTENT

				<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $imageWidth && $imageHeight ):
$return .= <<<IPSCONTENT
width="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" height="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='theImage' fetchpriority="high" decoding="sync">
			</a>
			
IPSCONTENT;

if ( \is_countable( $image->_notes ) AND \count( $image->_notes ) ):
$return .= <<<IPSCONTENT

				<noscript>
					
IPSCONTENT;

foreach ( $image->_notes as $note ):
$return .= <<<IPSCONTENT

						<div class='cGalleryNote' style='left: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['LEFT'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%; top: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['TOP'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%; width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['WIDTH'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%; height: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['HEIGHT'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%'>
							<div class='cGalleryNote_border'></div>
							<div class='cGalleryNote_note'><div>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['NOTE'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div></div>
						</div>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</noscript>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
        	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image );
$return .= <<<IPSCONTENT


		<div class='cGalleryImageFade'>
			
IPSCONTENT;

if ( $image->canEdit() OR $image->canDownloadOriginal() OR \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				<a href='#' data-ipsMenu data-ipsMenu-above='true' id='elImageTools' class='ipsButton ipsButton--overlay ipsJS_show cGalleryViewImage_controls'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_tools', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span> <i class='fa-solid fa-caret-down'></i></a>
				<ul class='ipsMenu ipsMenu_auto ipsHide' id='elImageTools_menu'>
					
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item ipsResponsive_hidePhone'><a href='#' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_image_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action='addNote'>
							<i class='fa-regular fa-pen-to-square'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_image_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
						
IPSCONTENT;

if ( !$image->media ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item ipsMenu_subItems'>
								<a href='#' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' id='elImage_rotate' data-ipsMenu data-ipsMenu-appendTo='#elGalleryImage'>
									<i class='fa-solid fa-rotate-right'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</a>
								<ul class='ipsMenu ipsMenu_auto ipsHide' id='elImage_rotate_menu'>
									<li class='ipsMenu_item'>
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( 'rotate' )->csrf()->setQueryString( 'direction', 'right' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_right', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
											<i class='fa-solid fa-rotate-right'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_right', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										</a>
									</li>
									<li class='ipsMenu_item'>
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( 'rotate' )->csrf()->setQueryString( 'direction', 'left' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_left', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
											<i class='fa-solid fa-rotate-left'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_left', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										</a>
									</li>
								</ul>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item
IPSCONTENT;

if ( $image->canSetAsAlbumCover() OR $image->canSetAsCategoryCover() ):
$return .= <<<IPSCONTENT
 ipsMenu_subItems
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

if ( $image->canSetAsAlbumCover() OR $image->canSetAsCategoryCover() ):
$return .= <<<IPSCONTENT

								<a href='#' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_image_as', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' id='elImageSetAs' data-ipsMenu data-ipsMenu-appendTo='#elGalleryImage'>
									<i class='fa-regular fa-object-group'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_image_as', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</a>
								<ul class='ipsMenu ipsMenu_auto ipsHide' id='elImageSetAs_menu'>
									
IPSCONTENT;

if ( $image->canSetAsAlbumCover() AND $image->canSetAsCategoryCover() ):
$return .= <<<IPSCONTENT

										<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'category')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_category_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'album')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_album_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'both')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_both', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

elseif ( $image->canSetAsCategoryCover() ):
$return .= <<<IPSCONTENT

										<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'category')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_category', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

elseif ( $image->canSetAsAlbumCover() ):
$return .= <<<IPSCONTENT

										<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'album')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

										<li class='ipsMenu_sep'><hr></li>
										<li class='ipsMenu_item'>
											<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url('setAsPhoto')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='setAsProfile' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
										</li>
                                        
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw and $image->canEdit() ):
$return .= <<<IPSCONTENT

                                            <li class='ipsMenu_sep'><hr></li>
                                            <li class='ipsMenu_item'>
                                                <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url('toggleNSFW')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='setAsProfile' title="
IPSCONTENT;

if ( $image->nsfw ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_nsfw_on', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_nsfw_off', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $image->nsfw ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_nsfw_on', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_nsfw_off', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
                                            </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
							
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url('setAsPhoto')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $image->canDownloadOriginal() ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url('download'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-download'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<span class='elGalleryImageNav'>
       
IPSCONTENT;

if ( $image->hasPreviousOrNext() ):
$return .= <<<IPSCONTENT

            <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( [ 'browse' => 1, 'do' => 'previous' ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='elGalleryImageNav_prev' data-action="prevMedia"><i class="fa-solid fa-arrow-left"></i></a>
            <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( [ 'browse' => 1, 'do' => 'next' ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='elGalleryImageNav_next' data-action="nextMedia"><i class="fa-solid fa-arrow-right"></i></a>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</span>
</div>
IPSCONTENT;

		return $return;
}

	function imageInfo( $image ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->contentItemMessages( $image->getMessages(), $image );
$return .= <<<IPSCONTENT


<div class='ipsBox ipsColumns ipsColumns--lines ipsPull i-margin-bottom_block'>
	<div class='ipsColumns__primary'>
		
IPSCONTENT;

if ( $image->hidden() === 1 and $image->canUnhide() ):
$return .= <<<IPSCONTENT

			<div class="ipsBox i-padding_3 i-background_2 i-margin-bottom_3">
				<p><i class='fa-solid fa-triangle-exclamation'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				<br>
				<ul class='ipsList ipsList--inline'>
					<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unhide' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--positive" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-check-circle"></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

if ( $image->canDelete() ):
$return .= <<<IPSCONTENT
				
						<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--small ipsButton--negative"><i class='fa-solid fa-xmark'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<article>
			<div data-role='imageDescription'>
				<header class='ipsPageHeader'>
					<div class="ipsPageHeader__row">
						<div class="ipsPageHeader__primary">
							<div class='ipsPageHeader__title'>
								<h1>
									
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT

										<span data-controller="core.front.core.moderation">
IPSCONTENT;

if ( $image->locked() ):
$return .= <<<IPSCONTENT
<i class='fa-solid fa-lock'></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											<span data-role="editableTitle" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
										</span>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</h1>
								<div class='ipsBadges'>
									
IPSCONTENT;

foreach ( $image->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</div>
							</div>
							
IPSCONTENT;

if ( $image->directContainer()->allow_rating ):
$return .= <<<IPSCONTENT

								<div class='ipsPageHeader__rating'>{$image->rating()}</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \count( $image->tags() ) OR ( $image->canEdit() AND $image::canTag( NULL, $image->container() ) ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tagsWithPrefix( $image->tags(), $image->prefix(), FALSE, FALSE, ( $image->canEdit() AND ( \count( $image->tags() ) OR $image::canTag( NULL, $image->container() ) ) ) ? $image->url() : NULL );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<div class='ipsButtons'>
							
IPSCONTENT;

if ( \count( $image->shareLinks() ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $image );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'gallery', 'image', $image->id, $image->followersCount() );
$return .= <<<IPSCONTENT

						</div>
					</div>
					<div class="ipsPageHeader__row ipsPageHeader__row--footer">
						<div class='ipsPageHeader__primary'>
							<div class='ipsPhotoPanel ipsPhotoPanel--inline'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $image->author(), 'mini', $image->warningRef() );
$return .= <<<IPSCONTENT

								<div class='ipsPhotoPanel__text'>
									<p class="ipsPhotoPanel__primary">
IPSCONTENT;

$htmlsprintf = array($image->author()->link( $image->warningRef() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate_itemprop', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
									<ul class='ipsPhotoPanel__secondary ipsList ipsList--sep'>
										<li>
IPSCONTENT;

$val = ( $image->date instanceof \IPS\DateTime ) ? $image->date : \IPS\DateTime::ts( $image->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
										<li>
IPSCONTENT;

$pluralize = array( $image->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
										
IPSCONTENT;

if ( $image->author()->member_id ):
$return .= <<<IPSCONTENT

											<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$image->author()->member_id}&do=content&type=gallery_image", "front", "profile_content", $image->author()->members_seo_name, 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array($image->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'find_users_images', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</ul>
								</div>
							</div>
						</div>
					</div>
				</header>
				<div class='ipsEntry__post i-padding_3'>
					
IPSCONTENT;

if ( $image->description ):
$return .= <<<IPSCONTENT

						<div class='ipsRichText ipsRichText--user' data-controller="core.front.core.lightboxedImages">
							{$image->content()}
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $image->credit_info OR $image->copyright ):
$return .= <<<IPSCONTENT

						<div class='i-margin-top_4 i-flex i-flex-wrap_wrap i-gap_2'>
							
IPSCONTENT;

if ( $image->credit_info ):
$return .= <<<IPSCONTENT

								<div class='i-flex_11'>
									<h3 class='ipsTitle ipsTitle--h6'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_credit_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
									<div>
										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->credit_info, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									</div>
								</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $image->copyright ):
$return .= <<<IPSCONTENT

								<div class='i-flex_11'>
									<h3 class='ipsTitle ipsTitle--h6'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_copyright', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
									<div>
										&copy; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->copyright, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									</div>
								</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>				
			</div>
			<footer class='ipsEntry__footer'>
				<menu class='ipsEntry__controls'>
					<li>{$image->menu()}</li>
				</menu>
				
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $image, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $image );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</footer>
		</div>
	</article>
	<div class='ipsColumns__secondary i-basis_380 i-background_2 i-padding_3' id='elGalleryImageStats'>
		<div class='' data-role="imageStats">
			
IPSCONTENT;

if ( \IPS\GeoLocation::enabled() AND $image->gps_show ):
$return .= <<<IPSCONTENT

				{$image->map( 308, 200 )}
				<div class='i-margin-top_2'>
					<div class='i-flex i-justify-content_space-between i-flex-wrap_wrap i-gap_1'>
						<div>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->loc_short, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
						
IPSCONTENT;

if ( $image->canEdit()  ):
$return .= <<<IPSCONTENT

							<div>
								<a href='#' id='elMapForm' class='i-color_soft' data-ipsMenu data-ipsMenu-closeOnClick='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'map_privacy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								<div class='ipsMenu ipsMenu_auto ipsHide i-padding_2' id='elMapForm_menu'>
									{$image->enableMapForm()}
								</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
				<hr class='ipsHr'>
			
IPSCONTENT;

elseif ( \IPS\GeoLocation::enabled() AND $image->canEdit() AND $image->gps_raw ):
$return .= <<<IPSCONTENT

				<div class='i-opacity_6'>
					{$image->map( 308, 200 )}
					<div class='i-font-size_-1 i-margin-top_2'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->loc_short, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				</div>
				<div class='i-font-size_-1 i-margin-top_2'>
					<div class='i-flex i-justify-content_space-between i-flex-wrap_wrap i-gap_1'>
						<div>
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'map_not_being_shown', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						</div>
						<div>
							<a href='#' id='elMapForm' data-ipsMenu data-ipsMenu-closeOnClick='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'map_privacy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
							<div class='ipsMenu ipsMenu_auto ipsHide i-padding_3' id='elMapForm_menu'>
								{$image->enableMapForm()}
							</div>
						</div>
					</div>
				</div>

				<hr class='ipsHr'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


            <div>
                <h2 class='ipsTitle ipsTitle--h6 i-color_soft'>
IPSCONTENT;

if ( $image->directContainer() instanceof \IPS\gallery\Album ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from_the_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from_the_category', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
                <h3 class='ipsTitle ipsTitle--h4 i-display_inline-block'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->directContainer()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->directContainer()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
                <span class='i-color_soft'>&middot; 
IPSCONTENT;

$pluralize = array( $image->directContainer()->count_imgs ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_images', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
            </div>
            <hr class='ipsHr'>

			
IPSCONTENT;

if ( \is_countable($image->metadata) AND \count($image->metadata) ):
$return .= <<<IPSCONTENT

				<div class='cGalleryExif'>
					
IPSCONTENT;

if ( isset( $image->metadata['EXIF.FocalLength'] ) || ( isset( $image->metadata['IFD0.Make'] ) AND isset( $image->metadata['IFD0.Model'] ) ) || isset( $image->metadata['EXIF.ShutterSpeedValue'] ) || isset( $image->metadata['COMPUTED.ApertureFNumber'] ) || isset( $image->metadata['Exif.Photo.ISOSpeed'] ) ):
$return .= <<<IPSCONTENT

						<h3 class='ipsTitle ipsTitle--h4 ipsTitle--margin'>
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_metadata', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h3>
						<ul class='ipsList ipsList--border ipsList--label-value'>
							
IPSCONTENT;

if ( isset( $image->metadata['IFD0.Make'] ) AND isset( $image->metadata['IFD0.Model'] ) ):
$return .= <<<IPSCONTENT

								<li>
									<i class="fa-solid fa-camera-retro" aria-hidden="true"></i>
									<strong class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_exif_camera', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
									<span class='ipsList__value'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['IFD0.Make'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['IFD0.Model'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $image->metadata['EXIF.FocalLength'] ) ):
$return .= <<<IPSCONTENT

								<li>
									<i class='fa-solid fa-arrows-left-right'></i>
									<strong class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'EXIF.FocalLength', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
									<span class='ipsList__value'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->focallength, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $image->metadata['EXIF.ExposureTime'] ) ):
$return .= <<<IPSCONTENT

								<li>
									<i class='fa-regular fa-clock'></i>
									<strong class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'EXIF.ExposureTime', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
									<span class='ipsList__value'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['EXIF.ExposureTime'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $image->metadata['COMPUTED.ApertureFNumber'] ) ):
$return .= <<<IPSCONTENT

								<li>
									<i class="fa-solid fa-expand"></i>
									<strong class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'COMPUTED.ApertureFNumber', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
									<span class='ipsList__value'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['COMPUTED.ApertureFNumber'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $image->metadata['Exif.Photo.ISOSpeed'] ) || isset( $image->metadata['EXIF.ISOSpeedRatings'] ) ):
$return .= <<<IPSCONTENT

								<li>
									<i class="fa-solid fa-camera" aria-hidden="true"></i>
									<strong class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_exif_isospeed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
									<span class='ipsList__value'>
										
IPSCONTENT;

if ( isset( $image->metadata['Exit.Photo.ISOSpeed'] ) ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['Exif.Photo.ISOSpeed'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( \is_array( $image->metadata['EXIF.ISOSpeedRatings'] ) ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( '/', $image->metadata['EXIF.ISOSpeedRatings'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['EXIF.ISOSpeedRatings'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</span>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
						<div class='i-text-align_center i-margin-top_3'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( 'metadata' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsdialog-size="narrow" data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_photo_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_photo_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class='i-text-align_center i-margin-top_3'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( 'metadata' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsdialog-size="narrow" data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_photo_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_photo_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

		</div>
	</div>
</div>

<div class='ipsBox ipsBox--padding ipsPull ipsResponsive_showPhone'>
	<div class='ipsPageActions'>
		
IPSCONTENT;

if ( \count( $image->shareLinks() ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $image, 'verySmall', 'light' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'gallery', 'image', $image->id, $image->followersCount() );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function imageLightbox( $image, $commentsAndReviews=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='gallery.front.view.image' class='cGalleryLightbox' data-role='lightbox'>
	<div class='cGalleryLightbox_inner' data-role="imageSizer">
		<div class='elGalleryHeader' class='cGalleryLightbox_image'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "gallery" )->imageLightboxFrame( $image );
$return .= <<<IPSCONTENT

		</div>

		<div class='cGalleryLightbox_info i-background_1'>
			<section data-role='imageInfo'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", \IPS\Request::i()->app )->imageLightboxInfo( $image, $commentsAndReviews );
$return .= <<<IPSCONTENT
	
			</section>
		</div>
	</div>
</div> 
IPSCONTENT;

		return $return;
}

	function imageLightboxFrame( $image ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div id='elGalleryImageLightbox' class='elGalleryImage' data-role="imageFrame" data-setHeight 
IPSCONTENT;

if ( $image->data ):
$return .= <<<IPSCONTENT
data-imageSizes='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->data, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( $image->media  ):
$return .= <<<IPSCONTENT

		<div class='cGallery_videoContainer'>
			<video data-controller="core.global.core.embeddedvideo" id="elGalleryVideo" data-role="video" data-imageID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT
data-editable
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 controls preload="auto" width="100%" height="100%"
IPSCONTENT;

if ( $image->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->original_file_name )->url;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
				<embed wmode="opaque" autoplay="true" showcontrols="true" showstatusbar="true" showtracker="true" src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->original_file_name )->url;
$return .= <<<IPSCONTENT
" width="480" height="360" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
			</video>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='cGalleryViewImage' data-role='notesWrapper' data-controller='gallery.front.view.notes' data-imageID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT
data-editable
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-notesData='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->_notes_json, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<div>
				<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='theImage' class='ipsHide'>
			</div>
			
IPSCONTENT;

if ( \count( $image->_notes ) ):
$return .= <<<IPSCONTENT

				<noscript>
					
IPSCONTENT;

foreach ( $image->_notes as $note ):
$return .= <<<IPSCONTENT

						<div class='cGalleryNote' style='left: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['LEFT'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%; top: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['TOP'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%; width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['WIDTH'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%; height: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['HEIGHT'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%'>
							<div class='cGalleryNote_border'></div>
							<div class='cGalleryNote_note'><div>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note['NOTE'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div></div>
						</div>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</noscript>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div class='cGalleryImageFade'>
			<div class='cGalleryImageTopBar'>
				<div class='cGalleryImageTitle'>
					<h1 class='ipsTitle ipsTitle--h3'>
						
IPSCONTENT;

if ( $image->prefix() OR ( $image->canEdit() AND $image::canTag( NULL, $image->container() ) AND $image::canPrefix( NULL, $image->container() ) ) ):
$return .= <<<IPSCONTENT

							<span 
IPSCONTENT;

if ( !$image->prefix() ):
$return .= <<<IPSCONTENT
class='ipsHide'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( ( $image->canEdit() AND $image::canTag( NULL, $image->container() ) AND $image::canPrefix( NULL, $image->container() ) ) ):
$return .= <<<IPSCONTENT
data-editablePrefix
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $image->prefix( TRUE ), $image->prefix() );
$return .= <<<IPSCONTENT

							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $image->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT

							<span class='' data-controller="core.front.core.moderation">
IPSCONTENT;

if ( $image->locked() ):
$return .= <<<IPSCONTENT
<i class='fa-solid fa-lock'></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="editableTitle" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							</span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span class=''>
IPSCONTENT;

if ( $image->locked() ):
$return .= <<<IPSCONTENT
<i class='fa-solid fa-lock'></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</h1>
					
IPSCONTENT;

if ( $image->album_id ):
$return .= <<<IPSCONTENT

						<div class='ipsType_desc ipsTruncate_1'>
							<strong class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from_the_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> <em><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->directContainer()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->directContainer()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></em>
							(
IPSCONTENT;

$pluralize = array( $image->directContainer()->count_imgs ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_images', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>

				<ul class='cGalleryControls ipsList ipsList--inline'>
					<li>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->promote( $image );
$return .= <<<IPSCONTENT

					</li>
					<li>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'gallery', 'image', $image->id, $image->followersCount() );
$return .= <<<IPSCONTENT

					</li>
					
IPSCONTENT;

if ( !$image->media  ):
$return .= <<<IPSCONTENT

						<li>
							<a href='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_fullsize_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--icon ipsButton--text ipsButton--small ipsButton--soft' data-ipsTooltip target='_blank' rel='noopener' data-role="toggleFullscreen"></a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>

			<div class='cGalleryImageBottomBar'>
				<div class='cGalleryCreditInfo'>
					
IPSCONTENT;

if ( $image->copyright ):
$return .= <<<IPSCONTENT

						<div>
							&copy; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->copyright, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

if ( $image->credit_info ):
$return .= <<<IPSCONTENT

						<div>
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_credit_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->credit_info, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>

				
IPSCONTENT;

if ( \count( $image->shareLinks() ) OR $image->canEdit() OR $image->canDownloadOriginal() OR \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

					<ul class='ipsList ipsList--inline'>
						
IPSCONTENT;

if ( $image->canEdit() OR $image->canDownloadOriginal() OR \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

							<li>
								<a href='#' data-ipsMenu data-ipsMenu-above='true' data-ipsMenu-appendTo='.elGalleryHeader' id='elImageToolsLightbox' class='ipsButton ipsButton--text ipsButton--small ipsJS_show cGalleryViewImage_controls'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_tools', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								<ul class='ipsMenu ipsMenu_auto ipsHide' id='elImageToolsLightbox_menu'>
									
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT

										<li class='ipsMenu_item ipsResponsive_hidePhone'><a href='#' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_image_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action='addNote'>
											<i class='fa-regular fa-pen-to-square'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_image_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
										</li>
										
IPSCONTENT;

if ( !$image->media ):
$return .= <<<IPSCONTENT

											<li class='ipsMenu_item ipsMenu_subItems'>
												<a href='#' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' id='elImageLightbox_rotate' data-ipsMenu>
													<i class='fa-solid fa-rotate-right'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

												</a>
												<ul class='ipsMenu ipsMenu_auto ipsHide' id='elImageLightbox_rotate_menu'>
													<li class='ipsMenu_item'>
														<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( 'rotate' )->csrf()->setQueryString( 'direction', 'right' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_right', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action="rotateImage">
															<i class='fa-solid fa-rotate-right'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_right', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

														</a>
													</li>
													<li class='ipsMenu_item'>
														<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( 'rotate' )->csrf()->setQueryString( 'direction', 'left' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_left', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-action="rotateImage">
															<i class='fa-solid fa-rotate-left'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rotate_left', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

														</a>
													</li>
												</ul>
											</li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		                            
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

										<li class='ipsMenu_item
IPSCONTENT;

if ( $image->canSetAsAlbumCover() OR $image->canSetAsCategoryCover() ):
$return .= <<<IPSCONTENT
 ipsMenu_subItems
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
											
IPSCONTENT;

if ( $image->canSetAsAlbumCover() OR $image->canSetAsCategoryCover() ):
$return .= <<<IPSCONTENT

												<a href='#' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_image_as', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' id='elImageLightboxSetAs' data-ipsMenu>
													<i class='fa-regular fa-object-group'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_image_as', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

												</a>
												<ul class='ipsMenu ipsMenu_auto ipsHide' id='elImageLightboxSetAs_menu'>
													
IPSCONTENT;

if ( $image->canSetAsAlbumCover() AND $image->canSetAsCategoryCover() ):
$return .= <<<IPSCONTENT

														<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'category')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_category_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
														<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'album')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_album_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
														<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'both')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_both', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
													
IPSCONTENT;

elseif ( $image->canSetAsCategoryCover() ):
$return .= <<<IPSCONTENT

														<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'category')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_category', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
													
IPSCONTENT;

elseif ( $image->canSetAsAlbumCover() ):
$return .= <<<IPSCONTENT

														<li class='ipsMenu_item'><a data-action='setAsCover' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'do', 'cover' )->setQueryString( 'set', 'album')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

														<li class='ipsMenu_sep'><hr></li>
														<li class='ipsMenu_item'>
															<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url('setAsPhoto')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='setAsProfile' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
														</li>
												</ul>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url('setAsPhoto')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action="setAsProfile">
													
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_gallery_image_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

												</a>

										</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $image->canDownloadOriginal() ):
$return .= <<<IPSCONTENT

										<li class='ipsMenu_item'>
											<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url('download'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-download'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
										</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \count( $image->shareLinks() ) ):
$return .= <<<IPSCONTENT

							<li>
								<a href='#' data-ipsMenu data-ipsMenu-above='true' id='elImageLightboxShare' class='ipsButton ipsButton--text ipsButton--small ipsJS_show'><i class='fa-solid fa-share-nodes'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_share', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								<div id='elImageLightboxShare_menu' class='ipsMenu ipsMenu_auto i-padding_3 ipsHide'>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->sharelinks( $image );
$return .= <<<IPSCONTENT

								</div>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>

	<span class='elGalleryImageNav'>
        
IPSCONTENT;

if ( $image->hasPreviousOrNext() ):
$return .= <<<IPSCONTENT

		    <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( [ 'browse' => 1, 'do' => 'previous' ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='elGalleryImageNav_prev'><i class='fa-solid fa-angle-left'></i></a>
            <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( [ 'browse' => 1, 'do' => 'next' ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='elGalleryImageNav_next'><i class='fa-solid fa-angle-right'></i></a>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</span>
</div>
IPSCONTENT;

		return $return;
}

	function imageLightboxInfo( $image, $commentsAndReviews ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-role='imageDescription' 
IPSCONTENT;

if ( $image->hidden() === -1 ):
$return .= <<<IPSCONTENT
class='ipsModerated'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->contentItemMessages( $image->getMessages(), $image );
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $image->hidden() === 1 and $image->canUnhide() ):
$return .= <<<IPSCONTENT

		<div class="ipsModerated i-padding_3">
			<p><i class='fa-solid fa-triangle-exclamation'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'image_pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<br>
			<ul class='ipsList ipsList--inline'>
				<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unhide' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--positive" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-check-circle"></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

if ( $image->canDelete() ):
$return .= <<<IPSCONTENT
				
					<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--small ipsButton--negative"><i class='fa-solid fa-xmark'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class='ipsPhotoPanel ipsPhotoPanel--mini i-background_2 i-padding_3'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $image->author(), 'mini', $image->warningRef() );
$return .= <<<IPSCONTENT

		<div>
			<p class='ipsTitle ipsTitle--h3'>
				{$image->author()->link( $image->warningRef() )}
			</p>
			<ul class='ipsList ipsList--inline i-link-color_inherit i-color_soft'>
				<li>
IPSCONTENT;

$val = ( $image->date instanceof \IPS\DateTime ) ? $image->date : \IPS\DateTime::ts( $image->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
				<li>
IPSCONTENT;

$pluralize = array( $image->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

if ( $image->canReportOrRevoke() === TRUE ):
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url('report'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id or \IPS\Helpers\Form\Captcha::supportsModal() ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsDialog-remoteSubmit data-ipsDialog-flashMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_submit_success', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		
IPSCONTENT;

if ( ( $image->canEdit() or $image->canFeature() or $image->canPin() or $image->canHide() or $image->canUnhide() or $image->canMove() or $image->canLock() or $image->canUnlock() or $image->canDelete() or $image->canChangeAuthor() ) or ( $image->hidden() == -2 AND \IPS\Member::loggedIn()->modPermission('can_manage_deleted_content') ) ):
$return .= <<<IPSCONTENT

			<div>
				<a href='#elImageActions_menu' id='elImageLightboxActions' class='ipsButton ipsButton--inherit ipsButton--small ipsButton--icon' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'manage_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsMenu><i class='fa-solid fa-gear i-font-size_2'></i> <i class='fa-solid fa-caret-down'></i></a>
				<ul id='elImageLightboxActions_menu' class='ipsMenu ipsMenu_auto ipsHide'>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_manage_deleted_content') AND $image->hidden() == -2 ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'restore' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_visible_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_visible', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'restoreAsHidden' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_hidden_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete', 'immediate' => 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_immediately_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_immediately', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canChangeAuthor() ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( array( 'do' => 'changeAuthor' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_author', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_author_ititle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_author', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT
				
							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( array( 'do' => 'edit' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_edit_details_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsDialog data-action="editImage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_edit_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canPin() ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'pin' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pin_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canUnpin() ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unpin' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unpin_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unpin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canHide() ):
$return .= <<<IPSCONTENT
				
							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( array( 'do' => 'moderate', 'action' => 'hide' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canUnhide() ):
$return .= <<<IPSCONTENT
				
							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unhide' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

if ( $image->hidden() === 1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unhide_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $image->hidden() === 1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unhide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canLock() ):
$return .= <<<IPSCONTENT
				
							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'lock' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'lock_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'lock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canUnlock() ):
$return .= <<<IPSCONTENT
				
							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unlock' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unlock_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unlock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canMove() ):
$return .= <<<IPSCONTENT
				
							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( array( 'do' => 'move' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'move', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"  title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_move_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'move', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canDelete() ):
$return .= <<<IPSCONTENT
				
							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm  title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_title_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->canOnMessage( 'add' ) ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( array( 'do' => 'messageForm' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_view_moderation_log') ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_sep'><hr></li>
							<li class="ipsMenu_item"><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( array( 'do' => 'modLog' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

	<div class='i-padding_3'>
		
IPSCONTENT;

if ( $image->directContainer()->allow_rating ):
$return .= <<<IPSCONTENT

			<div class='i-margin-bottom_2'>{$image->rating('small')}</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( \count( $image->tags() ) OR ( $image->canEdit() AND $image::canTag( NULL, $image->container() ) ) ):
$return .= <<<IPSCONTENT

			<div class=''>
                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $image->tags(), FALSE, FALSE, ( $image->canEdit() AND ( \count( $image->tags() ) OR $image::canTag( NULL, $image->container() ) ) ) ? $image->url() : NULL );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $image->description ):
$return .= <<<IPSCONTENT

			<div class='ipsRichText i-margin-block_2' data-controller="core.front.core.lightboxedImages" data-ipsTruncate>
				{$image->content()}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $image, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $image );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

	
IPSCONTENT;

$return .= \IPS\core\Advertisement::loadByLocation( 'ad_image_lightbox' );
$return .= <<<IPSCONTENT


	<div class='cGalleryExif i-background_2 i-padding_3'>
		
IPSCONTENT;

if ( \count($image->metadata) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $image->metadata['EXIF.FocalLength'] ) || ( isset( $image->metadata['IFD0.Make'] ) AND isset( $image->metadata['IFD0.Model'] ) ) || isset( $image->metadata['EXIF.ShutterSpeedValue'] ) || isset( $image->metadata['COMPUTED.ApertureFNumber'] ) || isset( $image->metadata['Exif.Photo.ISOSpeed'] ) ):
$return .= <<<IPSCONTENT

				<h3 class='i-font-size_-2'>
					
IPSCONTENT;

if ( isset( $image->metadata['IFD0.Make'] ) AND isset( $image->metadata['IFD0.Model'] ) ):
$return .= <<<IPSCONTENT

						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_exif_camera', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( mb_strpos( $image->metadata['IFD0.Model'], $image->metadata['IFD0.Make'] ) !== 0 ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['IFD0.Make'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['IFD0.Model'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'camera_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</h3>
				<ul class='ipsList ipsList--inline cGalleryExif_data'>
					
IPSCONTENT;

if ( isset( $image->metadata['EXIF.FocalLength'] ) ):
$return .= <<<IPSCONTENT

						<li data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'EXIF.FocalLength', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
							<span><i class='fa-solid fa-arrows-left-right'></i></span>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->focallength, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $image->metadata['EXIF.ExposureTime'] ) ):
$return .= <<<IPSCONTENT

						<li data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'EXIF.ExposureTime', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
							<span><i class='fa-regular fa-clock'></i></span>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['EXIF.ExposureTime'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $image->metadata['COMPUTED.ApertureFNumber'] ) ):
$return .= <<<IPSCONTENT

						<li data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'COMPUTED.ApertureFNumber', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
							<span class='cGalleryExif_f'>f</span>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['COMPUTED.ApertureFNumber'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $image->metadata['Exif.Photo.ISOSpeed'] ) || isset( $image->metadata['EXIF.ISOSpeedRatings'] ) ):
$return .= <<<IPSCONTENT

						<li data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_exif_isospeed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
							<span class='cGalleryExif_iso'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_exif_iso', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

if ( isset( $image->metadata['Exit.Photo.ISOSpeed'] ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['Exif.Photo.ISOSpeed'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \is_array( $image->metadata['EXIF.ISOSpeedRatings'] ) ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( '/', $image->metadata['EXIF.ISOSpeedRatings'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->metadata['EXIF.ISOSpeedRatings'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( 'metadata' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-fixed="true" data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_photo_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='i-font-size_-2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_photo_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( 'metadata' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-fixed="true" data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_photo_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='i-font-size_-2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_photo_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( \IPS\GeoLocation::enabled() AND $image->gps_show ):
$return .= <<<IPSCONTENT

			<div class='i-margin-top_2'>
				{$image->map( 400, 100 )}
				<div class='i-font-size_-2 i-margin-top_2'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->loc_short, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $image->canEdit()  ):
$return .= <<<IPSCONTENT

						<a href='#' id='elMapLightboxForm' data-ipsMenu data-ipsMenu-closeOnClick='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'map_privacy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
						<div class='ipsMenu ipsMenu_auto ipsHide i-padding_3' id='elMapLightboxForm_menu'>
							{$image->enableMapForm( TRUE )}
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

elseif ( $image->canEdit() AND \IPS\GeoLocation::enabled() AND $image->gps_raw ):
$return .= <<<IPSCONTENT

			<div class='i-margin-top_2'>
				<div class='i-opacity_4'>
					{$image->map( 400, 100 )}
					<div class='i-font-size_-2 i-margin-top_2'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->loc_short, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				</div>
				<div class='i-font-size_-2 i-margin-top_3'>
					<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'map_not_being_shown', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					<a href='#' id='elMapLightboxForm' data-ipsMenu data-ipsMenu-closeOnClick='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'map_privacy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
					<div class='ipsMenu ipsMenu_auto ipsHide i-padding_3' id='elMapLightboxForm_menu'>
						{$image->enableMapForm( TRUE )}
					</div>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

	<div data-role='imageComments' data-commentsContainer="lightbox">
		
IPSCONTENT;

if ( $commentsAndReviews ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ( $image->directContainer()->allow_reviews && $image->container()->allow_reviews ) && ( $image->directContainer()->allow_comments && $image->container()->allow_comments )  ):
$return .= <<<IPSCONTENT

				<a id="replies"></a>
				<h2 class='ipsHide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_feedback', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			{$commentsAndReviews}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function metadata( $image ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('gallery_metadata', FALSE, array( 'sprintf' => array( $image->caption ) ) ) );
endif;
$return .= <<<IPSCONTENT

<div class='i-padding_2'>
	
IPSCONTENT;

if ( \count( $image->metadata ) ):
$return .= <<<IPSCONTENT

		<ol class='ipsList ipsList--border ipsList--label-value'>
			
IPSCONTENT;

foreach ( $image->metadata as $key => $value ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !\is_array( $value ) AND ( $image->gps_show OR mb_strpos( $key, 'GPS.' ) === FALSE ) AND ( mb_strpos( $key, 'UndefinedTag' ) === FALSE OR \IPS\Member::loggedIn()->language()->checkKeyExists( $key ) ) AND ( !\IPS\Member::loggedIn()->language()->checkKeyExists( $key ) OR ( \IPS\Member::loggedIn()->language()->checkKeyExists( $key ) AND \IPS\Member::loggedIn()->language()->get( $key ) ) ) ):
$return .= <<<IPSCONTENT

					<li>
						<strong class='ipsList__label'>
IPSCONTENT;

$val = "{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						<span class='ipsList__value'>
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists( $key . '_map_' . $value ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$key}_map_{$value}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_no_metadata', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function reviews( $image ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.front.core.commentFeed' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-commentsType='reviews' data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $image->isLastPage('reviews') ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->reviewFeedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='reviews' class="ipsEntries ipsEntries--reviews ipsEntries--gallery-reviews">
	
IPSCONTENT;

if ( \count( $image->reviews( NULL, NULL, NULL, 'desc', NULL, NULL, NULL, NULL, isset( \IPS\Widget\Request::i()->showDeleted ) ) ) ):
$return .= <<<IPSCONTENT


		<div class='ipsButtonBar ipsButtonBar--top'>
			
IPSCONTENT;

if ( $image->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

				<div class='ipsButtonBar__pagination'>{$image->reviewPagination( array( 'tab', 'sort' ) )}</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end'>
				<ul class="ipsDataFilters">
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( array( 'tab' => 'reviews', 'sort' => 'helpful' ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->sort ) or \IPS\Widget\Request::i()->sort != 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'most_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( array( 'tab' => 'reviews', 'sort' => 'newest' ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->sort ) and \IPS\Widget\Request::i()->sort == 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'newest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				</ul>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimodHeader( $image, '#reviews', 'review' );
$return .= <<<IPSCONTENT

			</div>
		</div>

		<div data-role='commentFeed' data-controller='core.front.core.moderation'>
			<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->csrf()->setQueryString( 'do', 'multimodReview' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
				
IPSCONTENT;

$reviewCount=0; $timeLastRead = $image->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $image->reviews( NULL, NULL, NULL, 'desc', NULL, NULL, NULL, NULL, isset( \IPS\Widget\Request::i()->showDeleted ) ) as $review ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$lined and $timeLastRead and $timeLastRead->getTimestamp() < $review->mapped('date') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $lined = TRUE and $reviewCount ):
$return .= <<<IPSCONTENT

							<hr class="ipsUnreadBar">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$reviewCount++;
$return .= <<<IPSCONTENT

					{$review->html()}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $image, 'review' );
$return .= <<<IPSCONTENT

			</form>
		</div>
		
IPSCONTENT;

if ( $image->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar ipsButtonBar--bottom'>
				<div class='ipsButtonBar__pagination'>{$image->reviewPagination( array( 'tab', 'sort' ) )}</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( !$image->canReview() ):
$return .= <<<IPSCONTENT

		<div class='ipsBox__padding'>
			<p class='ipsEmptyMessage' data-role='noReviews'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

if ( $image->reviewForm() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $image->locked() ):
$return .= <<<IPSCONTENT

			<strong class='i-color_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'item_locked_can_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div id='elImageReviewForm'>
			{$image->reviewForm()}
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $image->hasReviewed() ):
$return .= <<<IPSCONTENT

			<!-- Already reviewed -->
		
IPSCONTENT;

elseif ( $image->locked() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "gallery", 'front' )->commentUnavailable( 'item_locked_cannot_review' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( \IPS\Member::loggedin()->restrict_post ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->restrict_post == -1 ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "gallery", 'front' )->reviewUnavailable( 'restricted_cannot_comment' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "gallery", 'front' )->reviewUnavailable( 'restricted_cannot_comment', \IPS\Member::loggedIn()->warnings(5,NULL,'rpa'), \IPS\Member::loggedIn()->restrict_post );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "gallery", 'front' )->reviewUnavailable( 'unacknowledged_warning_cannot_post', \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function rssContent( $image ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$image->description}
<p>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></a>
</p>
IPSCONTENT;

		return $return;
}}