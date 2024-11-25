<?php
namespace IPS\Theme;
class class_core_front_myAttachments extends \IPS\Theme\Template
{	function rows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $attachment ):
$return .= <<<IPSCONTENT

	<div class='ipsData__item'>
		<a href="
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
applications/core/interface/file/attachment.php?id=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachment['attach_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $attachment['attach_security_key'] ):
$return .= <<<IPSCONTENT
&key=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachment['attach_security_key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsData__image i-basis_70">
			
IPSCONTENT;

if ( $attachment['attach_is_image'] ):
$return .= <<<IPSCONTENT

				<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Attachment", $attachment['attach_location'] )->url;
$return .= <<<IPSCONTENT
" alt='' data-ipsLightbox data-ipsLightbox-group="myAttachments" loading="lazy">
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getIconFromName( $attachment['attach_file'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</a>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<h4 class='ipsData__title'>
					<a href="
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
applications/core/interface/file/attachment.php?id=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachment['attach_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $attachment['attach_security_key'] ):
$return .= <<<IPSCONTENT
&key=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachment['attach_security_key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachment['attach_file'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				</h4>
				<p class='ipsData__meta'>
					
IPSCONTENT;

if ( !$attachment['attach_is_image'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$pluralize = array( $attachment['attach_hits'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'attach_hits_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $attachment['attach_filesize'] );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $attachment['attach_date'] )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachment_uploaded', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

				</p>
			</div>
			<div class='i-basis_380 i-color_soft'>
				{$attachment['attach_content']}
			</div>
		</div>
		
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

		<div class='ipsData__mod'>
			<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachment['attach_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"  data-state='' class="ipsInput ipsInput--toggle">
		</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function template( $table, $used, $count ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsPageHeader ipsPageHeader--my-attachments'>
	<h1 class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
</header>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_attach_max'] > 0 ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$percentage = round( ( $used / ( \IPS\Member::loggedIn()->group['g_attach_max'] * 1024 ) ) * 100 );
$return .= <<<IPSCONTENT

	<div class='ipsBox i-padding_3 i-margin-top_3'>
		<h2 class='ipsMinorTitle i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachment_quota', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (<span data-role="percentage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $percentage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>%)</h2>
		<meter class='ipsMeter' max='100' high='90' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $percentage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></meter>
		<small class='i-color_soft i-display_block i-margin-top_1'>
			
IPSCONTENT;

$sprintf = array(\IPS\Output\Plugin\Filesize::humanReadableFilesize( $used ), \IPS\Output\Plugin\Filesize::humanReadableFilesize( \IPS\Member::loggedIn()->group['g_attach_max'] * 1024 )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachments_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		</small>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='ipsBox i-margin-top_3'>
	<h2 class='ipsBox__header'>
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachments_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
	{$table}
</div>
IPSCONTENT;

		return $return;
}}