<?php
namespace IPS\Theme;
class class_core_front_embed extends \IPS\Theme\Template
{	function embedHeader( $content, $lang, $date, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed_header'>
	<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $content->author(), 'tiny', $content->warningRef() );
$return .= <<<IPSCONTENT

		<div class='ipsPhotoPanel__text'>
			<p class='ipsPhotoPanel__primary ipsTruncate_1'>
				<a href='
IPSCONTENT;

if ( $url ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			</p>
			<p class='ipsPhotoPanel__secondary ipsTruncate_1'>
				<a href='
IPSCONTENT;

if ( $url ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = ( $date instanceof \IPS\DateTime ) ? $date : \IPS\DateTime::ts( $date );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
			</p>
		</div>
	</div>
	<a href='
IPSCONTENT;

if ( $url ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_openItem'><i class='fa-solid fa-arrow-up-right-from-square'></i></a>
</div>
IPSCONTENT;

		return $return;
}

	function embedItemStats( $content, $commentsEnabled=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$reactionItem = $content;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $content::$firstCommentRequired ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$reactionItem = $content->firstComment();
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( ( \IPS\IPS::classUsesTrait( $content, 'IPS\Content\Ratings' ) and $content->averageRating() ) || ( isset( $content::$reviewClass ) AND $content->averageReviewRating() ) || $content::$commentClass || ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $reactionItem, 'IPS\Content\Reactable' ) and \count( $reactionItem->reactions() ) ) ):
$return .= <<<IPSCONTENT

	<ul class='ipsRichEmbed_stats'>
		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $reactionItem, 'IPS\Content\Reactable' ) and \count( $reactionItem->reactions() ) ):
$return .= <<<IPSCONTENT

			<li>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $reactionItem, TRUE, 'small' );
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $content, 'IPS\Content\Ratings' ) and $rating = $content->averageRating() ):
$return .= <<<IPSCONTENT

			<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'large', $rating, 5 );
$return .= <<<IPSCONTENT
</li>
		
IPSCONTENT;

elseif ( isset( $content::$reviewClass ) AND $rating = $content->averageReviewRating() ):
$return .= <<<IPSCONTENT

			<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'large', $rating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT
<span class='i-color_soft i-margin-start_2'>
IPSCONTENT;

if ( $content->mapped('num_reviews') ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$pluralize = array( $content->mapped('num_reviews') ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from_num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
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
</span></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $content::$commentClass AND $commentsEnabled ):
$return .= <<<IPSCONTENT

			<li>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url( "getPrefComment" )->setQueryString('tab', 'comments'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					<i class='fa-solid fa-comment'></i> 
					
IPSCONTENT;

if ( $content::$firstCommentRequired ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( $content->mapped('num_comments') - 1 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( $content->mapped('num_comments') ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
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

IPSCONTENT;

		return $return;
}

	function embedOriginalItem( $item, $showContent=FALSE, $otherInfo=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<h3 class='ipsRichEmbed_itemTitle ipsTruncate_1'>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

IPSCONTENT;

if ( $showContent ):
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__snippet'>
		{$item->truncated(TRUE)}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $otherInfo ):
$return .= <<<IPSCONTENT

	{$otherInfo}

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<ul class='ipsList ipsList--inline i-color_soft i-link-color_inherit i-margin-top_2 i-gap_1'>
	<li class='ipsRichEmbed_commentPhoto'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $item->author(), 'tinier' );
$return .= <<<IPSCONTENT

	</li>
	<li>
		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->setQueryString( 'do', 'getFirstComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

$sprintf = array($item->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $item->mapped('date') instanceof \IPS\DateTime ) ? $item->mapped('date') : \IPS\DateTime::ts( $item->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

		</a>
	</li>
	
IPSCONTENT;

if ( $item::$commentClass ):
$return .= <<<IPSCONTENT

		<li class="i-margin-start_auto">
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->setQueryString( 'do', 'getLastComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

if ( $item::$firstCommentRequired ):
$return .= <<<IPSCONTENT

					<i class='fa-solid fa-comment'></i> 
IPSCONTENT;

$pluralize = array( $item->mapped('num_comments') - 1 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<i class='fa-solid fa-comment'></i> 
IPSCONTENT;

$pluralize = array( $item->mapped('num_comments') ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</a>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}}