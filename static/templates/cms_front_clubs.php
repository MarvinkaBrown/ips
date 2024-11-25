<?php
namespace IPS\Theme;
class class_cms_front_clubs extends \IPS\Theme\Template
{	function clubCategoryView( $category, $club, $index ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->clubs_header != 'sidebar' ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $category, 'full' );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


{$index}

IPSCONTENT;

		return $return;
}

	function index( $database, $articles, $url, $pagination ) {
		$return = '';
		$return .= <<<IPSCONTENT


<header class='ipsPageHeader ipsBox ipsPull'>
    <div class="ipsPageHeader__row">
        <div class='ipsPageHeader__primary'>
            <h1 class='ipsPageHeader__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $database->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
            
IPSCONTENT;

if ( $database->_description ):
$return .= <<<IPSCONTENT

            <div class='ipsPageHeader__desc'>
                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $database->_description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

            </div>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>
        <div class="ipsButtons">
            
IPSCONTENT;

$catClass = 'IPS\cms\Categories' . $database->id; $category = $catClass::load( $database->default_category );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'cms','categories' . $database->id, $category->_id, \IPS\cms\Records::containerFollowerCount( $category ) );
$return .= <<<IPSCONTENT

        </div>
    </div>
</header>


IPSCONTENT;

if ( $database->can('add') or \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

<ul class="ipsButtons ipsButtons--main ipsResponsive_hidePhone">
    
IPSCONTENT;

if ( $database->can('add') ):
$return .= <<<IPSCONTENT

        <li>
            <a class="ipsButton ipsButton--important" 
IPSCONTENT;

if ( $database->use_categories ):
$return .= <<<IPSCONTENT
data-ipsDialog="1" data-ipsDialog-size="narrow" data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_select_category', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'form', 'd' => \IPS\cms\Databases\Dispatcher::i()->databaseId ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($database->recordWord( 1 )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_add_new_record_button', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
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


<section>
    
IPSCONTENT;

if ( \count($articles) ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

foreach ( $articles as $id => $record ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "category_articles", "cms", 'database' )->entry( $record, $database );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

        <div class="ipsBox ipsBox--padding">
IPSCONTENT;

$sprintf = array(\IPS\cms\Databases::load( \IPS\cms\Databases\Dispatcher::i()->databaseId )->recordWord()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_no_records_to_show', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</section>

IPSCONTENT;

if ( $database->featured_settings['pagination'] and ( $pagination['pages'] > 1 ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $url, $pagination['pages'], $pagination['page'], $database->featured_settings['perpage'], TRUE, 'page' );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}