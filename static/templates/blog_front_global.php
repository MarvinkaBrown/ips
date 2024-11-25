<?php
namespace IPS\Theme;
class class_blog_front_global extends \IPS\Theme\Template
{	function blogCategoryLink( $category ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function categoryLink( $category ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-font-weight_600">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function commentTableHeader( $comment, $entry ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- This template is used when viewing Profile > See my activity > Blog Entries -->
<div class='i-flex i-gap_2'>
	<div class='i-flex_00 i-basis_40'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $entry->author() );
$return .= <<<IPSCONTENT

	</div>
	<div class='i-flex_11'>
		<h3 class='ipsTitle ipsTitle--h3'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($entry->mapped('title')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</a>
		</h3>
		<p class='i-link-color_inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></p>
		<p class='i-color_soft i-link-color_inherit'>
			
IPSCONTENT;

if ( $entry->container()->owner() instanceof \IPS\Member ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$htmlsprintf = array($entry->container()->owner()->link(), $entry->container()->category()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( $club = $entry->container()->club() ):
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-users'></i> 
IPSCONTENT;

$sprintf = array($club->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_blog_for', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-users'></i> 
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::loggedIn()->language()->addToStack( 'blogs_groupblog_name_' . $entry->container()->id ), $entry->container()->category()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'group_blog_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedBlogs( $blog, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsRichEmbed ipsRichEmbed--blog'>
	<div class='ipsRichEmbed_header'>
		
IPSCONTENT;

if ( !( $blog->owner() instanceof \IPS\Member ) and \count( $blog->contributors() ) ):
$return .= <<<IPSCONTENT

			<div>
				<p class='i-font-weight_600 i-color_hard ipsTruncate_1'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				</p>
				
IPSCONTENT;

if ( $blog->latestEntry() ):
$return .= <<<IPSCONTENT

					<p class='i-color_soft ipsTruncate_1 i-link-color_inherit'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $blog->latestEntry()->mapped('date') )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_last_entry_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</a>
					</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $blog->owner(), 'tiny' );
$return .= <<<IPSCONTENT

				<div class='ipsPhotoPanel__text'>
					<p class='ipsPhotoPanel__primary ipsTruncate_1'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array($blog->owner()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_created_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
					</p>
					
IPSCONTENT;

if ( $blog->latestEntry() ):
$return .= <<<IPSCONTENT

						<p class='ipsPhotoPanel__secondary ipsTruncate_1'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $blog->latestEntry()->mapped('date') )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_last_entry_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</a>
						</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_openItem'><i class='fa-solid fa-arrow-up-right-from-square'></i></a>
	</div>
	
IPSCONTENT;

if ( $blog->coverPhoto() && $blog->coverPhoto()->file ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$photo = $blog->coverPhoto()->file;
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photo->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading="lazy">
		</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>

		<ul class='ipsList ipsList--inline'>
			<li>
IPSCONTENT;

$pluralize = array( $blog->_items ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_blog_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$pluralize = array( $blog->_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_blog_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$pluralize = array( $blog->num_views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_blog_views', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
		</ul>

		
IPSCONTENT;

if ( $blog->description ):
$return .= <<<IPSCONTENT

			<div class='ipsRichEmbed__snippet'>
				{$blog->description}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $blog->latestEntry() ):
$return .= <<<IPSCONTENT

			<hr class='ipsHr'>
			<p class='i-margin-top_2'>
				<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'latest_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</span> <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

	
IPSCONTENT;

if ( !( $blog->owner() instanceof \IPS\Member ) and \count( $blog->contributors() ) ):
$return .= <<<IPSCONTENT

		<div class='ipsRichEmbed_moreInfo'>
			<h3 class='ipsMinorTitle ipsTruncate_1 i-link-color_inherit'>
IPSCONTENT;

$pluralize = array( \count( $blog->contributors() ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_contributors_to_this', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h3>
			<ul class='ipsList ipsList--inline i-gap_0 i-margin-top_2'>
				
IPSCONTENT;

foreach ( $blog->contributors() as $idx => $contributor ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $contributor['member'], 'mini' );
$return .= <<<IPSCONTENT
<span class='ipsNotification'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contributor['contributions'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function embedEntry( $entry, $blog, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--blog-entry'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $entry, $entry->mapped('title'), $entry->mapped('date'), $url );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $entry->coverPhoto() && $entry->coverPhoto()->file ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$photo = $entry->coverPhoto()->file;
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
			<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photo->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading="lazy">
		</a>
	
IPSCONTENT;

elseif ( $blog->coverPhoto() && $blog->coverPhoto()->file ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$photo = $blog->coverPhoto()->file;
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
			<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photo->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading="lazy">
		</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "blog" )->embedEntryItemSnippet( $blog );
$return .= <<<IPSCONTENT

			<div class='ipsRichEmbed__snippet'>
				{$entry->truncated(TRUE)}
			</div>
		</div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedItemStats( $entry );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedEntryComment( $comment, $entry, $blog, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--blog-comment'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $entry->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT
	
	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			<div>
				
IPSCONTENT;

if ( $entry->coverPhoto() && $entry->coverPhoto()->file ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$photo = $entry->coverPhoto()->file;
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
						<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photo->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
					</a>
				
IPSCONTENT;

elseif ( $blog->coverPhoto() && $blog->coverPhoto()->file ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$photo = $blog->coverPhoto()->file;
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
						<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photo->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsHide'>
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='ipsRichEmbed__content'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $entry, TRUE, \IPS\Theme::i()->getTemplate( 'global', 'blog' )->embedEntryItemSnippet( $blog ) );
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>

		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled AND \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \count( $comment->reactions() ) ):
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

	function embedEntryItemSnippet( $blog ) {
		$return = '';
		$return .= <<<IPSCONTENT


<p class='i-font-weight_600 i-color_hard'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from_the_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 "
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
</p>
IPSCONTENT;

		return $return;
}

	function profileBlogRows( $table, $headers, $blogs, $showSmall ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $blogs AS $blog ):
$return .= <<<IPSCONTENT

	<li class='ipsInnerBox ipsInnerBox--padding 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
		<div class='ipsColumns i-align-items_center'>
			<div class='ipsColumns__primary'>
				<h3 class='ipsTitle ipsTitle--h4'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> 
IPSCONTENT;

if ( $showSmall && !($blog->owner() instanceof \IPS\Member) ):
$return .= <<<IPSCONTENT
 <span class='i-color_soft' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_group', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-users'></i> 
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( 'blogs_groupblog_name_' . $blog->id )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</h3>
			</div>
			<div class='ipsColumns__secondary'>
				<ul class='ipsList ipsList--inline ipsList--label-value'>
					<li><span class='ipsList__value'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_items, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><span class='ipsList__label'>
IPSCONTENT;

$pluralize = array( $blog->_items ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></li>
					<li><span class='ipsList__value'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><span class='ipsList__label'>
IPSCONTENT;

$pluralize = array( $blog->_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></li>
					<li><span class='ipsList__value'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->num_views, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><span class='ipsList__label'>
IPSCONTENT;

$pluralize = array( $blog->num_views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_views', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></li>
				</ul>
			</div>
		</div>
		
IPSCONTENT;

if ( $blog->latestEntry() ):
$return .= <<<IPSCONTENT

			<div class='i-margin-top_2 i-border-top_1 i-padding-top_2'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($blog->latestEntry()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='i-font-weight_600 i-color_hard'>
					
IPSCONTENT;

if ( $blog->latestEntry()->unread() ):
$return .= <<<IPSCONTENT

						<span class='ipsIndicator' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unread_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</a>
				<ul class='ipsList ipsList--inline i-margin-top_1'>
					<li>
IPSCONTENT;

$val = ( $blog->latestEntry()->date instanceof \IPS\DateTime ) ? $blog->latestEntry()->date : \IPS\DateTime::ts( $blog->latestEntry()->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

if ( \IPS\Settings::i()->blog_enable_rating ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'small', $blog->latestEntry()->averageRating(), 5, $blog->latestEntry()->memberRating() );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$blog->latestEntry()->num_comments ):
$return .= <<<IPSCONTENT

						<li class='i-color_soft'>
IPSCONTENT;

$pluralize = array( $blog->latestEntry()->num_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
#comments' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_comments_on_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$pluralize = array( $blog->latestEntry()->num_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
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

	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function profileBlogTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	
IPSCONTENT;

if ( isset( $rows['owner'] ) AND \count( $rows['owner'] ) ):
$return .= <<<IPSCONTENT

		<h2 class='ipsTitle ipsTitle--h3 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_blogs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<ol class='i-grid i-gap_2 i-margin-bottom_3' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_owner'>
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows['owner'], FALSE );
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $rows['contributor'] ) AND \count( $rows['contributor'] ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $rows['owner'] ) AND \count( $rows['owner'] ) ):
$return .= <<<IPSCONTENT

			<h3 class='ipsTitle ipsTitle--h3 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'also_contributes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<h2 class='ipsTitle ipsTitle--h3 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contributes_to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<ol class='i-grid i-gap_2' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_contributor'>
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows['contributor'], TRUE );
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function rows( $table, $headers, $entries ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- This template is used when viewing Profile > See my activity > Blog Entries -->

IPSCONTENT;

foreach ( $entries as $idx => $entry ):
$return .= <<<IPSCONTENT

	<li class='ipsData__item 
IPSCONTENT;

if ( $entry->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->blogViewMedium( $entry, $table, FALSE );
$return .= <<<IPSCONTENT

	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}