<?php
namespace IPS\Theme;
class class_downloads_front_global extends \IPS\Theme\Template
{	function commentTableHeader( $comment, $file ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-flex i-gap_2'>
	<div class='i-flex_00 i-basis_70'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->thumbImage( $file->primary_screenshot, $file->name, 'small', '', 'view_this', $file->url( 'getPrefComment' ) );
$return .= <<<IPSCONTENT

	</div>
	<div class='i-flex_11'>
		<h3 class='ipsTitle ipsTitle--h3'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($file->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
		<p class='i-color_soft i-link-color_inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></p>
		
IPSCONTENT;

if ( $file->container()->bitoptions['reviews'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $file->rating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT
 &nbsp;&nbsp;
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<span>
IPSCONTENT;

if ( !$file->downloads ):
$return .= <<<IPSCONTENT
<span class='i-color_soft'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<i class='fa-solid fa-circle-arrow-down'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$file->downloads ):
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $file->container()->bitoptions['comments'] ):
$return .= <<<IPSCONTENT
&nbsp;&nbsp;
IPSCONTENT;

if ( !$file->comments ):
$return .= <<<IPSCONTENT
<span class='i-color_soft'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<i class='fa-solid fa-comment'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$file->comments ):
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedFile( $item, $url, $image=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--downloads-file'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $item, $item->mapped('title'), $item->mapped('date'), $url );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $item->primary_screenshot_thumb ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy" alt="">
		</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline'>
				<li class='cFilePrice'>
					
IPSCONTENT;

if ( $item->isPaid() ):
$return .= <<<IPSCONTENT

						{$item->price()}
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_free', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
				
IPSCONTENT;

if ( $renewalTerm = $item->renewalTerm() ):
$return .= <<<IPSCONTENT

					<li class='i-color_soft'>
						
IPSCONTENT;

$sprintf = array($renewalTerm); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_renewal_term_val', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
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

if ( $item->container()->version_numbers OR ($item->isPaid() and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) )) OR (!$item->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) )) ):
$return .= <<<IPSCONTENT

		<ul class='ipsRichEmbed_stats'>
			
IPSCONTENT;

if ( $item->container()->version_numbers ):
$return .= <<<IPSCONTENT
 <li><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $item->isPaid() and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) ):
$return .= <<<IPSCONTENT

				<li 
IPSCONTENT;

if ( !$item->purchaseCount() ):
$return .= <<<IPSCONTENT
class='i-color_soft'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class='fa-solid fa-cart-shopping'></i> 
IPSCONTENT;

$pluralize = array( $item->purchaseCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !$item->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) ):
$return .= <<<IPSCONTENT

				<li 
IPSCONTENT;

if ( !$item->downloads ):
$return .= <<<IPSCONTENT
class='i-color_soft'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class='fa-solid fa-circle-arrow-down'></i> 
IPSCONTENT;

