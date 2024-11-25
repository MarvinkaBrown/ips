<?php
namespace IPS\Theme;
class class_cms_front_global extends \IPS\Theme\Template
{	function basicRelationship( $items ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsList ipsList--csv'>
	
IPSCONTENT;

foreach ( $items as $id => $item ):
$return .= <<<IPSCONTENT

		<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function commentTableHeader( $comment, $record ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$iposted = $record->container()->contentPostedIn();
$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $record::$databaseColumnId;
$return .= <<<IPSCONTENT

<div>
	<h3 class='ipsTitle ipsTitle--h3'>
		
IPSCONTENT;

if ( $record->unread() ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $record->url( 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip class='ipsIndicator 
IPSCONTENT;

if ( \in_array( $record->$idField, $iposted ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<span class='ipsIndicator ipsIndicator--read 
IPSCONTENT;

if ( \in_array( $record->$idField, $iposted ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( 'content_db_lang_sl_' . $record::$customDatabaseId, FALSE ), $record->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_cmsrecord', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $record->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

if ( $record->container()->allow_rating ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $record->rating_hits ? ( $record->rating_total / $record->rating_hits ) : 0 );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h3>
	<p class='i-color_soft i-link-color_inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $record->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $record->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></p>
</div>
IPSCONTENT;

		return $return;
}

	function embedRecord( $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$image = NULL;
$return .= <<<IPSCONTENT

<div class='ipsRichEmbed ipsRichEmbed--cms-record'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $item, $item->mapped('title'), $item->mapped('date'), $url );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $item->record_image ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$image = \IPS\File::get( "cms_Records", $item->record_image );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( $contentImage = $item->contentImages(1) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$attachType = key( $contentImage[0] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$image = \IPS\File::get( $attachType, $contentImage[0][ $attachType ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
			<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
		</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed__snippet'>
			{$item->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \count( $item->customFieldsForDisplay('listing') ) ):
$return .= <<<IPSCONTENT

			<div class='i-margin-top_2'>
				
IPSCONTENT;

foreach ( $item->customFieldsForDisplay('listing') as $fieldId => $fieldValue ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $fieldValue ):
$return .= <<<IPSCONTENT

						{$fieldValue}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedItemStats( $item, $item::database()->options['comments'] );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedRecordComment( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--cms-comment'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			<div>
				
IPSCONTENT;

if ( $item->record_image ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$image = \IPS\File::get( "cms_Records", $item->record_image );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $contentImage = $item->contentImages(1) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$attachType = key( $contentImage[0] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$image = \IPS\File::get( $attachType, $contentImage[0][ $attachType ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
						<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='i-padding_3'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item, TRUE, \IPS\Theme::i()->getTemplate( 'global', 'cms' )->embedRecordItemSnippet( $item )  );
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>

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

	function embedRecordItemSnippet( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $item->customFieldsForDisplay('listing') ) ):
$return .= <<<IPSCONTENT

	<div class='i-margin-top_2'>
		
IPSCONTENT;

foreach ( $item->customFieldsForDisplay('listing') as $fieldId => $fieldValue ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $fieldValue ):
$return .= <<<IPSCONTENT

				{$fieldValue}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function embedRecordReview( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--cms-review'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			<div>
				
IPSCONTENT;

if ( $item->record_image ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$image = \IPS\File::get( "cms_Records", $item->record_image );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $contentImage = $item->contentImages(1) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$attachType = key( $contentImage[0] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$image = \IPS\File::get( $attachType, $contentImage[0][ $attachType ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
						<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='i-padding_3'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item, TRUE, \IPS\Theme::i()->getTemplate( 'global', 'cms' )->embedRecordItemSnippet( $item )  );
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

	function recordResultSnippet( $indexData, $itemData, $url, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $itemData['record_image'] ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--pages'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->thumbImage( $itemData['record_image'], $indexData['index_title'], 'large', '', 'view_this', $url, 'cms_Records' );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$condensed ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

		<div class='ipsStreamItem__content-content ipsStreamItem__content-content--pages'>
			<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_4'
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
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function uploadDisplay( $file, $record, $downloadUrl, $cipher ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $file->isImage() ):
$return .= <<<IPSCONTENT

	<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsImage" data-ipsLightbox>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $record ):
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "applications/cms/interface/file/file.php?record={$record->_id}&database={$record::$customDatabaseId}&fileKey={$cipher}&file=", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( urlencode($file->originalFilename), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $downloadUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}