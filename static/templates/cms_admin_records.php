<?php
namespace IPS\Theme;
class class_cms_admin_records extends \IPS\Theme\Template
{	function category( $category ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $parent = $category->parent() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$databaseId = \IPS\Widget\Request::i()->database_id;
$return .= <<<IPSCONTENT

	<a href="#" data-ipsHover-timeout="0.1" data-ipsHover-target="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=databases&controller=records&do=categoryTree&id={$category->_id}&database_id={$databaseId}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsHover class="i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $parent->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> <i class="i-color_soft fa-solid fa-angle-right"></i>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function categorySelector( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3'>
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function categoryTree( $category, $parents ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">

IPSCONTENT;

$databaseId = \IPS\Widget\Request::i()->database_id;
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $parents as $cat ):
$return .= <<<IPSCONTENT

	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=databases&controller=categories&do=form&id={$cat->_id}&database_id={$databaseId}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cat->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> <i class="i-color_soft fa-solid fa-angle-right"></i>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=databases&controller=categories&do=form&id={$category->_id}&database_id={$databaseId}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function title( $row, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $row['record_future_date'] or $row['record_approved'] === -1 or $row['record_approved'] === 0 or $row['record_pinned'] === 1 or $row['record_featured'] === 1 ):
$return .= <<<IPSCONTENT

	<span class=''>
	
IPSCONTENT;

if ( $row['record_future_date'] ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$time  = \IPS\DateTime::ts( $row['record_publish_date'] );
$return .= <<<IPSCONTENT

		<span class="ipsBadge ipsBadge--icon ipsBadge--small ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$sprintf = array($time->localeDate(), $time->localeTime()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_future_date_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'><i class='fa-regular fa-clock'></i></span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $row['record_approved'] === -1 ):
$return .= <<<IPSCONTENT

		<span class="ipsBadge ipsBadge--icon ipsBadge--small ipsBadge--warning"><i class='fa-solid fa-eye-slash'></i></span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $row['record_approved'] === 0 ):
$return .= <<<IPSCONTENT

		<span class="ipsBadge ipsBadge--icon ipsBadge--small ipsBadge--warning" data-ipsTooltip><i class='fa-solid fa-triangle-exclamation'></i></span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $row['record_pinned'] === 1 ):
$return .= <<<IPSCONTENT

		<span class="ipsBadge ipsBadge--icon ipsBadge--small ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pinned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-thumbtack'></i></span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $row['record_featured'] === 1 ):
$return .= <<<IPSCONTENT

		<span class="ipsBadge ipsBadge--icon ipsBadge--small ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-star'></i></span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}