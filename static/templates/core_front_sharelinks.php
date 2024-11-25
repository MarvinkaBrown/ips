<?php
namespace IPS\Theme;
class class_core_front_sharelinks extends \IPS\Theme\Template
{	function email( $url, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="mailto:?subject=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&body=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel='nofollow' class='ipsShareLink ipsShareLink--email' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
	<i class="fa-solid fa-envelope"></i>
</a>
IPSCONTENT;

		return $return;
}

	function facebook( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="https://www.facebook.com/sharer/sharer.php?u=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsShareLink ipsShareLink--facebook" target="_blank" data-role="shareLink" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'facebook_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip rel='noopener nofollow'>
	<i class="fa-brands fa-facebook"></i>
</a>
IPSCONTENT;

		return $return;
}

	function linkedin( $url, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="https://www.linkedin.com/shareArticle?mini=true&amp;url=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&amp;title=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow noopener" class="ipsShareLink ipsShareLink--linkedin" target="_blank" data-role="shareLink" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'lin_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
	<i class="fa-brands fa-linkedin"></i>
</a>
IPSCONTENT;

		return $return;
}

	function pinterest( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsShareLink ipsShareLink--pinterest" rel="nofollow noopener" target="_blank" data-role="shareLink" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pinterest_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
	<i class="fa-brands fa-pinterest"></i>
</a>
IPSCONTENT;

		return $return;
}

	function reddit( $url, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="https://www.reddit.com/submit?url=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&amp;title=
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( urlencode( $title ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow noopener" class="ipsShareLink ipsShareLink--reddit" target="_blank" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reddit_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
	<i class="fa-brands fa-reddit"></i>
</a>
IPSCONTENT;

		return $return;
}

	function shareButton( $item, $classes = '', $type = 'inherit' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$id = mt_rand();
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $item->sharelinks() )  ):
$return .= <<<IPSCONTENT

    <a href='#elShareItem_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' id='elShareItem_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsMenu class='ipsButton ipsButton--share 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsButton--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
        <i class='fa-solid fa-share-nodes'></i><span class="ipsButton__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'share', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
    </a>
    <div class='i-padding_2 ipsMenu ipsMenu_normal ipsHide' id='elShareItem_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' data-controller="core.front.core.sharelink">
        
IPSCONTENT;

$url = $item->url();
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( \IPS\Settings::i()->ref_on ):
$return .= <<<IPSCONTENT

        	
IPSCONTENT;

$url = $url->setQueryString( array( '_rid' => \IPS\Member::loggedIn()->member_id  ) );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        <span data-ipsCopy data-ipsCopy-flashmessage>
            <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsPageActions__mainLink" data-role="copyButton" data-clipboard-text="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy_share_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-regular fa-copy"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
        </span>
        <ul class='ipsList ipsList--inline i-justify-content_center i-gap_1 i-margin-top_2'>
            
IPSCONTENT;

foreach ( $item->sharelinks() as $sharelink  ):
$return .= <<<IPSCONTENT

                <li>{$sharelink}</li>
            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        </ul>
        
IPSCONTENT;

if ( $shareData = $item->webShareData() ):
$return .= <<<IPSCONTENT

            <button class='ipsHide ipsButton ipsButton--small ipsButton--soft ipsButton--wide i-margin-top_2' data-controller='core.front.core.webshare' data-role='webShare' data-webShareTitle='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $shareData['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-webShareText='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $shareData['text'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-webShareUrl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $shareData['url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_share_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function x( $url, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="https://x.com/share?url=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsShareLink ipsShareLink--x" target="_blank" data-role="shareLink" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip rel='nofollow noopener'>
    <i class="fa-brands fa-x-twitter"></i>
</a>
IPSCONTENT;

		return $return;
}}