$pluralize = array( $item->downloads ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
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

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<div>
				
IPSCONTENT;

if ( $item->canBuy() AND $item->isPurchasable() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $item->canDownload() ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

if ( \IPS\Settings::i()->idm_antileech AND !$item->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('download')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('download'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--wide ipsButton--primary' 
IPSCONTENT;

if ( $item->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT
data-dialog
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('buy')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--wide ipsButton--primary'><i class='fa-solid fa-cart-shopping'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'buy_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $price = $item->price() ):
$return .= <<<IPSCONTENT
 - {$price}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $item->isPaid() AND !$item->isPurchasable( FALSE ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchasing_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $item->canDownload() or !$item->downloadTeaser() ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

if ( \IPS\Settings::i()->idm_antileech AND !$item->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('download')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url('download'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--wide ipsButton--primary' 
IPSCONTENT;

if ( $item->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT
data-dialog
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span class="ipsButton ipsButton--soft ipsButton--wide ipsButton--disable">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_teaser', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
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

		<div class='ipsRichEmbed__snippet'>
			{$item->truncated(TRUE)}
		</div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedItemStats( $item, $item->container()->bitoptions['comments'] );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedFileComment( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--downloads-comment'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			
IPSCONTENT;

if ( $item->primary_screenshot_thumb ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead ipsRichEmbed_masthead--small'>
					<img src='
IPSCONTENT;

$return .= \IPS\File::get( "downloads_Screenshots", $item->primary_screenshot_thumb )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsRichEmbed__content'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item, FALSE, \IPS\Theme::i()->getTemplate( 'global', 'downloads' )->embedFileItemSnippet( $item )  );
$return .= <<<IPSCONTENT

			</div>
		</div>
		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>
		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \count( $comment->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsRichEmbed_stats'>
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

	function embedFileItemSnippet( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


<ul class='ipsList ipsList--inline i-margin-bottom_2'>
	
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

		<li class='cFilePrice'>
			
IPSCONTENT;

if ( $item->isPaid() ):
$return .= <<<IPSCONTENT

				{$item->price()}
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_free', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
		
IPSCONTENT;

if ( $renewalTerm = $item->renewalTerm() ):
$return .= <<<IPSCONTENT

			<li class='i-color_soft'>
				
IPSCONTENT;

$sprintf = array($renewalTerm); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_renewal_term_val', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $item->isPaid() and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) ):
$return .= <<<IPSCONTENT

		<li 
IPSCONTENT;

if ( !$item->purchaseCount() ):
$return .= <<<IPSCONTENT
class='i-color_soft'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 title="
IPSCONTENT;

$pluralize = array( $item->purchaseCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-cart-shopping'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !$item->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) ):
$return .= <<<IPSCONTENT

		<li 
IPSCONTENT;

if ( !$item->downloads ):
$return .= <<<IPSCONTENT
class='i-color_soft'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 title="
IPSCONTENT;

$pluralize = array( $item->downloads ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-circle-arrow-down'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $item->downloads );
$return .= <<<IPSCONTENT
</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</ul>
	
IPSCONTENT;

		return $return;
}

	function embedFileReview( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--downloads-review'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			<div>
				
IPSCONTENT;

if ( $item->primary_screenshot_thumb ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead ipsRichEmbed_masthead--small'>
						<img src='
IPSCONTENT;

$return .= \IPS\File::get( "downloads_Screenshots", $item->primary_screenshot_thumb )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
					</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class='ipsRichEmbed_masthead'></div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='i-padding_3'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item, FALSE, \IPS\Theme::i()->getTemplate( 'global', 'downloads' )->embedFileItemSnippet( $item )  );
$return .= <<<IPSCONTENT

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

		<hr class='ipsHr'>
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

	function manageFollowRow( $table, $headers, $rows, $includeFirstCommentInCommentCount=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

		<li class="ipsData__item 
IPSCONTENT;

if ( method_exists( $row, 'tableClass' ) && $row->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-controller='core.front.system.manageFollowed' data-followID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_area'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_rel_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $row->unread() ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<figure class='ipsData__image'>
				
IPSCONTENT;

if ( $row->_primary_screenshot  ):
$return .= <<<IPSCONTENT

					<img src='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( '(', ')' ), array( '\(', '\)' ), $row->primary_screenshot->url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
			<div class='ipsData__content'>
				<div class='ipsData__main'>
					<div class='ipsData__title'>
						<div class='ipsBadges'>
							
IPSCONTENT;

if ( $row->mapped('locked') ):
$return .= <<<IPSCONTENT

								<span><i class="fa-solid fa-lock"></i></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $row->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $row->prefix() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $row->prefix( TRUE ), $row->prefix() );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<h4><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $row->mapped('title') ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a></h4>
					</div>
					
IPSCONTENT;

if ( method_exists( $row, 'tableDescription' ) ):
$return .= <<<IPSCONTENT

						<div class='ipsData__desc ipsTruncate_2'>
							{$row->tableDescription()}
						</div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class='ipsData__meta'>
							
IPSCONTENT;

$htmlsprintf = array($row->author()->link( $row->warningRef() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $row->__get( $row::$databaseColumnMap['date'] ) instanceof \IPS\DateTime ) ? $row->__get( $row::$databaseColumnMap['date'] ) : \IPS\DateTime::ts( $row->__get( $row::$databaseColumnMap['date'] ) );$return .= $val->html();
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						</div>			
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>

				<div class='cFollowedContent_anon'>
					<span class='ipsBadge ipsBadge--icon ipsBadge--new 
IPSCONTENT;

if ( !$row->_followData['follow_is_anon'] ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='followAnonymous' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_is_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
				</div>

				<div class='i-basis_180 cFollowedContent_info'>
					<ul>
						<li title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_when', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='followDate'><i class='fa-regular fa-clock'></i> 
IPSCONTENT;

$val = ( $row->_followData['follow_added'] instanceof \IPS\DateTime ) ? $row->_followData['follow_added'] : \IPS\DateTime::ts( $row->_followData['follow_added'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
						<li title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_how', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='followFrequency'>
							
IPSCONTENT;

if ( $row->_followData['follow_notify_freq'] == 'none' ):
$return .= <<<IPSCONTENT

								<i class='fa-regular fa-bell-slash'></i>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class='fa-solid fa-bell'></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "follow_freq_{$row->_followData['follow_notify_freq']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</li>
					</ul>
				</div>

				<div class='i-basis_200 i-text-align_end cFollowedContent_manage'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->manageFollow( $row->_followData['follow_app'], $row->_followData['follow_area'], $row->_followData['follow_rel_id'] );
$return .= <<<IPSCONTENT

				</div>
			</div>

			
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

				<div class='ipsData__mod'>
					<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( $row->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class="ipsInput ipsInput--toggle">
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function searchResultCommentSnippet( $indexData, $screenshot, $url, $reviewRating, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $screenshot ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--downloads'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->thumbImage( $screenshot, $indexData['index_title'], 'fluid', '', 'view_this', $url, 'downloads_Screenshots', '', true );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$condensed ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-content ipsStreamItem__content-content--downloads'>
		
IPSCONTENT;

if ( $reviewRating !== NULL ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'medium', $reviewRating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsStream__comment'>
			
IPSCONTENT;

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

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

$return .= \IPS\Content\Search\Result::preDisplay( $indexData['index_content'] );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function searchResultFileSnippet( $indexData, $itemData, $screenshot, $url, $price, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $screenshot ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--downloads'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->thumbImage( $screenshot, $indexData['index_title'], 'fluid', '', 'view_this', $url, 'downloads_Screenshots', '', true );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$condensed ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-content ipsStreamItem__content-content--downloads'>
		<ul class='ipsList ipsList--inline ipsList--icons'>
			
IPSCONTENT;

if ( !$price or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) )  ):
$return .= <<<IPSCONTENT

				<li class='i-font-weight_600'><i class='fa-solid fa-download'></i> 
IPSCONTENT;

$pluralize = array( $itemData['file_downloads'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $itemData['file_version'] ):
$return .= <<<IPSCONTENT
<li class='i-color_soft i-font-weight_500'><i class="fa-regular fa-file-lines"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData['file_version'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
		</ul>
		
IPSCONTENT;

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

			<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_2'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class='ipsRichText' data-searchable data-findTerm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

$return .= \IPS\Content\Search\Result::preDisplay( $indexData['index_content'] );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
		
		
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

			<div class='cNexusPrice'>
				
IPSCONTENT;

if ( $price ):
$return .= <<<IPSCONTENT

					{$price}
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_free', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}