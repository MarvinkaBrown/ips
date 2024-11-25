<?php
namespace IPS\Theme;
class class_core_global_global extends \IPS\Theme\Template
{	function advertisementImage( $advertisement, $acpLink=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $advertisement->_images ) ):
$return .= <<<IPSCONTENT

<div class='ips
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\IPS::mb_ucfirst(\IPS\SUITE_UNIQUE_KEY), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 i-text-align_center'>
	<ul>
		
IPSCONTENT;

$hmacKey = hash_hmac( "sha256", $advertisement->link, \IPS\Settings::i()->site_secret_key . 'a' );
$return .= <<<IPSCONTENT

		<li class='ips
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\IPS::mb_ucfirst(\IPS\SUITE_UNIQUE_KEY), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_large ipsResponsive_showDesktop'>
			
IPSCONTENT;

if ( $advertisement->link ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;

if ( $acpLink ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $acpLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=redirect&do=advertisement&ad={$advertisement->id}&key={$hmacKey}", "front", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $advertisement->new_window ):
$return .= <<<IPSCONTENT
target='_blank'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 rel='nofollow noopener'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<img src='
IPSCONTENT;

$return .= \IPS\File::get( $advertisement->storageExtension(), $advertisement->_images['large'] )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

if ( $advertisement->image_alt ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->image_alt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'advertisement_alt', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $acpLink ):
$return .= <<<IPSCONTENT
style="max-width:100%;max-width:min(100%, 300px);"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 loading="lazy">
			
IPSCONTENT;

if ( $advertisement->link ):
$return .= <<<IPSCONTENT

				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
		
IPSCONTENT;

if ( !$acpLink  ):
$return .= <<<IPSCONTENT

		<li class='ips
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\IPS::mb_ucfirst(\IPS\SUITE_UNIQUE_KEY), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_medium ipsResponsive_showTablet'>
			
IPSCONTENT;

if ( $advertisement->link ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;

if ( $acpLink ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $acpLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=redirect&do=advertisement&ad={$advertisement->id}&key={$hmacKey}", "front", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $advertisement->new_window ):
$return .= <<<IPSCONTENT
target='_blank'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 rel='nofollow noopener'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<img src='
IPSCONTENT;

if ( !empty( $advertisement->_images['medium'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\File::get( $advertisement->storageExtension(), $advertisement->_images['medium'] )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\File::get( $advertisement->storageExtension(), $advertisement->_images['large'] )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

if ( $advertisement->image_alt ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->image_alt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'advertisement_alt', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $acpLink ):
$return .= <<<IPSCONTENT
style="max-width:100%;max-width:min(100%, 300px);"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 loading="lazy">
			
IPSCONTENT;

if ( $advertisement->link ):
$return .= <<<IPSCONTENT

				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>

		<li class='ips
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\IPS::mb_ucfirst(\IPS\SUITE_UNIQUE_KEY), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_small ipsResponsive_showPhone'>
			
IPSCONTENT;

if ( $advertisement->link ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;

if ( $acpLink ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $acpLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=redirect&do=advertisement&ad={$advertisement->id}&key={$hmacKey}", "front", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $advertisement->new_window ):
$return .= <<<IPSCONTENT
target='_blank'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 rel='nofollow noopener'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<img src='
IPSCONTENT;

if ( !empty( $advertisement->_images['small'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\File::get( $advertisement->storageExtension(), $advertisement->_images['small'] )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( !empty( $advertisement->_images['medium'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\File::get( $advertisement->storageExtension(), $advertisement->_images['medium'] )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\File::get( $advertisement->storageExtension(), $advertisement->_images['large'] )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

if ( $advertisement->image_alt ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->image_alt, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'advertisement_alt', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $acpLink ):
$return .= <<<IPSCONTENT
style="max-width:100%;max-width:min(100%, 300px);"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 loading="lazy">
			
IPSCONTENT;

if ( $advertisement->link ):
$return .= <<<IPSCONTENT

				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
	
IPSCONTENT;

if ( $acpLink ):
$return .= <<<IPSCONTENT

		<div class="i-font-size_-1 i-margin-top_3 i-font-weight_500"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $acpLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="noreferrer" class="i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $acpLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
	
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

	function applePieChart( $segments, $sorted=TRUE, $classes='' ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $segments ) ):
$return .= <<<IPSCONTENT

    <div class='ipsPieBar 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
        <div class='ipsPieBar__bar'>
            
IPSCONTENT;

foreach ( $segments as $segment ):
$return .= <<<IPSCONTENT

                <div class='ipsPieBar__barSegment' style='width: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( $segment['percentage'], 2, '.', '' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%' 
                    
IPSCONTENT;

if ( !empty( $segment['title'] ) || !empty( $segment['tooltip'] ) ):
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

if ( !empty( $segment['title'] ) ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $segment['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( !empty( $segment['tooltip'] ) ):
$return .= <<<IPSCONTENT
data-ipsTooltip data-ipsTooltip-label="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $segment['tooltip'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !empty( $segment['tooltipSafe'] ) ):
$return .= <<<IPSCONTENT
data-ipsTooltip-safe
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                        title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $segment['value'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $segment['percentage'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)"
                        data-ipsTooltip
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                ></div>
            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        </div>
        <ul class='ipsList ipsList--inline ipsPieBar__legend'>
            
IPSCONTENT;

foreach ( $segments as $segment ):
$return .= <<<IPSCONTENT

                <li class='ipsPieBar__legendItem'>
                    <span class='ipsPieBar__legendItemKey'></span>
                    
IPSCONTENT;

if ( isset( $segment['nameRaw'] ) ):
$return .= <<<IPSCONTENT
{$segment['nameRaw']}
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $segment['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                </li>
            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        </ul>
    </div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <p class='i-color_soft i-text-align_center'>
        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_data', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function badge( $badge, $cssClass = NULL, $tooltip = TRUE, $showRare = FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<span class='i-position_relative'>
    <img src='
IPSCONTENT;

if ( $badge->badge_use_image ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\File::get( "core_Badges", $badge->image )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge->_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' loading="lazy" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cssClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $tooltip ):
$return .= <<<IPSCONTENT
data-ipsTooltip title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
    
IPSCONTENT;

if ( $showRare && $badge->isRare() ):
$return .= <<<IPSCONTENT

        <span class='ipsBadge ipsBadge--rare'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rare_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</span>
IPSCONTENT;

		return $return;
}

	function basicHover( $message ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $message, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function basicUrl( $url, $newWindow=TRUE, $title=NULL, $wordbreak=TRUE, $nofollow=FALSE, $noreferrer=FALSE, $titleRaw = FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $wordbreak ):
$return .= <<<IPSCONTENT
<div class=''>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

if ( $newWindow === TRUE ):
$return .= <<<IPSCONTENT
 target='_blank' 
IPSCONTENT;

if ( $nofollow === FALSE ):
$return .= <<<IPSCONTENT
rel="
IPSCONTENT;

if ( $noreferrer ):
$return .= <<<IPSCONTENT
noreferrer
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
noopener
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $nofollow === TRUE ):
$return .= <<<IPSCONTENT
 rel="nofollow
IPSCONTENT;

if ( $newWindow === TRUE ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $noreferrer ):
$return .= <<<IPSCONTENT
noreferrer
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
noopener
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( $title ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $titleRaw ):
$return .= <<<IPSCONTENT

			 {$title}
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</a>

IPSCONTENT;

if ( $wordbreak ):
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function chart( $chart, $type, $options, $format=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $chart->errors ) AND \count( $chart->errors ) ):
$return .= <<<IPSCONTENT

	<div class='ipsMessage ipsMessage--error'>
		
IPSCONTENT;

foreach ( $chart->errors as $error ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset($error['sprintf']) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$val = "{$error['string']}"; $sprintf = array($error['sprintf']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$val = "{$error['string']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
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

<table class="ipsTable" data-ipsChart data-ipsChart-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsChart-extraOptions='{$options}' 
IPSCONTENT;

if ( $format ):
$return .= <<<IPSCONTENT
data-ipsChart-format='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $format, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<thead>
		<tr>
			
IPSCONTENT;

foreach ( $chart->headers as $data ):
$return .= <<<IPSCONTENT

				<th data-colType="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['label'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</th>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</tr>
	</thead>
	<tbody>
		
IPSCONTENT;

foreach ( $chart->rows as $row ):
$return .= <<<IPSCONTENT

			<tr>
				
IPSCONTENT;

foreach ( $row as $value ):
$return .= <<<IPSCONTENT

					<td 
IPSCONTENT;

if ( \is_array( $value ) ):
$return .= <<<IPSCONTENT
data-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

if ( \is_array( $value ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['value'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</td>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</tr>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</tbody>
</table>
<div class="ipsChart"></div>
IPSCONTENT;

		return $return;
}

	function chartTimezoneInfo( $mysqlTimezone ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	
IPSCONTENT;

$sprintf = array($mysqlTimezone); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dynamic_chart_timezone_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function decision( $blurb, $options ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-background_2 i-padding_3 i-text-align_center'>
	<div class='i-font-size_2'><strong>
IPSCONTENT;

$val = "{$blurb}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></div>
	<br><br>
	<ul class='ipsList ipsList--inline'>
		
IPSCONTENT;

foreach ( $options as $lang => $link ):
$return .= <<<IPSCONTENT

			<li>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function dynamicChart( $chart, $html ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsChart ipsBox' data-controller='core.admin.core.dynamicChart' data-chart-url='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->baseURL, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-chart-identifier='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-chart-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-chart-timescale="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->timescale, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-chart-customfilter-submitted='
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->filter_form_submitted ) ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	<div class='i-padding_2 i-background_2 i-border-bottom_3 ipsChart_filters i-flex i-align-items_center i-flex-wrap_wrap i-gap_1'>
		<div class='i-flex_91'>
			
IPSCONTENT;

if ( $chart->title ):
$return .= <<<IPSCONTENT

				<h2 class='ipsTitle ipsTitle--h4'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $chart->description ):
$return .= <<<IPSCONTENT

				<p class="i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<ul class='ipsButtons ipsButtons--end'>
			
IPSCONTENT;

if ( $chart->showIntervals OR $chart->showDateRange OR $chart->showFilterTabs ):
$return .= <<<IPSCONTENT

				<li>
					<button data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save_chart_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small' data-role='saveReport' 
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->chartId OR \IPS\Widget\Request::i()->chartId == '_default' ):
$return .= <<<IPSCONTENT
data-ipsMenu data-ipsMenu-closeOnClick='false'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-chartId='
IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->chartId ) ? htmlspecialchars( \IPS\Widget\Request::i()->chartId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
FilterSave'><i class='fa-solid fa-plus'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save_chart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Widget\Request::i()->chartId AND \IPS\Widget\Request::i()->chartId != '_default' ):
$return .= <<<IPSCONTENT

				<li>
					<a data-confirm href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->baseURL->setQueryString( array( 'deleteChart' => \IPS\Request::i()->chartId ) )->csrf() , ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton--small ipsButton ipsButton--negative'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mychart_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<button data-role="renameChart" class='ipsButton--small ipsButton ipsButton--inherit' data-ipsMenu data-ipsMenu-closeOnClick='false' id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
FilterRename'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_rename', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					<ul id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
FilterRename_menu' class='ipsMenu ipsMenu_auto ipsHide' data-role="filterRenameMenu">
						<li class='ipsMenu_item'>{$chart->form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'forms', 'core', 'front' ), 'popupTemplate' ) )}</li>
					</ul>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
		<ul class='ipsButtons ipsButtons--end i-flex_11'>
			
IPSCONTENT;

if ( $chart->showIntervals ):
$return .= <<<IPSCONTENT

				<li data-role="groupingButtons">
					<ul class="ipsButtonGroup ipsButton--small">
						
IPSCONTENT;

if ( $chart->enableHourly ):
$return .= <<<IPSCONTENT

							<li><a class='ipsButton 
IPSCONTENT;

if ( $chart->timescale == 'hourly' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $chart->type == 'Table' ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->url->setQueryString( array( 'timescale' => array( $chart->identifier => 'hourly' ), 'noheader' => 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-timescale="hourly" 
IPSCONTENT;

if ( $chart->timescale == 'hourly' ):
$return .= <<<IPSCONTENT
data-selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_date_group_hourly', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( array( 'daily', 'weekly', 'monthly' ) as $k ):
$return .= <<<IPSCONTENT

							<li><a class='ipsButton 
IPSCONTENT;

if ( $chart->timescale == $k ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $chart->type == 'Table' ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->url->setQueryString( array( 'timescale' => array( $chart->identifier => $k ), 'noheader' => 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-timescale="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $chart->timescale == $k ):
$return .= <<<IPSCONTENT
data-selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "stats_date_group_$k"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $chart->showDateRange ):
$return .= <<<IPSCONTENT

            <li>
                <a data-action='chartDate' data-ipsMenu data-ipsMenu-closeOnBlur='false' data-ipsMenu-closeOnClick='false' id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Date' href="#" class="ipsButton ipsButton--small ipsButton--soft"><i class='fa-solid fa-calendar'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_date_range', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span data-role='dateSummary' class='i-color_soft'>
IPSCONTENT;

if ( $chart->start AND $chart->end ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$sprintf = array($chart->start->localeDate(), $chart->end->localeDate()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_betweenXandX', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

elseif ( $chart->start ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$sprintf = array($chart->start->localeDate()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_afterX', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

elseif ( $chart->end ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$sprintf = array($chart->end->localeDate()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_beforeX', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span> <i class='fa-solid fa-caret-down'></i></a>
                <div id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Date_menu' class='ipsMenu ipsMenu_normal ipsHide i-padding_3'>
                    <form accept-charset='utf-8' action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-role="dateForm" data-ipsForm>
                        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->date( 'start', $chart->start ?: NULL, FALSE, NULL, NULL, FALSE, FALSE, NULL, NULL, NULL, array(), TRUE, 'ipsInput--fullWidth', \IPS\Member::loggedIn()->language()->addToStack('stats_start_date') );
$return .= <<<IPSCONTENT

                        <br><br>
                        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->date( 'end', $chart->end ?: NULL, FALSE, NULL, NULL, FALSE, FALSE, NULL, NULL, NULL, array(), TRUE, 'ipsInput--fullWidth', \IPS\Member::loggedIn()->language()->addToStack('stats_end_date') );
$return .= <<<IPSCONTENT

                        <br><br>
                        <button type="submit" class="ipsButton ipsButton--primary ipsButton--wide" data-role="updateDate">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
                    </form>
                </div>
            </li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $chart->showSave and count( $chart->availableFilters ) ):
$return .= <<<IPSCONTENT

				<li>
					<a data-action="chartFilter" data-ipsMenu data-ipsMenu-selectable data-ipsMenu-closeOnClick='false' id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Filter' href="#" class="ipsButton ipsButton--small ipsButton--soft"><i class='fa-solid fa-filter'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_chart_filters', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
					<ul id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Filter_menu' class='ipsMenu ipsMenu_selectable ipsMenu_auto ipsHide' data-role='filterMenu'>
						<!-- Removed as there weren't enough options to warrant an All/None feature -->
						<!-- <li class='ipsMenu_item ipsMenu_itemNonSelect' data-noselect>
							<a href='#' data-role='selectAll'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
						<li class='ipsMenu_item ipsMenu_itemNonSelect' data-noselect>
							<a href='#' data-role='unselectAll'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li> -->
						
IPSCONTENT;

foreach ( $chart->availableFilters as $f => $name ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item 
IPSCONTENT;

if ( \in_array( $f, $chart->currentFilters ) ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $f, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->flipUrlFilter( $f ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( \is_array( $name ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name['value'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						<li class='ipsMenu_itemNonSelect i-margin-top_2' data-noselect>
							<span>
								<button disabled class='ipsButton ipsButton--small ipsButton--primary ipsButton--wide' data-role="applyFilters">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'apply_filters', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
							</span>
						</li>
					</ul>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $chart->customFiltersForm ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$customFilterFormTitle = $chart->customFiltersForm['title'] ?? 'chart_customfilters_title';
$return .= <<<IPSCONTENT

				<li>
					<a href="#elCustomFiltersForm" class="ipsButton ipsButton--small ipsButton--soft" data-ipsDialog data-ipsDialog-size="narrow" data-ipsDialog-title="
IPSCONTENT;

$val = "{$customFilterFormTitle}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsDialog-content="#el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
CustomFiltersForm"><i class='fa-solid fa-chart-column'></i> 
IPSCONTENT;

$val = "{$customFilterFormTitle}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<div id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
CustomFiltersForm' class='i-background_2 i-padding_3 ipsJS_hide'>
						{$chart->getCustomFiltersForm()->customTemplate( array( \IPS\Theme::i()->getTemplate( 'forms', 'core', 'front' ), 'popupTemplate' ) )}
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $chart->options['limitSearch'] ) ):
$return .= <<<IPSCONTENT

				<li>
					<a data-action='chartSearch' data-ipsMenu data-ipsMenu-closeOnBlur='false' data-ipsMenu-closeOnClick='false' id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Search' href="#" class="ipsButton ipsButton--small ipsButton--soft">
IPSCONTENT;

$val = "{$chart->options['limitSearch']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span data-role='searchSummary' class='i-color_soft'></span> <i class='fa-solid fa-caret-down'></i></a>
					<div id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Search_menu' class='ipsMenu ipsMenu_normal ipsHide i-padding_3'>
						<form accept-charset='utf-8' action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-role="searchForm" data-ipsForm>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->text( 'search', 'text', NULL, FALSE );
$return .= <<<IPSCONTENT

							<br><br>
							<button type="submit" class="ipsButton ipsButton--primary ipsButton--wide" data-role="updateSearch">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
							<button type="submit" value='reset' class="ipsButton ipsButton--soft ipsButton--wide i-margin-top_2" data-role="clearSearchTerm">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_search_reset', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
						</form>
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $chart->showSave ):
$return .= <<<IPSCONTENT

				<li>
                    <button class='ipsButton ipsButton--small ipsButton--primary ipsHide' data-role='saveReport' 
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->chartId OR \IPS\Widget\Request::i()->chartId == '_default' ):
$return .= <<<IPSCONTENT
data-ipsMenu data-ipsMenu-closeOnClick='false'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-chartId='
IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->chartId ) ? htmlspecialchars( \IPS\Widget\Request::i()->chartId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
FilterSave'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->chartId OR \IPS\Widget\Request::i()->chartId == '_default' ):
$return .= <<<IPSCONTENT

						<ul id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->identifier, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
FilterSave_menu' class='ipsMenu ipsMenu_auto ipsHide' data-role='filterSaveMenu'>
							<li class='ipsMenu_item'>{$chart->form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'forms', 'core', 'front' ), 'popupTemplate' ) )}</li>
						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $chart instanceof \IPS\Helpers\Chart\Dynamic ):
$return .= <<<IPSCONTENT

				<li>
					<a class='ipsButton ipsButton--soft ipsButton--small' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->url->setQueryString(array( "download" => 1 ))->csrf() , ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_as_csv', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='downloadChart'><i class="fa-solid fa-download"></i> CSV</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $chart->availableTypes ) > 1 ):
$return .= <<<IPSCONTENT

				<li>
					<div class="ipsButtonGroup ipsButton--small">
						
IPSCONTENT;

foreach ( $chart->availableTypes as $t ):
$return .= <<<IPSCONTENT

							<a class='ipsButton 
IPSCONTENT;

if ( $chart->type == $t ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $chart->url->setQueryString( array( 'type' => array( $chart->identifier => $t ), 'noheader' => 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title='
IPSCONTENT;

$val = "chart_{$t}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $t, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $chart->type == $t ):
$return .= <<<IPSCONTENT
data-selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								
IPSCONTENT;

if ( $t === 'Table' ):
$return .= <<<IPSCONTENT

									<i class="fa-solid fa-table"></i>
								
IPSCONTENT;

elseif ( $t === 'LineChart' ):
$return .= <<<IPSCONTENT

									<i class="fa-solid fa-chart-line"></i>
								
IPSCONTENT;

elseif ( $t == 'AreaChart' ):
$return .= <<<IPSCONTENT

									<i class='fa-solid fa-chart-area'></i>
								
IPSCONTENT;

elseif ( $t === 'ColumnChart' ):
$return .= <<<IPSCONTENT

									<i class="fa-solid fa-chart-column"></i>
								
IPSCONTENT;

elseif ( $t === 'BarChart' ):
$return .= <<<IPSCONTENT

									<i class="fa-solid fa-chart-column fa-rotate-90"></i>
								
IPSCONTENT;

elseif ( $t === 'PieChart' ):
$return .= <<<IPSCONTENT

									<i class="fa-solid fa-pie-chart"></i>
								
IPSCONTENT;

elseif ( $t === 'GeoChart' ):
$return .= <<<IPSCONTENT

									<i class="fa-solid fa-earth-americas"></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</a>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
		
	</div>
	<div class='ipsChart__chart i-padding_3' data-role="chart">
		{$html}
	</div>
</div>

IPSCONTENT;

if ( $chart->timezoneError and \IPS\Member::loggedIn()->isAdmin() ):
$return .= <<<IPSCONTENT

	<p class="i-font-size_-2 i-color_soft i-padding_2"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dynamic_chart_timezone_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $chart->hideTimezoneLink === FALSE ):
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=chartTimezones", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dynamic_chart_timezone_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'learn_more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function googleMap( $mapParameters ) {
		$return = '';
		$return .= <<<IPSCONTENT




IPSCONTENT;

$width = $mapParameters['width'] ?? 500;
$return .= <<<IPSCONTENT


IPSCONTENT;

$height = $mapParameters['height'] ?? 500;
$return .= <<<IPSCONTENT

<div data-controller="ips.core.map.googlemap" data-map-data='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $mapParameters ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style="width:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px; height: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px; max-width: 100%; max-height: 100%; overflow: hidden;" >
    
IPSCONTENT;

if ( isset($mapParameters['lat']) and isset($mapParameters['long'])  ):
$return .= <<<IPSCONTENT

    <span itemscope itemtype='http://schema.org/GeoCoordinates'>
		<meta itemprop='latitude' content='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mapParameters["lat"], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<meta itemprop='longitude' content='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mapParameters["long"], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	</span>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    <div data-role="mapContainer" style="width: 100%; height: 100%;"></div>
</div>
IPSCONTENT;

		return $return;
}

	function icon( $icon ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="ipsIcon ipsIcon--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon['type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-label="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true">{$icon['raw']}</span>
IPSCONTENT;

		return $return;
}

	function includeCSS(  ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( array_unique( \IPS\Output::i()->cssFiles, SORT_STRING ) as $file ):
$return .= <<<IPSCONTENT

	<link rel='stylesheet' href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Http\Url::external( $file )->setQueryString( 'v', \IPS\Theme::i()->cssCacheBustKey() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	<!--!Font Awesome Free 6 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
	<link rel='stylesheet' href='
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "applications/core/interface/fontawesome/css/all.min.css?v=6.5.1", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Dispatcher::i()->controllerLocation == 'front' and ! \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \IPS\Theme::i()->core_css and \IPS\Theme::i()->core_css_filename ):
$return .= <<<IPSCONTENT

		<link rel='stylesheet' href='
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", \IPS\Theme::i()->core_css_filename )->url;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Output::i()->headCss ):
$return .= <<<IPSCONTENT

		<style id="headCSS">
			
IPSCONTENT;

$return .= \IPS\Output::i()->headCss;
$return .= <<<IPSCONTENT

		</style>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<style id="themeVariables">
		
IPSCONTENT;

if ( \IPS\Theme::i()->getInlineCssVariables() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getInlineCssVariables();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</style>

	
IPSCONTENT;

if ( \IPS\Widget\Request::i()->controller != 'themeeditor' ):
$return .= <<<IPSCONTENT

		<style id="themeCustomCSS">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getCustomCssForOutput();
$return .= <<<IPSCONTENT

		</style>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->isEditingTheme() ):
$return .= <<<IPSCONTENT

		<style id="themeEditorStyles"></style>
		<style id="themeEditorTempStyles"></style>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function includeJS(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( \IPS\IN_DEV AND defined('PAGEBUILDER_DEV') AND \PAGEBUILDER_DEV ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$pagebuilderDevUrl = defined('PAGEBUILDER_DEV_URL') ? \PAGEBUILDER_DEV_URL : 'http://localhost:5173';
$return .= <<<IPSCONTENT

        <script type="module">
            import RefreshRuntime from '{$pagebuilderDevUrl}/@react-refresh'
            RefreshRuntime.injectIntoGlobalHook(window)
            window.\$RefreshReg\$ = () => {}
            window.\$RefreshSig\$ = () => (type) => type
            window.__vite_plugin_react_preamble_installed__ = true
        </script>
        <script type="module" src="{$pagebuilderDevUrl}/@vite/client"></script>
        <script type="module" src="{$pagebuilderDevUrl}/src/main.jsx"></script>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( \IPS\IN_DEV AND defined('EDITOR_DEV') AND \EDITOR_DEV ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$editorURL = defined('EDITOR_DEV_URL') ? \EDITOR_DEV_URL : 'http://localhost:5174';
$return .= <<<IPSCONTENT

        <script type="module">
            import RefreshRuntime from '{$editorURL}/@react-refresh'
            RefreshRuntime.injectIntoGlobalHook(window)
            window.\$RefreshReg$ = () => {}
            window.\$RefreshSig$ = () => (type) => type
            window.__vite_plugin_react_preamble_installed__ = true
        </script>
        <script type="module" src="{$editorURL}/@vite/client"></script>
        <script type="module" src="{$editorURL}/src/main.jsx"></script>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$maxImageDims = \IPS\Settings::i()->attachment_image_size ? explode( 'x', \IPS\Settings::i()->attachment_image_size ) : array( 1000, 750 );
$return .= <<<IPSCONTENT

	<script>
		var ipsDebug = 
IPSCONTENT;

if ( ( \IPS\IN_DEV and \IPS\DEV_DEBUG_JS ) or \IPS\DEBUG_JS ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
;
		var ipsSettings = {
			
IPSCONTENT;

if ( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation == 'admin' ):
$return .= <<<IPSCONTENT

			isAcp: true,
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\COOKIE_DOMAIN !== NULL ):
$return .= <<<IPSCONTENT

			cookie_domain: "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\COOKIE_DOMAIN, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
",
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			cookie_path: "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Request::getCookiePath(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
",
			
IPSCONTENT;

if ( \IPS\COOKIE_PREFIX !== NULL ):
$return .= <<<IPSCONTENT

			cookie_prefix: "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\COOKIE_PREFIX, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
",
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( mb_substr( \IPS\Settings::i()->base_url, 0, 5 ) == 'https' AND \IPS\COOKIE_BYPASS_SSLONLY !== TRUE ):
$return .= <<<IPSCONTENT

			cookie_ssl: true,
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			cookie_ssl: false,
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            essential_cookies: 
IPSCONTENT;

$return .= json_encode( \IPS\Request::getEssentialCookies());
$return .= <<<IPSCONTENT
,
			upload_imgURL: "
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "notifyIcons/upload.png", "core", 'front', false );
$return .= <<<IPSCONTENT
",
			message_imgURL: "
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "notifyIcons/message.png", "core", 'front', false );
$return .= <<<IPSCONTENT
",
			notification_imgURL: "
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "notifyIcons/notification.png", "core", 'front', false );
$return .= <<<IPSCONTENT
",
			baseURL: "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Http\Url::baseUrl( \IPS\Http\Url::PROTOCOL_RELATIVE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
",
			jsURL: "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( rtrim( \IPS\Http\Url::baseUrl( \IPS\Http\Url::PROTOCOL_RELATIVE ), '/' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/applications/core/interface/js/js.php",
			uploadBaseURLs: 
IPSCONTENT;

$return .= json_encode( \IPS\File::baseUrls() );
$return .= <<<IPSCONTENT
,
			csrfKey: "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
",
			antiCache: "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Theme::i()->cssCacheBustKey(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
",
			jsAntiCache: "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output\Javascript::javascriptCacheBustKey(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
",
			disableNotificationSounds: true,
			useCompiledFiles: 
IPSCONTENT;

if ( \IPS\IN_DEV ):
$return .= <<<IPSCONTENT
false
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
true
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
,
			links_external: 
IPSCONTENT;

if ( \IPS\Settings::i()->links_external  ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
,
			memberID: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( ( \IPS\Member::loggedIn()->member_id ) ? \IPS\Member::loggedIn()->member_id : 0, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
,
			blankImg: "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Text\Parser::blankImage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
",
			googleAnalyticsEnabled: 
IPSCONTENT;

if ( \IPS\Settings::i()->ga_enabled  ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
,
			matomoEnabled: 
IPSCONTENT;

if ( \IPS\Settings::i()->matomo_enabled  ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
,
			viewProfiles: 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) ) ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
,
			mapProvider: 
IPSCONTENT;

if ( \IPS\Settings::i()->googlemaps and \IPS\Settings::i()->google_maps_api_key ):
$return .= <<<IPSCONTENT
'google'
IPSCONTENT;

elseif ( \IPS\Settings::i()->mapbox and \IPS\Settings::i()->mapbox_api_key ):
$return .= <<<IPSCONTENT
'mapbox'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
'none'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
,
			mapApiKey: 
IPSCONTENT;

if ( \IPS\Settings::i()->googlemaps and \IPS\Settings::i()->google_maps_api_key ):
$return .= <<<IPSCONTENT
"
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->google_maps_api_key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

elseif ( \IPS\Settings::i()->mapbox and \IPS\Settings::i()->mapbox_api_key ):
$return .= <<<IPSCONTENT
"
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->mapbox_api_key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
''
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
,
			pushPublicKey: 
IPSCONTENT;

if ( \IPS\Notification::webPushEnabled() ):
$return .= <<<IPSCONTENT
"
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->vapid_public_key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
null
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
,
			relativeDates: 
IPSCONTENT;

if ( \IPS\Settings::i()->relative_dates_enable ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
,
            pagebuilderKey: "
IPSCONTENT;

$return .= json_decode( file_get_contents( \IPS\ROOT_PATH . '/applications/core/data/pagebuilder.json' ), true )['build'];
$return .= <<<IPSCONTENT
",
            ipsApps: 
IPSCONTENT;

$return .= json_encode( \IPS\IPS::$ipsApps );
$return .= <<<IPSCONTENT

		};
		
IPSCONTENT;

if ( \IPS\Settings::i()->custom_page_view_js && \IPS\Dispatcher::hasInstance() && \IPS\Dispatcher::i()->controllerLocation == 'front' ):
$return .= <<<IPSCONTENT

			ipsSettings['paginateCode'] = 
IPSCONTENT;

$return .= \IPS\Settings::i()->custom_page_view_js;
$return .= <<<IPSCONTENT
;
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !empty( $maxImageDims[0] ) AND !empty( $maxImageDims[1] ) AND ( \intval( $maxImageDims[0] ) !== 0 || \intval( $maxImageDims[1] ) !== 0 )  ):
$return .= <<<IPSCONTENT

			ipsSettings['maxImageDimensions'] = {
				width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxImageDims[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
,
				height: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $maxImageDims[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			};
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		const ipsJsFileMap = 
IPSCONTENT;

$return .= json_encode(\IPS\Output\Javascript::getJavascriptFileMap());
$return .= <<<IPSCONTENT
;
		
	</script>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() and \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation == 'front' and \IPS\Settings::i()->fb_pixel_enabled and \IPS\Settings::i()->fb_pixel_id ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$pixelId = \IPS\Settings::i()->fb_pixel_id;
$return .= <<<IPSCONTENT

<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
setTimeout( function() {
	fbq('init', '
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pixelId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
');
	
IPSCONTENT;

if ( $pixels = \IPS\core\Facebook\Pixel::i()->output() ):
$return .= <<<IPSCONTENT

	{$pixels}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

}, 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \intval( \IPS\Settings::i()->fb_pixel_delay * 1000 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 );
</script>
<!-- End Facebook Pixel Code -->

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( array_unique( array_filter( \IPS\Output::i()->jsFiles ), SORT_STRING ) as $js ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$js = \IPS\Http\Url::external( $js );
$return .= <<<IPSCONTENT

    <script
        src='
IPSCONTENT;

if ( $js->data['host'] == parse_url( \IPS\Settings::i()->base_url, PHP_URL_HOST ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $js->setQueryString( 'v', \IPS\Output\Javascript::javascriptCacheBustKey() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $js, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
        data-ips
        
IPSCONTENT;

if ( str_contains( $js, "interface/iro" ) or str_contains( $js, 'interface/tiptap' ) or str_contains( $js, "interface/pagebuilder" ) or str_contains( $js, 'interface/codehighlighting' )  ):
$return .= <<<IPSCONTENT
type="module"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( str_contains( $js, "interface/iro" ) ):
$return .= <<<IPSCONTENT
data-iro-loader
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

></script>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( array_unique( \IPS\Output::i()->jsFilesAsync, SORT_STRING ) as $js ):
$return .= <<<IPSCONTENT

    <script
        src="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Http\Url::external( $js )->setQueryString( 'v', \IPS\Output\Javascript::javascriptCacheBustKey() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
        
IPSCONTENT;

if ( str_contains( $js, 'interface/tiptap' ) or str_contains( $js, "interface/pagebuilder" ) or str_contains( $js, 'interface/codehighlighting' )  ):
$return .= <<<IPSCONTENT
type="module"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
        async
    ></script>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() and ( \count( \IPS\Output::i()->jsVars ) || \IPS\Output::i()->headJs) ):
$return .= <<<IPSCONTENT

	<script>
		
IPSCONTENT;

foreach ( \IPS\Output::i()->jsVars as $k => $v ):
$return .= <<<IPSCONTENT

			ips.setSetting( '
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
', 
IPSCONTENT;

if ( ! \is_array( $v ) ):
$return .= <<<IPSCONTENT
jQuery.parseJSON('
IPSCONTENT;

$return .= json_encode( $v, JSON_HEX_APOS );
$return .= <<<IPSCONTENT
')
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= json_encode( $v, JSON_HEX_APOS );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 );
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$hasPermission = \IPS\Helpers\Form\Editor::memberHasPermission( permission: "raw_embed" );
$return .= <<<IPSCONTENT

        ips.setSetting('maxEmbedWidth', 
IPSCONTENT;

$return .= json_encode( \IPS\Settings::i()->max_internalembed_width ?: null, JSON_HEX_APOS );
$return .= <<<IPSCONTENT
);
        ips.setSetting('allowedIframeDomains', 
IPSCONTENT;

$return .= ( $hasPermission and \IPS\Settings::i()->ipb_embed_url_filter_option ) ? json_encode(explode(',',\IPS\Settings::i()->ipb_embed_url_whitelist), JSON_HEX_APOS ) : '[]';
$return .= <<<IPSCONTENT
);
		
IPSCONTENT;

$return .= \IPS\Output::i()->headJs;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( \IPS\Lang::vleActive() ):
$return .= <<<IPSCONTENT

        <!--ipsVleWords-->
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </script>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( \IPS\Output::i()->jsonLd ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( \IPS\Output::i()->jsonLd as $object ):
$return .= <<<IPSCONTENT

<script type='application/ld+json'>

IPSCONTENT;

$return .= json_encode( $object, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS );
$return .= <<<IPSCONTENT
	
</script>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<script>
    (() => {
        let gqlKeys = 
IPSCONTENT;

$return .= json_encode(\IPS\Output::i()->graphData);
$return .= <<<IPSCONTENT
;
        for (let [k, v] of Object.entries(gqlKeys)) {
            ips.setGraphQlData(k, v);
        }
    })();
</script>

IPSCONTENT;

if ( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation == 'front' and \IPS\Theme::i()->core_js ):
$return .= <<<IPSCONTENT

<!-- Theme javascript -->
<script>
    
IPSCONTENT;

$return .= \IPS\Theme::i()->core_js;
$return .= <<<IPSCONTENT

</script>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function includeMeta(  ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT


	<meta name="viewport" content="width=device-width, initial-scale=1">
	
IPSCONTENT;

if ( !isset( \IPS\Output::i()->metaTags['og:image'] ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$shareLogos = \IPS\Settings::i()->icons_sharer_logo ? json_decode( \IPS\Settings::i()->icons_sharer_logo, true ) : array();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $shareLogos as $logo ):
$return .= <<<IPSCONTENT

			<meta property="og:image" content="
IPSCONTENT;

$return .= \IPS\File::get( "core_Icons", $logo )->url->setScheme("https");
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !isset( \IPS\Output::i()->metaTags['og:image'] ) and !\count( $shareLogos )  ):
$return .= <<<IPSCONTENT

		<meta name="twitter:card" content="summary">
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<meta name="twitter:card" content="summary_large_image">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->site_twitter_id ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( mb_substr( \IPS\Settings::i()->site_twitter_id, 0, 1 ) === '@' ):
$return .= <<<IPSCONTENT

			<meta name="twitter:site" content="
IPSCONTENT;

$return .= \IPS\Settings::i()->site_twitter_id;
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<meta name="twitter:site" content="@
IPSCONTENT;

$return .= \IPS\Settings::i()->site_twitter_id;
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( \IPS\Output::i()->metaTags as $name => $content ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $name == 'canonical' ):
$return .= <<<IPSCONTENT

			<link rel="canonical" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $name != 'title' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \is_array( $content )  ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $content as $_value  ):
$return .= <<<IPSCONTENT

						<meta 
IPSCONTENT;

if ( mb_substr( $name, 0, 3 ) === 'og:' or mb_substr( $name, 0, 3 ) === 'fb:' ):
$return .= <<<IPSCONTENT
property
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
name
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" content="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $content ):
$return .= <<<IPSCONTENT

					<meta 
IPSCONTENT;

if ( mb_substr( $name, 0, 3 ) === 'og:' or mb_substr( $name, 0, 3 ) === 'fb:' ):
$return .= <<<IPSCONTENT
property
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
name
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" content="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				
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

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( \IPS\Output::i()->linkTags as $type => $value ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \is_array( $value ) ):
$return .= <<<IPSCONTENT

			<link 
IPSCONTENT;

foreach ( $value as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
/>
		
IPSCONTENT;

elseif ( $type != 'canonical' OR !isset( \IPS\Output::i()->metaTags['canonical'] ) ):
$return .= <<<IPSCONTENT

			<link rel="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( \IPS\Output::i()->rssFeeds as $title => $url ):
$return .= <<<IPSCONTENT
<link rel="alternate" type="application/rss+xml" title="
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Output::i()->base ):
$return .= <<<IPSCONTENT

		<base target="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->base, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$manifest = json_decode( \IPS\Settings::i()->manifest_details, TRUE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$manifest['cache_key'] = isset($manifest['cache_key']) ? $manifest['cache_key'] : time();
$return .= <<<IPSCONTENT

	<link rel="manifest" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=metatags&do=manifest", "front", "manifest", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
	<meta name="msapplication-config" content="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=metatags&do=iebrowserconfig", "front", "iebrowserconfig", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
	<meta name="msapplication-starturl" content="
IPSCONTENT;

if ( ! empty( $manifest['start_url'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $manifest['start_url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
/
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<meta name="application-name" content="
IPSCONTENT;

if ( ! empty( $manifest['name'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $manifest['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<meta name="apple-mobile-web-app-title" content="
IPSCONTENT;

if ( ! empty( $manifest['name'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $manifest['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

if ( isset( $manifest['theme_color'] ) ):
$return .= <<<IPSCONTENT

		<meta name="theme-color" content="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $manifest['theme_color'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $manifest['background_color'] ) ):
$return .= <<<IPSCONTENT

		<meta name="msapplication-TileColor" content="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $manifest['background_color'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->icons_mask_icon AND \IPS\Settings::i()->icons_mask_color ):
$return .= <<<IPSCONTENT

		<link rel="mask-icon" href="
IPSCONTENT;

$return .= \IPS\File::get( "core_Icons", \IPS\Settings::i()->icons_mask_icon )->url->setQueryString( 'v', $manifest['cache_key']);
$return .= <<<IPSCONTENT
" color="
IPSCONTENT;

$return .= \IPS\Settings::i()->icons_mask_color;
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

$homeScreen = json_decode( \IPS\Settings::i()->icons_homescreen, TRUE ) ?? array();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $homeScreen as $name => $image ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $name != 'original' ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( mb_strpos( $name, 'apple-touch-icon' ) !== FALSE ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $name === 'apple-touch-icon-57x57' ):
$return .= <<<IPSCONTENT

					<link rel="apple-touch-icon" href="
IPSCONTENT;

$return .= \IPS\File::get( "core_Icons", $image['url'] )->url->setQueryString( 'v', $manifest['cache_key']);
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<link rel="apple-touch-icon" sizes="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['width'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
x
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['height'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;

$return .= \IPS\File::get( "core_Icons", $image['url'] )->url->setQueryString( 'v', $manifest['cache_key']);
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( mb_strpos( $name, 'msapplication' ) !== FALSE ):
$return .= <<<IPSCONTENT

				<meta name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" content="
IPSCONTENT;

$return .= \IPS\File::get( "core_Icons", $image['url'] )->url->setQueryString( 'v', $manifest['cache_key']);
$return .= <<<IPSCONTENT
"/>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<link rel="icon" sizes="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['width'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
x
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['height'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;

$return .= \IPS\File::get( "core_Icons", $image['url'] )->url->setQueryString( 'v', $manifest['cache_key']);
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

$apple = json_decode( \IPS\Settings::i()->icons_apple_startup, TRUE ) ?? array();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $apple ) ):
$return .= <<<IPSCONTENT

		<meta name="mobile-web-app-capable" content="yes">
		<meta name="apple-touch-fullscreen" content="yes">
		<meta name="apple-mobile-web-app-capable" content="yes">

		
IPSCONTENT;

foreach ( $apple as $name => $image ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $name !== 'original' ):
$return .= <<<IPSCONTENT

				<link rel="apple-touch-startup-image" media="screen and (device-width: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $image['width'] / $image['density'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px) and (device-height: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $image['height'] / $image['density'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px) and (-webkit-device-pixel-ratio: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['density'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
) and (orientation: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['orientation'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)" href="
IPSCONTENT;

$return .= \IPS\File::get( "core_Icons", $image['url'] )->url->setQueryString( 'v', $manifest['cache_key']);
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function message( $message, $type, $debug=NULL, $parse=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $debug !== NULL ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

if ( $parse ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			{$message}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<br><br>
		<pre>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $debug, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class="ipsMessage ipsMessage--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

if ( $parse ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			{$message}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function miniPagination( $baseUrl, $pages, $activePage=1, $perPage=25, $ajax=FALSE, $pageParam='page' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $pages > 1 ):
$return .= <<<IPSCONTENT

	<ul class="ipsMiniPagination" id='elPagination_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($baseUrl), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

foreach ( range( 1, ( 4 > $pages ) ? $pages : 4 ) as $i ):
$return .= <<<IPSCONTENT

			<li hidden><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $i ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$sprintf = array($i); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_page_x', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $pages > 4 ):
$return .= <<<IPSCONTENT

			<li hidden><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $pages ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'last_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pages, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-right'></i></a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $pages ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'last_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><span><i class="fa-regular fa-file-lines"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pages, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a></li>
	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function multipleRedirect( $url, $mr=NULL, $height=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRedirect_manualButton">
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'mr' => '0', '_mrReset' => 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
<div data-controller="core.global.core.multipleRedirect" data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class="ipsRedirect ipsHide i-padding_3">
		<div class="ipsLoading ipsRedirect--loading" data-role="loadingIcon" data-loading-text="" 
IPSCONTENT;

if ( $height ):
$return .= <<<IPSCONTENT
style="height: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
px;"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		</div>
		<div class="ipsHide" data-role="progressBarContainer">
			<div class="ipsRedirect_progress" data-loading-text="">
				<div class="ipsProgress ipsProgress--animated">
					<div class="ipsProgress__progress" data-role="progressBar"></div>
				</div>
			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function offline(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<head>
		<title>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_are_offline', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 - 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->board_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>

			:root{
				--background: hsl(0 0% 94%);
				--color: hsl(0 0% 40%);
				--color-hard: hsl(0 0% 10%);
				--button--ba-co: hsl(0 0% 10%);
				--button--co: hsl(0 0% 100%);
			}

			@media (prefers-color-scheme: dark){
				:root{
					--background: hsl(0 0% 10%);
					--color: hsl(0 0% 60%);
					--color-hard: hsl(0 0% 100%);
					--button--ba-co: hsl(0 0% 95%);
					--button--co: hsl(0 0% 0%);
				}
			}

			*, *::before, *::after{
				box-sizing: border-box;
			}

			html {
				background: var(--background);
			}

			body {
				font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
				font-size: 16px;
				line-height: 1.5;
				color: var(--color);
				min-height: 100vh;
				min-height: 100svh;
				display: grid;
				place-content: center;
			}

			.cOfflineBox{
				margin: auto;
				max-width: min(90vw, 500px);
			}

			h1{
				color: var(--color-hard);
				font-weight: 600;
				margin: 0;
				margin-bottom: .4em;
				font-size: min(1em + 3vw, 2.5em);
				line-height: 1.2;
			}

			h2{
				color: var(--color-hard);
				font-weight: 500;
				margin: 0;
				margin-bottom: .4em;
				font-size: min(1em + 1vw, 1.5em);
			}

			.ipsButton {
				border: 0;
				font-weight: 500;
				text-align: center;
				text-decoration: none;
				display: inline-flex;
				align-items: center;
				gap: .7em;
				vertical-align: middle;
				padding: .8em 1.5em;
				border-radius: 5px;
				cursor: pointer;
				user-select: none;
				background: var(--button--ba-co);
				color: var(--button--co);
				margin-top: 2em;
			}

				.ipsButton:hover{
					background-image: linear-gradient(hsl(0 0% 100% / .15) 0% 100%);
				}

			svg{
				height: 1em;
				fill: currentColor;
			}
		</style>
		<link rel='shortcut icon' href='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMCAzMCIgZmlsbD0iIzMzMzMzMyI+PHBhdGggZD0iTSAxNSAzIEMgMTQuMTY4NDMyIDMgMTMuNDU2MDYzIDMuNTA2NzIzOCAxMy4xNTQyOTcgNC4yMjg1MTU2IEwgMi4zMDA3ODEyIDIyLjk0NzI2NiBMIDIuMzAwNzgxMiAyMi45NDkyMTkgQSAyIDIgMCAwIDAgMiAyNCBBIDIgMiAwIDAgMCA0IDI2IEEgMiAyIDAgMCAwIDQuMTQwNjI1IDI1Ljk5NDE0MSBMIDQuMTQ0NTMxMiAyNiBMIDE1IDI2IEwgMjUuODU1NDY5IDI2IEwgMjUuODU5Mzc1IDI1Ljk5MjE4OCBBIDIgMiAwIDAgMCAyNiAyNiBBIDIgMiAwIDAgMCAyOCAyNCBBIDIgMiAwIDAgMCAyNy42OTkyMTkgMjIuOTQ3MjY2IEwgMjcuNjgzNTk0IDIyLjkxOTkyMiBBIDIgMiAwIDAgMCAyNy42ODE2NDEgMjIuOTE3OTY5IEwgMTYuODQ1NzAzIDQuMjI4NTE1NiBDIDE2LjU0MzkzNyAzLjUwNjcyMzggMTUuODMxNTY4IDMgMTUgMyB6IE0gMTMuNzg3MTA5IDExLjM1OTM3NSBMIDE2LjIxMjg5MSAxMS4zNTkzNzUgTCAxNi4wMTE3MTkgMTcuODMyMDMxIEwgMTMuOTg4MjgxIDE3LjgzMjAzMSBMIDEzLjc4NzEwOSAxMS4zNTkzNzUgeiBNIDE1LjAwMzkwNiAxOS44MTA1NDcgQyAxNS44MjU5MDYgMTkuODEwNTQ3IDE2LjMxODM1OSAyMC4yNTI4MTMgMTYuMzE4MzU5IDIxLjAwNzgxMiBDIDE2LjMxODM1OSAyMS43NDg4MTIgMTUuODI1OTA2IDIyLjE4OTQ1MyAxNS4wMDM5MDYgMjIuMTg5NDUzIEMgMTQuMTc1OTA2IDIyLjE4OTQ1MyAxMy42Nzk2ODggMjEuNzQ4ODEzIDEzLjY3OTY4OCAyMS4wMDc4MTIgQyAxMy42Nzk2ODggMjAuMjUyODEzIDE0LjE3NDkwNiAxOS44MTA1NDcgMTUuMDAzOTA2IDE5LjgxMDU0NyB6IiBmaWxsPSIjMzMzMzMzIi8+PC9zdmc+Cg==' type="image/svg+xml">
	</head>
	<body>
		<div class='cOfflineBox'>
			<h1>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->board_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
			<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_offline_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<p>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_offline_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
			<p>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_offline_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
			<button onclick="javascript: window.location.reload()" class='ipsButton'>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
				<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offline_try_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</button>
		</div>
	</body>
</html>
IPSCONTENT;

		return $return;
}

	function pagination( $baseUrl, $pages, $activePage=1, $perPage=25, $ajax=TRUE, $pageParam='page', $simple=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$firstPage = $baseUrl->setPage( $pageParam );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $activePage > 1 || $pages > 1 ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$uniqId = mt_rand();
$return .= <<<IPSCONTENT

	<ul class='ipsPagination 
IPSCONTENT;

if ( $pages > 5 ):
$return .= <<<IPSCONTENT
ipsPagination--numerous
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' id='elPagination_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($baseUrl), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uniqId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsPagination-seoPagination='
IPSCONTENT;

if ( $firstPage->seoPagination ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-pages='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pages, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $ajax and ( \IPS\Settings::i()->ajax_pagination or \IPS\Widget\Request::i()->isAjax()) ):
$return .= <<<IPSCONTENT
data-ipsPagination 
IPSCONTENT;

if ( $pageParam != 'page' ):
$return .= <<<IPSCONTENT
data-ipsPagination-pageParam='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pageParam, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsPagination-pages="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pages, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsPagination-perPage='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $perPage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		
IPSCONTENT;

if ( $simple ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $activePage > 1 ):
$return .= <<<IPSCONTENT

				<li class='ipsPagination__prev'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $activePage - 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="prev" data-page='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $activePage - 1, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $activePage < $pages ):
$return .= <<<IPSCONTENT

				<li class='ipsPagination__next'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $activePage + 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="next" data-page='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $activePage + 1, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $activePage != 1 ):
$return .= <<<IPSCONTENT

				<li class='ipsPagination__first'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $firstPage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="first" data-page='1' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-angles-left'></i></a></li>
				<li class='ipsPagination__prev'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $activePage - 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="prev" data-page='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $activePage - 1, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

foreach ( range( ( ( $activePage - 5 ) > 0 ) ? ( $activePage - 5 ) : 1, $activePage - 1 ) as $idx => $i ):
$return .= <<<IPSCONTENT

					<li class='ipsPagination__page'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $i ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-page='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<li class='ipsPagination__first ipsPagination__inactive'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $firstPage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="first" data-page='1' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-angles-left'></i></a></li>
				<li class='ipsPagination__prev ipsPagination__inactive'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $activePage - 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="prev" data-page='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $activePage - 1, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<li class='ipsPagination__page ipsPagination__active'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $activePage ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-page='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activePage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activePage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
			
IPSCONTENT;

if ( $activePage != $pages ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( range( $activePage + 1, ( ( $activePage + 5 ) > $pages ) ? $pages : ( $activePage + 5 ) ) as $idx => $i ):
$return .= <<<IPSCONTENT

					<li class='ipsPagination__page'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $i ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-page='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<li class='ipsPagination__next'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $activePage + 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="next" data-page='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $activePage + 1, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				<li class='ipsPagination__last'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $pages ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="last" data-page='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pages, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'last_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-angles-right'></i></a></li>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<li class='ipsPagination__next ipsPagination__inactive'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, ( $activePage + 1 > $pages ) ? $pages : $activePage + 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="next" data-page='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( ( $activePage + 1 > $pages ) ? $pages : $activePage + 1, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				<li class='ipsPagination__last ipsPagination__inactive'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( $pageParam, $pages ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="last" data-page='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pages, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'last_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-angles-right'></i></a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $pages > 1 ):
$return .= <<<IPSCONTENT

				<li class='ipsPagination__pageJump'>
					<a href='#' data-ipsMenu data-ipsMenu-closeOnClick='false' data-ipsMenu-appendTo='#elPagination_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($baseUrl), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uniqId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elPagination_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($baseUrl), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uniqId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_jump'>
IPSCONTENT;

$sprintf = array($activePage, $pages); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pagination', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 &nbsp;<i class='fa-solid fa-caret-down'></i></a>
					<div class='ipsMenu ipsMenu_narrow ipsHide' id='elPagination_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($baseUrl), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uniqId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_jump_menu'>
						<form accept-charset='utf-8' method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setPage( 'page', NULL ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="pageJump" data-baseUrl='#'>
							<ul class='i-flex i-gap_1'>
								<input type='number' min='1' max='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pages, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'page_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsInput i-flex_11 i-text-transform_none' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pageParam, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' inputmode="numeric">
								<input type='submit' class='ipsButton ipsButton--primary' value='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
							</ul>
						</form>
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
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

	function poll( $poll, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->fetchPoll ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "poll:before", [ $poll,$url ] );
$return .= <<<IPSCONTENT
<section data-ips-hook="poll" data-controller="core.front.core.poll">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "poll:inside-start", [ $poll,$url ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $poll->canVote() and \IPS\Widget\Request::i()->_poll != 'results' and ( !$poll->getVote() or \IPS\Widget\Request::i()->_poll == 'form') and $pollForm = $poll->buildForm() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "question:before", [ $poll,$url ] );
$return .= <<<IPSCONTENT
<h2 data-ips-hook="question" class="ipsInnerBox__header">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "question:inside-start", [ $poll,$url ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $poll->poll_question, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $poll->votes ):
$return .= <<<IPSCONTENT
<span class="ipsInnerBox__header-secondary"><i class="fa-regular fa-check-square"></i> 
IPSCONTENT;

$pluralize = array( $poll->votes ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_num_votes', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "question:inside-end", [ $poll,$url ] );
$return .= <<<IPSCONTENT
</h2>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "question:after", [ $poll,$url ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "contents:before", [ $poll,$url ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="contents" class="ipsInnerBox__padding" data-role="pollContents">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "contents:inside-start", [ $poll,$url ] );
$return .= <<<IPSCONTENT

			{$pollForm->customTemplate( array( \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' ), 'pollForm' ), $url, $poll )}
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "contents:inside-end", [ $poll,$url ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "contents:after", [ $poll,$url ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( ( $poll->canViewResults() and ( !$poll->canVote() or $poll->getVote() ) ) or ( \IPS\Widget\Request::i()->_poll == 'results' and $poll->canViewResults() ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "questionNoVote:before", [ $poll,$url ] );
$return .= <<<IPSCONTENT
<h2 data-ips-hook="questionNoVote" class="ipsInnerBox__header">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "questionNoVote:inside-start", [ $poll,$url ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $poll->poll_question, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $poll->votes ):
$return .= <<<IPSCONTENT
<span class="ipsInnerBox__header-secondary"><i class="fa-regular fa-check-square"></i> 
IPSCONTENT;

$pluralize = array( $poll->votes ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_num_votes', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "questionNoVote:inside-end", [ $poll,$url ] );
$return .= <<<IPSCONTENT
</h2>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "questionNoVote:after", [ $poll,$url ] );
$return .= <<<IPSCONTENT

		<div class="ipsInnerBox__padding" data-role="pollContents">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "list:before", [ $poll,$url ] );
$return .= <<<IPSCONTENT
<ol data-ips-hook="list" class="ipsPollList i-grid i-gap_4">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "list:inside-start", [ $poll,$url ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$i = 0;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $poll->choices as $questionId => $question ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$i++;
$return .= <<<IPSCONTENT

					<li>
						<h3 class="ipsTitle ipsTitle--h5 ipsTitle--margin">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
. 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question['question'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
						<ul class="ipsPollList_choices i-grid i-gap_2 i-margin-top_2">
							
IPSCONTENT;

foreach ( $question['choice'] as $k => $choice ):
$return .= <<<IPSCONTENT

								<li class="i-flex i-flex-wrap i-align-items_center i-gap_1">
									<div class="i-flex_11 i-basis_340">
										{$choice}
									</div>
									<div class="i-flex_91 i-flex i-align-items_center i-gap_3 i-row-gap_1">
										<div class="i-flex_11">
											<progress class="ipsProgress" max="100" value="
IPSCONTENT;

if ( array_sum( $question['votes'] ) > 0  ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \intval( ( $question['votes'][ $k ] / array_sum( $question['votes'] ) ) * 100 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

if ( array_sum( $question['votes'] ) > 0  ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \intval( ( $question['votes'][ $k ] / array_sum( $question['votes'] ) ) * 100 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
%" data-ipstooltip></progress>
										</div>
										<div class="i-flex_01 i-font-size_-1">
											
IPSCONTENT;

if ( $poll->canSeeVoters() && $question['votes'][ $k ] > 0 ):
$return .= <<<IPSCONTENT

												<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=poll&do=voters&id={$poll->pid}&question={$questionId}&option={$k}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_voters', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="i-color_soft" data-ipstooltip data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $choice, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												<span class="i-opacity_7">
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												<i class="fa-solid fa-user"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question['votes'][ $k ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $poll->canSeeVoters() && $question['votes'][ $k ] > 0 ):
$return .= <<<IPSCONTENT

												</span></a>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</div>
									</div>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "list:inside-end", [ $poll,$url ] );
$return .= <<<IPSCONTENT
</ol>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "list:after", [ $poll,$url ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $poll->canVote() || !\IPS\Member::loggedIn()->member_id || $poll->canClose() || ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and !$poll->poll_closed ) || ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and $poll->poll_closed ) ):
$return .= <<<IPSCONTENT

				<hr class="ipsHr">

				
IPSCONTENT;

if ( $poll->poll_closed or ( $poll->poll_close_date instanceof \IPS\DateTime ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "closed:before", [ $poll,$url ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="closed" class="i-padding_2 i-font-weight_600 i-color_soft">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "closed:inside-start", [ $poll,$url ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $poll->poll_closed ):
$return .= <<<IPSCONTENT

							<p><i class="fa-solid fa-lock"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_closed_for_votes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and !$poll->poll_closed ):
$return .= <<<IPSCONTENT

							<p>
IPSCONTENT;

$sprintf = array($poll->poll_close_date->localeDate(), $poll->poll_close_date->localeTime( FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_auto_closes_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

elseif ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and $poll->poll_closed ):
$return .= <<<IPSCONTENT

							<p>
IPSCONTENT;

$sprintf = array($poll->poll_close_date->localeDate(), $poll->poll_close_date->localeTime( FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_auto_closed_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "closed:inside-end", [ $poll,$url ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "closed:after", [ $poll,$url ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				    <p>
IPSCONTENT;

$sprintf = array(\IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ), \IPS\Http\Url::internal( 'app=core&module=system&controller=register', 'front', 'register' )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_guest', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "vote:before", [ $poll,$url ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="vote" class="ipsButtons ipsButtons--polls i-margin-top_2">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "vote:inside-start", [ $poll,$url ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $poll->canVote() ):
$return .= <<<IPSCONTENT

						<li>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( '_poll', 'form' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_vote_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-margin-end_auto" data-action="viewResults"><i class="fa-solid fa-caret-left"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_vote_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
					
					
IPSCONTENT;

if ( $poll->canClose() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( ! $poll->poll_closed ):
$return .= <<<IPSCONTENT

							<li><a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'pollStatus', 'value' => 0 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-lock"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<li><a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'pollStatus', 'value' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-unlock"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_open', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "vote:inside-end", [ $poll,$url ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "vote:after", [ $poll,$url ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<h2 class="ipsBox__header">
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $poll->poll_question, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $poll->votes ):
$return .= <<<IPSCONTENT
<span class="ipsBox__header-secondary"><i class="fa-regular fa-check-square"></i> 
IPSCONTENT;

$pluralize = array( $poll->votes ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_num_votes', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h2>
		<div class="i-padding_3" data-role="pollContents">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_permission_poll', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$sprintf = array(\IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ), \IPS\Http\Url::internal( 'app=core&module=system&controller=register', 'front', 'register' )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_guest', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


            
IPSCONTENT;

if ( $poll->canClose() || ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and !$poll->poll_closed ) || ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and $poll->poll_closed ) ):
$return .= <<<IPSCONTENT

            <hr class="ipsHr">
            <ul class="ipsButtons cPollButtons">
                
IPSCONTENT;

if ( $poll->canClose() ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( !$poll->poll_closed ):
$return .= <<<IPSCONTENT

                        <li><a class="ipsButton ipsButton--inherit ipsButton--small" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'pollStatus', 'value' => 0 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-lock"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
                    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                        <li><a class="ipsButton ipsButton--inherit ipsButton--small" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'pollStatus', 'value' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-unlock"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_open', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


                
IPSCONTENT;

if ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and !$poll->poll_closed ):
$return .= <<<IPSCONTENT

                    <li class="cPollCloseDate">
IPSCONTENT;

$sprintf = array($poll->poll_close_date->localeDate(), $poll->poll_close_date->localeTime( FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_auto_closes_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</li>
                
IPSCONTENT;

elseif ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and $poll->poll_closed ):
$return .= <<<IPSCONTENT

                    <li class="cPollCloseDate">
IPSCONTENT;

$sprintf = array($poll->poll_close_date->localeDate(), $poll->poll_close_date->localeTime( FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_auto_closed_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</li>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </ul>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->fetchPoll ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "poll:inside-end", [ $poll,$url ] );
$return .= <<<IPSCONTENT
</section>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/poll", "poll:after", [ $poll,$url ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function pollForm( $url, $poll, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--poll" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->stripQueryString( array( 'fetchPoll', 'viewResults' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
 data-ipsForm>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
		
	
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

		<ol class='ipsPollList ipsPollList--questions'>
			
IPSCONTENT;

$i = 0;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $collection as $idx => $input ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$i++;
$return .= <<<IPSCONTENT

				<li class='ipsFieldRow ipsFieldRow--noLabel'>
					<h3 class='ipsTitle ipsTitle--h5 ipsTitle--margin'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
. 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->label, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
					<div>
					
IPSCONTENT;

if ( !$input->options['multiple'] ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->radio( $input->name, $input->value, $input->required, $input->options['options'], $input->options['disabled'], '', $input->options['disabled'] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->checkboxset( $input->name, $input->value, $input->required, $input->options['options'], FALSE, NULL, $input->options['disabled'], $input->options['toggles'], NULL, NULL, 'all', array(), TRUE, isset( $input->options['descriptions'] ) ? $input->options['descriptions'] : NULL, FALSE, FALSE, $input->options['condense'] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

						<div class="ipsFieldRow__warning">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	<hr class='ipsHr'>
	
IPSCONTENT;

if ( ($poll->poll_close_date instanceof \IPS\DateTime) or $poll->poll_view_voters ):
$return .= <<<IPSCONTENT

		<div class='i-padding_2 i-font-weight_600 i-color_soft'>
			
IPSCONTENT;

if ( $poll->poll_view_voters ):
$return .= <<<IPSCONTENT

				<p><i class="fa-regular fa-eye i-margin-end_icon"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_is_public', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and !$poll->poll_closed ):
$return .= <<<IPSCONTENT

				<p><i class="fa-regular fa-clock i-margin-end_icon"></i>
IPSCONTENT;

$sprintf = array($poll->poll_close_date->localeDate(), $poll->poll_close_date->localeTime( FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_auto_closes_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

elseif ( ( $poll->poll_close_date instanceof \IPS\DateTime ) and $poll->poll_closed ):
$return .= <<<IPSCONTENT

				<p><i class="fa-regular fa-clock i-margin-end_icon"></i>
IPSCONTENT;

$sprintf = array($poll->poll_close_date->localeDate(), $poll->poll_close_date->localeTime( FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_auto_closed_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<ul class="ipsButtons i-margin-top_3">
		<li>
			<button type="submit" class="ipsButton ipsButton--primary" accesskey="s" role="button">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save_vote', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</li>
		
IPSCONTENT;

if ( $poll->canViewResults() ):
$return .= <<<IPSCONTENT

		<li><a class='ipsButton ipsButton--text i-margin-end_auto' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_results_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( '_poll' => 'results', 'nullVote' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !\IPS\Settings::i()->allow_result_view ):
$return .= <<<IPSCONTENT
data-viewResults-confirm="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_allow_result_view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-action='viewResults'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( $poll->canClose() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ! $poll->poll_closed ):
$return .= <<<IPSCONTENT

				<li><a class='ipsButton ipsButton--inherit' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'pollStatus', 'value' => 0 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-lock"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_close', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<li><a class='ipsButton ipsButton--inherit' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'do' => 'pollStatus', 'value' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-unlock"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_open', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
</form>
IPSCONTENT;

		return $return;
}

	function pollVoters( $votes ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	<div class='ipsBox'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<ul class="ipsSpanGrid i-padding_3">
			
IPSCONTENT;

foreach ( $votes as $vote ):
$return .= <<<IPSCONTENT

				<li class='ipsSpanGrid__6 ipsPhotoPanel ipsPhotoPanel--mini'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $vote->member_id ), 'mini' );
$return .= <<<IPSCONTENT

					<div>
						<h3 class='ipsTruncate_1'>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $vote->member_id )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
						<span class="i-color_soft">
IPSCONTENT;

$val = ( $vote->vote_date instanceof \IPS\DateTime ) ? $vote->vote_date : \IPS\DateTime::ts( $vote->vote_date );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function prettyprint( $code ) {
		$return = '';
		$return .= <<<IPSCONTENT

<pre class='prettyprint'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
IPSCONTENT;

		return $return;
}

	function rank( $rank, $cssClass=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<img src='
IPSCONTENT;

if ( $rank->icon AND $rank->rank_use_image ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' loading="lazy" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cssClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_rank', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['pos'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['max'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)">
IPSCONTENT;

		return $return;
}

	function redirect( $url, $message ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<head>
		<title>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'redirecting', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</title>
		<meta http-equiv="refresh" content="2; url=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<meta name="robots" content="noindex,nofollow">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeMeta(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT

	</head>
	<body>
		<p class="ipsMessage ipsMessage--info ipsRedirectMessage">
			<strong>
IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
			<br>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'redirecting_wait', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT

	</body>
</html>
IPSCONTENT;

		return $return;
}

	function referralBanner( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_ReferralBanners", $url )->url;
$return .= <<<IPSCONTENT
" class="ipsImage">
IPSCONTENT;

		return $return;
}

	function richText( $value, $extraClasses=array(), $extraControllers=array(), $extraAttributes=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation == 'front' ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$controllers = array_merge( array('core.front.core.lightboxedImages'), $extraControllers );
$return .= <<<IPSCONTENT

<div class='ipsRichText 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode(' ', $extraClasses), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode(',', $controllers), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

foreach ( $extraAttributes as $attribute ):
$return .= <<<IPSCONTENT
 {$attribute}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
>{$value}</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT


IPSCONTENT;

$controllers = $extraControllers;
$return .= <<<IPSCONTENT

<div class='ipsRichText 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode(' ', $extraClasses), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \is_array( $controllers ) AND \count( $controllers ) ):
$return .= <<<IPSCONTENT
data-controller='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode(',', $controllers), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $extraAttributes as $attribute ):
$return .= <<<IPSCONTENT
 {$attribute}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
>{$value}</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function staticMap( $linkUrl, $imageUrl, $lat=NULL, $long=NULL, $width=NULL, $height=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>

IPSCONTENT;

if ( $lat and $long ):
$return .= <<<IPSCONTENT

	<span itemscope itemtype='http://schema.org/GeoCoordinates'>
		<meta itemprop='latitude' content='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lat, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<meta itemprop='longitude' content='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $long, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( $linkUrl ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $linkUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' class='ipsImage'
IPSCONTENT;

if ( $width AND $height ):
$return .= <<<IPSCONTENT
 width='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' height='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 loading='lazy'>
IPSCONTENT;

if ( $linkUrl ):
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $linkUrl ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $linkUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel='noopener'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' class='ipsImage'
IPSCONTENT;

if ( $width AND $height ):
$return .= <<<IPSCONTENT
 width='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $width, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' height='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $height, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 loading='lazy'>
IPSCONTENT;

if ( $linkUrl ):
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function tabScrollers(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsTabs__scrollers" data-role="tabScrollers">
	<button data-direction="prev" type="button" aria-hidden="true" tabindex="-1" hidden><i class="fa-solid fa-angle-left"></i></button>
	<button data-direction="next" type="button" aria-hidden="true" tabindex="-1" hidden><i class="fa-solid fa-angle-right"></i></button>
</div>
IPSCONTENT;

		return $return;
}

	function textBlock( $message ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/textBlock", "message:before", [ $message ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="message">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/textBlock", "message:inside-start", [ $message ] );
$return .= <<<IPSCONTENT

	{$message}

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/textBlock", "message:inside-end", [ $message ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/textBlock", "message:after", [ $message ] );
$return .= <<<IPSCONTENT

<br>


IPSCONTENT;

		return $return;
}

	function titleWithLink( $title, $link=NULL, $text='more_info', $hovertitle=NULL, $parsed=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $link===NULL ):
$return .= <<<IPSCONTENT

    <strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/titleWithLink", "url:before", [ $title,$link,$text,$hovertitle,$parsed ] );
$return .= <<<IPSCONTENT
<a data-ips-hook="url" data-ipstooltip 
IPSCONTENT;

if (  $hovertitle !== NULL ):
$return .= <<<IPSCONTENT
 _title="
IPSCONTENT;

if ( !$parsed ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$hovertitle}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $hovertitle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/titleWithLink", "url:inside-start", [ $title,$link,$text,$hovertitle,$parsed ] );
$return .= <<<IPSCONTENT

        <span class="ipsType i-font-weight_bold">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> <i class="fa-solid fa-arrow-up-right-from-square ipsType i-color_soft i-font-size_-2"></i>
    
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/titleWithLink", "url:inside-end", [ $title,$link,$text,$hovertitle,$parsed ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/titleWithLink", "url:after", [ $title,$link,$text,$hovertitle,$parsed ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function truncatedUrl( $url, $newWindow=TRUE, $length=50 ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/truncatedUrl", "url:before", [ $url,$newWindow,$length ] );
$return .= <<<IPSCONTENT
<a data-ips-hook="url" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $newWindow === TRUE ):
$return .= <<<IPSCONTENT
 target="_blank" rel="noopener" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/truncatedUrl", "url:inside-start", [ $url,$newWindow,$length ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( mb_substr( html_entity_decode( $url ), '0', $length ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) . ( ( mb_strlen( html_entity_decode( $url ) ) > $length ) ? '&hellip;' : '' );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/truncatedUrl", "url:inside-end", [ $url,$newWindow,$length ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/global/global/truncatedUrl", "url:after", [ $url,$newWindow,$length ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function vineEmbed( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsEmbeddedVideo" contenteditable="false"><div><iframe class="vine-embed" src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/embed/simple" width="600" height="600"></iframe><script async src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script></div></div>
IPSCONTENT;

		return $return;
}

	function wizard( $stepNames, $activeStep, $output, $baseUrl, $showSteps ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ipsWizard class='ipsWizard'>
	
IPSCONTENT;

if ( $showSteps ):
$return .= <<<IPSCONTENT

		<ul class="ipsSteps" data-role="wizardStepbar">
			
IPSCONTENT;

$doneSteps = TRUE;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $stepNames as $step => $name ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $activeStep == $name ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$doneSteps = FALSE;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li class='ipsStep 
IPSCONTENT;

if ( $activeStep == $name ):
$return .= <<<IPSCONTENT
ipsStep--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

if ( $doneSteps ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( '_step', $name ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="wizardLink">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<strong class='ipsStep_title'>
IPSCONTENT;

$sprintf = array($step + 1); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'step_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong>
						<span class='ipsStep__desc'>
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

if ( $doneSteps ):
$return .= <<<IPSCONTENT

						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div data-role="wizardContent">
		{$output}
	</div>
</div>
IPSCONTENT;

		return $return;
}}