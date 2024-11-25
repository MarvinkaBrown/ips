<?php
namespace IPS\Theme;
class class_nexus_front_global extends \IPS\Theme\Template
{	function commentTableHeader( $comment, $package, $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-flex i-gap_2'>
	<div class='i-flex_00 i-basis_60'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->thumbImage( $package->image, $package->_title, 'small', '', 'view_this', $package->url() );
$return .= <<<IPSCONTENT

	</div>
	<div class='i-flex_11'>
		<h3 class='ipsTitle ipsTitle--h3'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role='productLink'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
		<p class='i-color_soft i-link-color_inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></p>
		<div class='ipsTruncate_1'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockPrice( $package->fullPriceInfo() );
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedProduct( $item, $renewalTerm=NULL, $url=NULL, $image=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$asNode = \IPS\nexus\Package::load( $item->id );
$return .= <<<IPSCONTENT

<div class='ipsRichEmbed ipsRichEmbed--commerce-product cNexusEmbed'>
	<div class='ipsRichEmbed_header'>
		<div>
			<p class='i-font-weight_600 i-color_hard ipsTruncate_1'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'a_product_in_our_store', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</p>
			<p class='i-color_soft i-link-color_inherit ipsTruncate_1'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</p>
		</div>
		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_openItem'><i class='fa-solid fa-arrow-up-right-from-square'></i></a>
	</div>
	
IPSCONTENT;

if ( $images = $item->images() and \count( $images ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $images as $image ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead' data-imageID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading="lazy">
			</a>
			
IPSCONTENT;

break;
$return .= <<<IPSCONTENT
	
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='ipsRichEmbed_masthead'></div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_itemTitle ipsTruncate_1'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $item, 'IPS\Content\Ratings' ) and $rating = $item->averageRating() ):
$return .= <<<IPSCONTENT

				&nbsp;&nbsp;
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'large', $rating, 5 );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( isset( $item::$reviewClass ) AND $rating = $item->averageReviewRating() ):
$return .= <<<IPSCONTENT

				&nbsp;&nbsp;
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'large', $rating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT
<span class='i-color_soft'>
IPSCONTENT;

if ( $item->reviews ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$pluralize = array( $item->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from_num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_reviews_yet', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockPrice( $asNode->fullPriceInfo() );
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $asNode->stock === 0 ):
$return .= <<<IPSCONTENT

			<span class='ipsButton ipsButton--primary ipsButton--small ipsButton--wide ipsButton--disabled i-cursor_not-allowed'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'out_of_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $asNode->url()->setQueryString( 'purchase', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--wide ipsButton--small' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'store_quick_view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-forceReload>
				<i class='fa-solid fa-cart-shopping'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_to_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $desc = $item->truncated(TRUE) ):
$return .= <<<IPSCONTENT

			<div class='ipsRichEmbed__snippet'>
				{$desc}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedProductReview( $comment, $item, $renewalTerm, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$asNode = \IPS\nexus\Package::load( $item->id );
$return .= <<<IPSCONTENT

<div class='ipsRichEmbed ipsRichEmbed--commerce-review cNexusEmbed'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, \IPS\Member::loggedIn()->language()->addToStack( 'x_reviewed_product', FALSE, array( 'sprintf' => array( $comment->author()->name ) ) ), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			<div>
				
IPSCONTENT;

if ( $images = $item->images() and \count( $images ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $images as $image ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsRichEmbed_masthead'>
							<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
						</a>
						
IPSCONTENT;

break;
$return .= <<<IPSCONTENT
	
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class='ipsRichEmbed_masthead'></div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='i-padding_3'>
					<h3 class='ipsRichEmbed_itemTitle ipsTruncate_1'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					</h3>
					<div>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockPrice( $asNode->fullPriceInfo() );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsRichEmbed__snippet'>
						{$item->truncated(TRUE)}
					</div>
				</div>
			</div>
		</div>

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'veryLarge', $comment->mapped('rating') );
$return .= <<<IPSCONTENT
 
		
IPSCONTENT;

if ( $comment->mapped('votes_total') ):
$return .= <<<IPSCONTENT

			<p>{$comment->helpfulLine()}</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \count( $comment->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline i-margin-top_2'>
				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $comment, TRUE, 'small' );
$return .= <<<IPSCONTENT

				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function mobileFooterBar(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li data-el='cart' class="ipsMobileFooter__item" id="elCart_mobileFooterContainer" 
IPSCONTENT;

if ( !(isset( $_SESSION['cart'] ) and \count( $_SESSION['cart'] )) ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsMobileFooter__link">
		<span class="ipsMobileFooter__icon">
			<svg xmlns="http://www.w3.org/2000/svg" height="16" width="18" viewBox="0 0 576 512"><path d="M253.3 35.1c6.1-11.8 1.5-26.3-10.2-32.4s-26.3-1.5-32.4 10.2L117.6 192H32c-17.7 0-32 14.3-32 32s14.3 32 32 32L83.9 463.5C91 492 116.6 512 146 512H430c29.4 0 55-20 62.1-48.5L544 256c17.7 0 32-14.3 32-32s-14.3-32-32-32H458.4L365.3 12.9C359.2 1.2 344.7-3.4 332.9 2.7s-16.3 20.6-10.2 32.4L404.3 192H171.7L253.3 35.1zM192 304v96c0 8.8-7.2 16-16 16s-16-7.2-16-16V304c0-8.8 7.2-16 16-16s16 7.2 16 16zm96-16c8.8 0 16 7.2 16 16v96c0 8.8-7.2 16-16 16s-16-7.2-16-16V304c0-8.8 7.2-16 16-16zm128 16v96c0 8.8-7.2 16-16 16s-16-7.2-16-16V304c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
		</span>
		<span class='ipsNotification'>
			
IPSCONTENT;

if ( isset( $_SESSION['cart'] ) and \count( $_SESSION['cart'] )  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\nexus\Application::cartCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				0
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</span>
		<span class="ipsMobileFooter__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'your_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
	</a>
</li>
IPSCONTENT;

		return $return;
}

	function mobileHeaderBar(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $_SESSION['cart'] ) and \count( $_SESSION['cart'] )  ):
$return .= <<<IPSCONTENT

    <li data-el='cart'>
        <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsMobileNavIcons__button">
            <svg xmlns="http://www.w3.org/2000/svg" height="16" width="18" viewBox="0 0 576 512"><path d="M253.3 35.1c6.1-11.8 1.5-26.3-10.2-32.4s-26.3-1.5-32.4 10.2L117.6 192H32c-17.7 0-32 14.3-32 32s14.3 32 32 32L83.9 463.5C91 492 116.6 512 146 512H430c29.4 0 55-20 62.1-48.5L544 256c17.7 0 32-14.3 32-32s-14.3-32-32-32H458.4L365.3 12.9C359.2 1.2 344.7-3.4 332.9 2.7s-16.3 20.6-10.2 32.4L404.3 192H171.7L253.3 35.1zM192 304v96c0 8.8-7.2 16-16 16s-16-7.2-16-16V304c0-8.8 7.2-16 16-16s16 7.2 16 16zm96-16c8.8 0 16 7.2 16 16v96c0 8.8-7.2 16-16 16s-16-7.2-16-16V304c0-8.8 7.2-16 16-16zm128 16v96c0 8.8-7.2 16-16 16s-16-7.2-16-16V304c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
		    <span class='ipsNotification'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\nexus\Application::cartCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		    <span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'your_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
	    </a>
    </li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function referralRulesCommission( $rules ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->nexus_com_rules AND \count( $rules ) ):
$return .= <<<IPSCONTENT

<div class="ipsBox i-margin-bottom_3">
	<div class="i-background_2 i-padding_3">
		<strong><i class="fa-solid fa-basket-shopping"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'referrals_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
	</div>
	<i-data>
		<ul class="ipsData ipsData--table ipsData--referrals">
			
IPSCONTENT;

foreach ( $rules as $rule ):
$return .= <<<IPSCONTENT

			<li class='ipsData__item'>
				<div class='i-flex_00'>
					<span class='cReferralBadge'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rule->commission, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%</span>
				</div>
				<div class='ipsData__main'>
					<strong class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rule->description(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
					
IPSCONTENT;

$limit = $rule->commissionLimit();
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $rule->purchase_packages or $limit != '' ):
$return .= <<<IPSCONTENT
<p class='i-color_soft'>(
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $rule->purchase_packages ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $rule->purchase_package_limit ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ref_comm_limit_yes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ref_comm_limit_no', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $rule->commission_limit ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rule->commissionLimit(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $rule->purchase_packages or $limit != '' ):
$return .= <<<IPSCONTENT
)</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

	function searchResultProductSnippet( $indexData, $itemData, $image, $url, $priceInfo, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--commerce'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->thumbImage( $image, $indexData['index_title'], 'medium', '', 'view_this', $url, 'nexus_Products', '', true );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$condensed ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-content ipsStreamItem__content-content--commerce'>			
		<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_3'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class='ipsRichText' data-searchable data-findTerm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

$val = "nexus_package_{$indexData['index_item_id']}_desc"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>			
		<div class='cNexusPrice'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockPrice( $priceInfo );
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userNav(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $_SESSION['cart'] ) and \count( $_SESSION['cart'] ) ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$subtotal = new \IPS\Math\Number('0');
$return .= <<<IPSCONTENT

    <li data-el='cart' id='elCart_container'>
        <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsUserNav__link" id='elCart' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'your_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsMenu data-ipsMenu-closeOnClick='false'>
            <i class="fa-solid fa-basket-shopping ipsUserNav__icon"></i>
            <span class='ipsNotification'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\nexus\Application::cartCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
            <span class="ipsUserNav__text ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
        </a>
        <div id='elCart_menu' class='ipsMenu ipsMenu_wide ipsHide'>
            <div class="ipsMenu_headerBar"><h4 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'your_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4></div>
            <div class='ipsMenu_innerContent'>
                <i-data>
                    <ul class="ipsData ipsData--table ipsData--compact ipsData--cart-dropdown" data-role='cartList' id='elCartContent'>
                        
IPSCONTENT;

foreach ( $_SESSION['cart'] as $id => $item ):
$return .= <<<IPSCONTENT

                            <li class='ipsData__item'>
                                <figure class='ipsData__image'>
                                    
IPSCONTENT;

if ( $image = $item->image() ):
$return .= <<<IPSCONTENT

                                        <img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
                                    
IPSCONTENT;

elseif ( $icon = $item::$icon ):
$return .= <<<IPSCONTENT

                                        <i class='fa fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
                                    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                        <i></i>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                </figure>
                                <div class='ipsData__main'>
                                    <div class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
                                    <p class="ipsData__meta">&times;
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
                                </div>
                                <div class='cNexusPrice'>
                                    <strong>
                                        
IPSCONTENT;

$location = NULL;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( \IPS\Settings::i()->nexus_show_tax ):
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

$location = $location ?: \IPS\nexus\Customer::loggedIn()->estimatedLocation();
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

$linePrice = $item->grossLinePrice( $location );
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

$linePrice = $item->linePrice();
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $linePrice, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

$subtotal = $subtotal->add( $linePrice->amount );
$return .= <<<IPSCONTENT

                                    </strong>
                                    
IPSCONTENT;

if ( $item->quantity > 1 ):
$return .= <<<IPSCONTENT

                                        <p class='i-font-size_-2 i-color_soft'>
                                            
IPSCONTENT;

$sprintf = array($item->price); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'each_short', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

                                        </p>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                </div>
                            </li>
                        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                        <li class='ipsData__item cNexusMenuCart_totalRow'>
                            <div class='ipsData__main i-font-size_2 i-text-align_end'>
                                <strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subtotal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
                            </div>
                            <div class='i-basis_100 cNexusPrice i-font-size_2 i-text-align_end'>
                                <strong>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $subtotal, ( ( isset( \IPS\Request::i()->cookie['currency'] ) and \in_array( \IPS\Request::i()->cookie['currency'], \IPS\nexus\Money::currencies() ) ) ? \IPS\Request::i()->cookie['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency() ) );
$return .= <<<IPSCONTENT
</strong>
                            </div>
                        </li>
                    </ul>
                </i-data>
            </div>
            <div class='ipsMenu_footerBar i-text-align_center'>
                <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_and_checkout', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
            </div>
        </div>
    </li>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <li class='ipsHide' id='elCart_container'></li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}