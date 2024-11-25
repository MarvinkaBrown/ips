<?php
namespace IPS\Theme;
class class_nexus_front_widgets extends \IPS\Theme\Template
{	function bestSellers( $packages, $layout='grid-carousel', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $packages )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_latestProducts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget--commerce-best-sellers_' . mt_rand();
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
 ipsData--commerce-best-sellers' 
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

foreach ( $packages as $package ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockWidget( $package, TRUE );
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

	function donations( $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class="ipsWidget__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'current_donation_goals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
	<i-data>
		<ul class='ipsData ipsData--table ipsData--donations'>
			
IPSCONTENT;

foreach ( \IPS\nexus\Donation\Goal::roots() as $goal ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div class='ipsData__main'>
						<div class="i-flex i-align-items_center">
							<div class='ipsData__title i-flex_11'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
							
IPSCONTENT;

if ( $goal->goal ):
$return .= <<<IPSCONTENT

							<span class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( 100/$goal->goal*$goal->current, 0, '.', ''), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						
IPSCONTENT;

if ( $goal->goal ):
$return .= <<<IPSCONTENT

							<progress class="ipsProgress i-margin-top_2" value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( 100/$goal->goal*$goal->current, 0, '.', ''), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' max="100"></progress>
							<div class="i-color_soft i-font-size_-1 i-margin-top_1">
								
IPSCONTENT;

$sprintf = array(new \IPS\nexus\Money( $goal->current, $goal->currency ), new \IPS\nexus\Money( $goal->goal, $goal->currency )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'donate_progress', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<div class="i-color_soft i-font-size_-1">
								
IPSCONTENT;

$sprintf = array(new \IPS\nexus\Money( $goal->current, $goal->currency )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'donate_sofar', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class="i-flex i-justify-content_space-between">
							
IPSCONTENT;

if ( $goal->current ):
$return .= <<<IPSCONTENT

								<ul class='ipsCaterpillar'>
									
IPSCONTENT;

foreach ( $goal->donors( \IPS\nexus\Donation\Goal::DONATE_PUBLIC, 5, FALSE ) as $member ):
$return .= <<<IPSCONTENT

									<li>
										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'mini' );
$return .= <<<IPSCONTENT

									</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$count = $goal->donors( \IPS\nexus\Donation\Goal::DONATE_PUBLIC + \IPS\nexus\Donation\Goal::DONATE_ANONYMOUS, NULL, TRUE );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $count > 5 ):
$return .= <<<IPSCONTENT

									<!-- @todo Ehren - If the count is 5 we need to stack another avatar circle with the extra count -->
										<li>+
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $count - 5, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary ipsButton--small'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'commerce_donate_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right"></i></a>
							
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
	</i-data>
</div>
IPSCONTENT;

		return $return;
}

	function featuredProduct( $packages, $layout='grid-carousel', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $packages )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_featuredProduct', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget--commerce-featured-products_' . mt_rand();
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
 ipsData--commerce-featured-products' 
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

foreach ( $packages as $package ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockWidget( $package, TRUE, TRUE );
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

	function latestProducts( $packages, $layout='grid-carousel', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $packages )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_latestProducts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget--commerce-latest-products_' . mt_rand();
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
 ipsData--commerce-latest-products' 
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

foreach ( $packages as $package ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockWidget( $package, TRUE );
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

	function pendingActions( $pendingTransactions, $pendingWithdrawals, $pendingAdvertisements, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


<h3 class="ipsWidget__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_pendingActions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<i-data>
	
IPSCONTENT;

if ( $pendingTransactions OR $pendingWithdrawals OR $pendingAdvertisements ):
$return .= <<<IPSCONTENT

		<ul class="ipsData ipsData--table ipsData--pending-actions" id='elNexusActions'>
			
IPSCONTENT;

if ( $pendingTransactions !== NULL ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=transactions&filter=tstatus_hold", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_transactions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					<span class='cNexusActionBadge 
IPSCONTENT;

if ( $pendingTransactions < 1 ):
$return .= <<<IPSCONTENT
cNexusActionBadge_off
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $pendingTransactions > 99 ):
$return .= <<<IPSCONTENT
99+
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $pendingTransactions );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
					<div class='ipsData__main'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_transactions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $pendingWithdrawals !== NULL ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=payouts&filter=postatus_pend", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_widthdrawals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						<span class='cNexusActionBadge 
IPSCONTENT;

if ( $pendingWithdrawals < 1 ):
$return .= <<<IPSCONTENT
cNexusActionBadge_off
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $pendingWithdrawals > 99 ):
$return .= <<<IPSCONTENT
99+
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $pendingWithdrawals );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
						<div class='ipsData__main'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_widthdrawals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</div>
					</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $pendingAdvertisements !== NULL ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=promotion&controller=advertisements", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_advertisements', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						<span class='cNexusActionBadge 
IPSCONTENT;

if ( $pendingAdvertisements < 1 ):
$return .= <<<IPSCONTENT
cNexusActionBadge_off
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $pendingAdvertisements > 99 ):
$return .= <<<IPSCONTENT
99+
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $pendingAdvertisements );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
						<div class='ipsData__main'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_advertisements', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</div>
					</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class="ipsEmpty">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_pending_items', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</i-data>
IPSCONTENT;

