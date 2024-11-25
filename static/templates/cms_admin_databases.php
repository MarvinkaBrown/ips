<?php
namespace IPS\Theme;
class class_cms_admin_databases extends \IPS\Theme\Template
{	function downloadDialog( $database ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_3'>
	<i class="fa-solid fa-download ipsAlert_icon"></i>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_download_db_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<ul class="ipsButtons ipsButtons--end ipsButtons--downloadDialog">
		<li>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=databases&controller=databases&do=download&id={$database->_id}&go=true", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary'>
IPSCONTENT;

$sprintf = array($database->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_download_db', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
		</li>
	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function fieldsWrapper( $tree ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='ipsBox' data-ips-template="fieldsWrapper">
	<h4 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_fields_fixed_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
	{$tree}
</section>
IPSCONTENT;

		return $return;
}

	function manageDatabaseName( $database, $row, $page ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-flex">
	<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $database->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
	
IPSCONTENT;

if ( $page ):
$return .= <<<IPSCONTENT

		<a class="i-margin-start_2 ipsBadge ipsBadge--style1" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $page->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
IPSCONTENT;

$sprintf = array($page->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_db_used_on_page', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	    <span class="i-margin-start_2 ipsBadge ipsBadge--negative"><i class='fas fa-warning'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_no_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<p class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $database->_description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

		return $return;
}

	function rebuildCommentCounts( $id ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox'>
	<div class="i-padding_3">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_rebuild_commentcount_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		<p>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=databases&controller=databases&do=rebuildCommentCounts&id={$id}&process=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_rebuild_commentcount_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function rebuildTopics( $id ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox'>
	<div class="i-padding_3">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_rebuild_topics_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		<p>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=databases&controller=databases&do=rebuildTopicContent&id={$id}&process=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_rebuild_topics_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function templateGoButton( $name ) {
		$return = '';
		$return .= <<<IPSCONTENT

&nbsp; <a href="#" data-template-view="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-url="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&t_location=database", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'database_form_template_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}}