<?php
namespace IPS\Theme;
class class_core_front_tags extends \IPS\Theme\Template
{	function contentTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$rowIds = array();
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$rowIds[] = $row->$idField;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
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

if ( \IPS\IPS::classUsesTrait( $row, 'IPS\Content\Hideable' ) and $row->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
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
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

if ( $row->mapped('title') or $row->mapped('title') == 0 ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				<figure class='ipsData__image'>
				    
IPSCONTENT;

if ( $image = $row->primaryImage() ):
$return .= <<<IPSCONTENT

				        <img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped( 'title' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->defaultThumb(  );
$return .= <<<IPSCONTENT

				    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission( 'can_pin_tagged' ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pinUrl = \IPS\Http\Url::internal( 'app=core&module=discover&controller=tag&do=pin&tag=' . \IPS\Widget\Request::i()->tag . '&itemClass=' . \get_class( $row ) . '&itemId=' . $row->$idField )->csrf();
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pinUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsBadge ipsBadge--icon ipsBadge--positive' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pin_to_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pin_to_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-star'></i></a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</figure>
				
				<div class='ipsData__content'>
					<div class='ipsData__main'>
						<div class='ipsData__title'>
							<div class="ipsBadges">
							    
IPSCONTENT;

foreach ( $row->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $row, 'IPS\Content\Tags' ) AND $row->prefix() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $row->prefix( TRUE ), $row->prefix() );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
							
IPSCONTENT;

if ( $row->unread() ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip class='ipsIndicator 
IPSCONTENT;

if ( $row->containerWrapper() AND \IPS\IPS::classUsesTrait( $row->containerWrapper(), 'IPS\Node\Statistics' ) AND \in_array( $row->$idField, $row->containerWrapper()->contentPostedIn( null, $rowIds ) ) ):
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

if ( $row->containerWrapper() AND \IPS\IPS::classUsesTrait( $row->containerWrapper(), 'IPS\Node\Statistics' ) AND \in_array( $row->$idField, $row->containerWrapper()->contentPostedIn( null, $rowIds ) ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' aria-hidden='true'></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<h4>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $row->tableHoverUrl and $row->canView() ):
$return .= <<<IPSCONTENT
data-ipsHover data-ipsHover-target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url()->setQueryString('preview', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'data-ipsHover
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->canEdit() AND $row->editableTitle === TRUE ):
$return .= <<<IPSCONTENT
data-role="editableTitle" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									
IPSCONTENT;

if ( $row->mapped('title') or $row->mapped('title') == 0 ):
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

								</a>
							</h4>
							
IPSCONTENT;

if ( $row->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

		                        {$row->commentPagination( array(), 'miniPagination' )}
		                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		                </div>
						
IPSCONTENT;

if ( method_exists( $row, 'tableDescription' ) ):
$return .= <<<IPSCONTENT

		                	<div class='ipsData__desc ipsTruncate_2'>{$row->tableDescription()}</div>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<div class='ipsData__meta'>
	                            
IPSCONTENT;

$htmlsprintf = array($row->author()->link( $row->warningRef(), NULL, \IPS\IPS::classUsesTrait( $row, 'IPS\Content\Anonymous' ) ? $row->isAnonymous() : FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

$val = ( $row->__get( $row::$databaseColumnMap['date'] ) instanceof \IPS\DateTime ) ? $row->__get( $row::$databaseColumnMap['date'] ) : \IPS\DateTime::ts( $row->__get( $row::$databaseColumnMap['date'] ) );$return .= $val->html();
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \in_array( \IPS\Widget\Request::i()->controller, array( 'search' ) ) ):
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
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		                
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $row, 'IPS\Content\Taggable' ) AND $row->tags() AND \count( $row->tags() ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tagsWithPrefix( $row->tags(), $row->prefix(), true, true );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class="ipsData__extra">
						<ul class='ipsData__stats'>
							
IPSCONTENT;

foreach ( $row->stats(TRUE) as $k => $v ):
$return .= <<<IPSCONTENT

								<li 
IPSCONTENT;

if ( \in_array( $k, $row->hotStats ) ):
$return .= <<<IPSCONTENT
class="ipsData__stats-hot" data-text='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-statType='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
									<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
									<span class='ipsData__stats-label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
						<div class="ipsData__last">
							
IPSCONTENT;

if ( $row->mapped('num_comments') ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->lastCommenter(), 'tiny' );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->author(), 'tiny' );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<div class="ipsData__last-text">
								<div class="ipsData__last-primary">
									
IPSCONTENT;

if ( $row->mapped('num_comments') ):
$return .= <<<IPSCONTENT

										{$row->lastCommenter()->link()}
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										{$row->author()->link()}
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
								<div class="ipsData__last-secondary">
									
IPSCONTENT;

if ( $row->mapped('last_comment') ):
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('getLastComment'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = ( $row->mapped('last_comment') instanceof \IPS\DateTime ) ? $row->mapped('last_comment') : \IPS\DateTime::ts( $row->mapped('last_comment') );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$val = ( $row->mapped('date') instanceof \IPS\DateTime ) ? $row->mapped('date') : \IPS\DateTime::ts( $row->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
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


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function coverPhotoOverlay( $tag ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsPageHeader__row">
	<div class="ipsPageHeader__primary">
		<div class='ipsPageHeader__title'>
			<h2><span class="i-color_soft">#</span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag->text, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
		</div>
		
IPSCONTENT;

if ( $tag->_title ):
$return .= <<<IPSCONTENT

			<div class='i-color_soft i-margin-top_1'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $tag->description ):
$return .= <<<IPSCONTENT

			<div class="ipsPageHeader__desc">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag->description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'core','tag', $tag->id, $tag->followersCount() );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function overview( $items, $tag ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- This is used under "All Content" -->
<i-data>
    <ol class="ipsData ipsData--grid ipsData--tags-overview" data-role="tableRows">
        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "tags", "core" )->contentTableRows( null, array(), $items );
$return .= <<<IPSCONTENT

    </ol>
    
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'search' ) ) ):
$return .= <<<IPSCONTENT

    <div class="ipsSubmitRow">
        
IPSCONTENT;

$searchUrl = \IPS\Http\Url::internal( 'app=core&module=search&controller=search', 'front', 'search' )->setQueryString( 'tags', $tag->text );
$return .= <<<IPSCONTENT

        <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $searchUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--secondary"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tags__view_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right-long"></i></a>
    </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<i-data>
IPSCONTENT;

		return $return;
}

	function pinnedItem( $row, $tag, $showUnfeatureButton=true ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$type = $row->type;
$return .= <<<IPSCONTENT


IPSCONTENT;

$rowIds = array();
$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

$rowIds[] = $row->$idField;
$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

<li class="ipsData__item 
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $row, 'IPS\Content\Hideable' ) and $row->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
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
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( $row->mapped('title') or $row->mapped('title') == 0 ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<figure class='ipsData__image'>
	    
IPSCONTENT;

if ( $row->pinnedData['pinned_image'] ):
$return .= <<<IPSCONTENT

	        <img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Tags", $row->pinnedData['pinned_image'] )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped( 'title' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
	    
IPSCONTENT;

elseif ( $image = $row->primaryImage() ):
$return .= <<<IPSCONTENT

	        <img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped( 'title' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
	    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->defaultThumb(  );
$return .= <<<IPSCONTENT

	    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $showUnfeatureButton and \IPS\Member::loggedIn()->modPermission( 'can_pin_tagged' ) ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag->url()->csrf()->setQueryString( array( 'do' => 'unpin', 'itemClass' => \get_class( $row ), 'itemId' => $row->$idField ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm class="ipsBadge ipsBadge--negative"><i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unpin_from_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	</figure>

	<div class='ipsData__content'>
		<div class='ipsData__main'>
			<div class='ipsData__title'>
				
IPSCONTENT;

if ( $row->unread() ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip class='ipsIndicator 
IPSCONTENT;

if ( $row->containerWrapper() AND \IPS\IPS::classUsesTrait( $row->containerWrapper(), 'IPS\Node\Statistics' ) AND \in_array( $row->$idField, $row->containerWrapper()->contentPostedIn( null, $rowIds ) ) ):
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

if ( $row->containerWrapper() AND \IPS\IPS::classUsesTrait( $row->containerWrapper(), 'IPS\Node\Statistics' ) AND \in_array( $row->$idField, $row->containerWrapper()->contentPostedIn( null, $rowIds ) ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' aria-hidden='true'></span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<h4>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $row->tableHoverUrl and $row->canView() ):
$return .= <<<IPSCONTENT
data-ipsHover data-ipsHover-target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url()->setQueryString('preview', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'data-ipsHover
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->canEdit() AND $row->editableTitle === TRUE ):
$return .= <<<IPSCONTENT
data-role="editableTitle" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

if ( $row->mapped('title') or $row->mapped('title') == 0 ):
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

					</a>
				</h4>
				<div class="ipsBadges">
					
IPSCONTENT;

foreach ( $row->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( $row->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

					{$row->commentPagination( array(), 'miniPagination' )}
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsData__desc">
				
IPSCONTENT;

if ( method_exists( $row, 'tableDescription' ) ):
$return .= <<<IPSCONTENT

					<div class='ipsTruncate_2'>{$row->tableDescription()}</div>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					{$row->truncated(TRUE)}
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>

			
IPSCONTENT;

if ( $row instanceof \IPS\downloads\File ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

					<ul class='ipsList ipsList--inline'>
						<li class='cFilePrice'>
							
IPSCONTENT;

if ( $row->isPaid() ):
$return .= <<<IPSCONTENT

								{$row->price()}
							
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

if ( $renewalTerm = $row->renewalTerm() ):
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

endif;
$return .= <<<IPSCONTENT


			<div class='ipsData__meta'>
				
IPSCONTENT;

if ( \in_array( \IPS\Widget\Request::i()->controller, array( 'search' ) ) ):
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
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $row instanceof \IPS\downloads\File and $row->container()->version_numbers ):
$return .= <<<IPSCONTENT

					&middot; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $row, 'IPS\Content\Taggable' ) AND $row->tags() AND \count( $row->tags() ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tagsWithPrefix( $row->tags(), $row->prefix(), true, true );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsData__extra">
			<ul class='ipsData__stats'>
				
IPSCONTENT;

if ( $row instanceof \IPS\downloads\File ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $row->container()->version_numbers OR ($row->isPaid() and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) )) OR (!$row->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) )) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $row->isPaid() and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) and $row->purchaseCount() ):
$return .= <<<IPSCONTENT

							<li>
IPSCONTENT;

$pluralize = array( $row->purchaseCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !$row->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) and $row->downloads ):
$return .= <<<IPSCONTENT

							<li>
IPSCONTENT;

$pluralize = array( $row->downloads ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $row->stats(TRUE) as $k => $v ):
$return .= <<<IPSCONTENT

					<li 
IPSCONTENT;

if ( \in_array( $k, $row->hotStats ) ):
$return .= <<<IPSCONTENT
class="ipsData__stats-hot" data-text='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-stattype="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
			<div class="ipsData__last">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->author(), 'tiny' );
$return .= <<<IPSCONTENT

				<div class="ipsData__last-text">
					<div class="ipsData__last-primary">
						{$row->author()->link()}
					</div>
					<div class="ipsData__last-secondary">
						
IPSCONTENT;

$val = ( $row->mapped('date') instanceof \IPS\DateTime ) ? $row->mapped('date') : \IPS\DateTime::ts( $row->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		</div>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function view( $tag, $tabs, $content='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elTagHeader" class='ipsBox ipsPageHeader ipsPull'>
    {$tag->coverPhoto()}
    
IPSCONTENT;

if ( $pinnedItems = $tag->getPinnedItems() ):
$return .= <<<IPSCONTENT

    <div id='elTagPinnedItems'>
		<h3 class="i-color_soft i-text-transform_uppercase i-font-weight_600 i-font-size_-2 i-padding-block_2 i-padding-inline_3 i-background_2 i-border-top_3 i-border-bottom_3">
			
IPSCONTENT;

$sprintf = array($tag->text); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tags__pinned_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
		</h3>
		<i-data>
			<ol class='ipsData ipsData--grid ipsData--carousel' id="tags-pinned-carousel" tabindex="0">
				
IPSCONTENT;

foreach ( $pinnedItems as $pinned ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "tags", "core", 'front' )->pinnedItem( $pinned, $tag );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ol>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'tags-pinned-carousel' );
$return .= <<<IPSCONTENT

		</i-data>
    </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<div id='elTagControls' class="ipsBox ipsTagsFeed ipsPull">
    
IPSCONTENT;

if ( count( $tabs ) > 1 ):
$return .= <<<IPSCONTENT

	<i-tabs class='ipsTabs ipsTabs--sticky' id='ipsTabs_tag' data-ipsTabBar data-ipsTabBar-contentArea="#ipsTabs_tag_content">
		<div role='tablist'>
			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsTabs__tab" id="ipsTabs_tag_all" data-tab="all" role="tab" aria-controls="ipsTabs_tag_all_panel" aria-selected="
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->tab ) ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tag_all_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

foreach ( $tabs as $type ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tag->url()->setQueryString( 'tab', $type ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsTabs__tab" id="ipsTabs_tag_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-tab="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" role="tab" aria-controls="ipsTabs_tag_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->tab ) and \IPS\Widget\Request::i()->tab == $type ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$type}_pl"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

	</i-tabs>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id='ipsTabs_tag_content' class='ipsTabs__panels'>{$content}</div>
</div>
IPSCONTENT;

		return $return;
}}