		return $return;
}

	function recentCommerceReviews( $reviews, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsWidget__header'>
	<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_recentCommerceReviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$carouselID = 'widget-recent-commerce-reviews_' . mt_rand();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</header>
<div class='ipsWidget__content'>
	
IPSCONTENT;

if ( !empty( $reviews )  ):
$return .= <<<IPSCONTENT

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
 ipsData--widget-recent-commerce-reviews' 
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

foreach ( $reviews as $review ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$package =  \IPS\nexus\Package::load( $review->item()->id );
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsData__image'>
							
IPSCONTENT;

if ( $package->image ):
$return .= <<<IPSCONTENT

								<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
						<div class="ipsData__content">
							<div class='ipsData__main'>
								<h4 class='ipsData__title'>
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($review->item()->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_product', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
								</h4>
								<div class="i-flex i-gap_2">
									<div class='cNexusWidgetPrice'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->price(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $review->rating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT

									<span class='i-color_soft'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->item()->reviews, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
								</div>				
								<div class='ipsData__desc ipsRichText'>
									{$review->truncated(TRUE, 100)}
								</div>
							</div>
							<div class="ipsData__last">
								<div class="ipsData__last-text">
									<div class="ipsData__last-primary">
IPSCONTENT;

$htmlsprintf = array($review->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
									<div class="ipsData__last-secondary">
IPSCONTENT;

$val = ( $review->mapped('date') instanceof \IPS\DateTime ) ? $review->mapped('date') : \IPS\DateTime::ts( $review->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT
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
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_recent_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function subscriptions( $layout='wallpaper', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsWidget__header'>
	<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_widget_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$carouselID = 'widget--subscriptions_' . mt_rand();
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
 ipsData--subscriptions-widget" 
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

foreach ( \IPS\nexus\Subscription\Package::roots() as $package ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $package->enabled  ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

$sprintf = array($package->_title, $package->priceBlurb()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_widget_title_and_price', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span></a>
						<figure class="ipsData__image">
							
IPSCONTENT;

if ( $package->_image ):
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( '(', ')' ), array( '\(', '\)' ), $package->_image->url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
							
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
								<h4 class="ipsData__title"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
								<div class="ipsData__meta">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $package->priceBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
							</div>
						</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
	<p class='cWidgetSubscription_linkbox i-background_2 i-border-end-start-radius_box i-border-end-end-radius_box i-border-top_3 i-padding_2 i-font-weight_600 i-text-align_end i-font-size_-2'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions", null, "nexus_subscriptions", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_show_all_subs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-arrow-right"></i></a>
	</p>
</div>

IPSCONTENT;

		return $return;
}}