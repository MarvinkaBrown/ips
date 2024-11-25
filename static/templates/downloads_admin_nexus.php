<?php
namespace IPS\Theme;
class class_downloads_admin_nexus extends \IPS\Theme\Template
{	function fileInfo( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-background_2 i-padding_3 i-margin-bottom_2 i-border-radius_box'>
	<div class='ipsData__item'>
		<a href="$file->url()" class='ipsData__image'>
			
IPSCONTENT;

if ( $file->primary_screenshot_thumb ):
$return .= <<<IPSCONTENT

				<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Attachment", $file->primary_screenshot_thumb )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</a>
		<div class='ipsData__main'>
			<h4 class='ipsData__title ipsType_sectionHead'>
				
IPSCONTENT;

if ( $file->prefix() ):
$return .= <<<IPSCONTENT

					<span>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->prefix( $file->prefix( TRUE ), $file->prefix() );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<span><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($file->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></span>
			</h4>
			<p class='i-color_soft i-link-color_inherit'>
				
IPSCONTENT;

$htmlsprintf = array($file->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Widget\Request::i()->app != 'downloads' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
			<div class='i-font-size_1 ipsRichText ipsTruncate_2 i-margin-top_1'>
				{$file->truncated()}
			</div>
		</div>
		<div class='i-basis_220'>
			
IPSCONTENT;

if ( $file->container()->bitoptions['reviews'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $file->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $file->memberReviewRating() );
$return .= <<<IPSCONTENT
&nbsp;&nbsp; <span class='i-color_soft'>(
IPSCONTENT;

$pluralize = array( $file->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $file->container()->bitoptions['comments'] ):
$return .= <<<IPSCONTENT

				<p>
					
IPSCONTENT;

if ( $file->comments ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( 'tab', 'comments' )->setFragment('replies'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<i class='fa-solid fa-comment'></i> 
IPSCONTENT;

$pluralize = array( $file->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $file->comments ):
$return .= <<<IPSCONTENT

						</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<p class='i-font-size_1'><strong>
IPSCONTENT;

if ( $file->updated == $file->submitted ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submitted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $file->submitted instanceof \IPS\DateTime ) ? $file->submitted : \IPS\DateTime::ts( $file->submitted );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $file->updated instanceof \IPS\DateTime ) ? $file->updated : \IPS\DateTime::ts( $file->updated );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</strong></p>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function itemResultTemplate( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-itemid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role='contentItemRow'>
	<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
	
IPSCONTENT;

if ( $item->container() ):
$return .= <<<IPSCONTENT

		<em>
IPSCONTENT;

$sprintf = array($item->container()->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'item_selector_added_to_container', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</em>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<br>
	<span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 </span> &middot; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->price(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

</div>


IPSCONTENT;

		return $return;
}}