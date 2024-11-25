<?php
namespace IPS\Theme;
class class_nexus_front_purchases extends \IPS\Theme\Template
{	function advertisement( $purchase, $advertisement ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $advertisement->active == -1 ):
$return .= <<<IPSCONTENT

	<p class="ipsMessage ipsMessage--info">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'advertisement_pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

elseif ( $advertisement->active == 1 ):
$return .= <<<IPSCONTENT

	<p class="ipsMessage ipsMessage--success">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'advertisement_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class="ipsMessage ipsMessage--info">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'advertisement_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



<i-data>
	<ul class="ipsData ipsData--table ipsData--advertisement">
		<li class="ipsData__item">
			<span class="i-basis_120"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'advertisement_url', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<span class="ipsData__main">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		</li>
		<li class="ipsData__item">
			<span class="i-basis_120"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ads_ad_impressions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<span class="ipsData__main">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->impressions, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $advertisement->maximum_unit == 'i' and $advertisement->maximum_value != -1 ):
$return .= <<<IPSCONTENT
 / 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->maximum_value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		</li>
		<li class="ipsData__item">
			<span class="i-basis_120"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ads_ad_clicks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<span class="ipsData__main">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->clicks, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $advertisement->maximum_unit == 'c' and $advertisement->maximum_value != -1 ):
$return .= <<<IPSCONTENT
 / 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->maximum_value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		</li>
	</ul>
</i-data>

<div class="ips
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\IPS::mb_ucfirst(\IPS\SUITE_UNIQUE_KEY), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<ul class="i-text-align_center">
		
IPSCONTENT;

foreach ( json_decode( $advertisement->images, TRUE ) as $k => $image ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

				<li>
					<div class='ips
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\IPS::mb_ucfirst(\IPS\SUITE_UNIQUE_KEY), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 i-background_2 i-padding_2'>
						<div class='i-background_1 i-padding_2'>
							<a href="
IPSCONTENT;

$return .= \IPS\File::get( "core_Advertisements", $image )->url;
$return .= <<<IPSCONTENT
" class='ipsImage ipsThumb_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsLightbox data-ipsLightbox-group="ads" data-ipsLightbox-meta="
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Advertisements", $image )->url;
$return .= <<<IPSCONTENT
" loading='lazy' alt='' ></a>
                        </div>
					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function giftvoucher( $purchase, $data ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $data['method'] === 'print' ):
$return .= <<<IPSCONTENT

	<p class='ipsMessage ipsMessage--info'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_pending_print', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url()->setQueryString( 'do', 'extra' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'print_giftvoucher', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsMessage ipsMessage--info'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_pending_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<i-data>
	<ul class="ipsData ipsData--table ipsData--gift-voucher i-margin-top_3">
		<li class='ipsData__item'>
			<strong class='i-basis_120 i-text-align_end'>
				To
			</strong>
			<span class='ipsData__main'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['recipient_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $data['recipient_email'] ):
$return .= <<<IPSCONTENT
 (
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['recipient_email'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</span>
		</li>
		<li class='ipsData__item'>
			<strong class='i-basis_120 i-text-align_end'>
				Amount
			</strong>
			<span class='ipsData__main'>
				
IPSCONTENT;

$return .= new \IPS\nexus\Money( $data['amount'], $data['currency'] );
$return .= <<<IPSCONTENT

			</span>
		</li>
		<li class='ipsData__item'>
			<strong class='i-basis_120 i-text-align_end'>
				Message
			</strong>
			<div class='ipsData__main'>
				<div class=''>
					
IPSCONTENT;

if ( $data['message'] ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['message'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<em class='i-color_soft'>No message</em>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</li>
	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function giftvoucherPrint( $data ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!DOCTYPE html>
<html>
	<head>
		<title>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'print_gift_card', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</title>
		<style type='text/css'>
			body {
				font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
				font-size: 13px;
				line-height: 18px;
				margin: 0;
				padding: 30px;
				text-align: center;
			}

			#elNexusGiftcard_border {
				position: relative;
				border: 1px dashed #000;
				padding: 20px;
				display: inline-block;
				text-align: start;
				border-radius: 4px;
			}

			#elNexusGiftcard {
				width: 400px;
				border-radius: 10px;
				margin: 0 auto;
				position: relative;
			}

			#elNexusGiftcard_card {
				height: 230px;
				border-radius: 10px;
				position: relative;
			}

			#icon {
				position: absolute;
				display: block;
				background: #fff;
				border-radius: 50px;
				width: 100px;
				height: 100px;
				top: 15px;
				left: 15px;
				font-size: 60px;
				line-height: 100px;
				text-align: center;
				z-index: 5000;
			}

			#title {
				position: absolute;
				top: 30px;
				right: 15px;
				font-size: 20px;
				font-weight: 400;
				color: rgba(255,255,255,0.6);
			}

			#elNexusGiftcard_card h2 {
				position: absolute;
				right: 15px;
				bottom: 80px;
				color: #fff;
				font-weight: 400;
				font-size: 40px;
				margin: 0;
				padding: 0;
			}

			#value {
				position: absolute;
				right: 15px;
				bottom: 30px;
				color: rgba(255,255,255,0.8);
				font-weight: 300;
				font-size: 52px;
			}

			#message {
				margin: 10px 0;
			}

			#scissors_top,
			#scissors_bottom {
				position: absolute;
				font-size: 22px;
				left: 50%;
			}

			#scissors_top {
				top: -13px;
			}

			#scissors_bottom {
				bottom: -13px;
				transform: rotate(180deg);
			}

			#elNexusGiftcard_personalize {
				font-size: 14px;
				padding: 15px;
				color: #333;
			}

			#elNexusGiftcard_personalize hr {
				border: 0;
				border-top: 1px solid #999999;
				height: 1px;
				margin-top: 15px;
				margin-bottom: 15px;
			}

			#redeem, #code {
				font-size: 12px;
				text-align: center;
			}

				#code strong {
					font-size: 16px;
				}

			#redeem {
				color: #626262;
				margin-top: 5px;
			}
		</style>
		<link rel='stylesheet' href='
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "applications/core/interface/fontawesome/css/regular.min.css?v=6.3.0", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
'>
		<link rel='stylesheet' href='
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "applications/core/interface/fontawesome/css/solid.min.css?v=6.3.0", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
'>
		<link rel='stylesheet' href='
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "applications/core/interface/fontawesome/css/fontawesome.min.css?v=6.3.0", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
'>
		<link rel='stylesheet' href='
IPSCONTENT;

$return .= str_replace( array( 'http://', 'https://' ), '//', htmlspecialchars( \IPS\Http\Url::internal( "applications/core/interface/fontawesome/css/brands.min.css?v=6.3.0", "none", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ) );
$return .= <<<IPSCONTENT
'>
	</head>
	<body>
		<div id='elNexusGiftcard_border'>
			<i class='fa-solid fa-scissors' id='scissors_top'></i>
			<i class='fa-solid fa-scissors' id='scissors_bottom'></i>
			<div id='elNexusGiftcard'>
				<div id='elNexusGiftcard_card' style='background-color: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['color'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					<span id='icon'><i class='fa-solid fa-gift'></i></span>
					<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<strong id='title'>
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
</strong>
					<strong id='value'>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $data['amount'], $data['currency'] );
$return .= <<<IPSCONTENT
</strong>
				</div>
				<div id='elNexusGiftcard_personalize'>
					
IPSCONTENT;

if ( $data['recipient_name'] ):
$return .= <<<IPSCONTENT

						<div>
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['recipient_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>,
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $data['message'] ):
$return .= <<<IPSCONTENT

						<div id='message'>
							
IPSCONTENT;

$return .= nl2br( htmlspecialchars( $data['message'], ENT_DISALLOWED, 'UTF-8', FALSE ) );
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $data['sender'] ):
$return .= <<<IPSCONTENT

						<div><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['sender'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<hr>
					<div id='code'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'redemption_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
						<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['code'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
					</div>
					<div id='redeem'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'to_redeem_visit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
						
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=gifts&do=redeem", null, "store_giftvouchers_redeem", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		</div>
	</body>
</html>
IPSCONTENT;

		return $return;
}

	function package( $package, $customFieldsForm, $reactivateUrl, $upgradeDowngradeUrl, $upgradeDowngradeLang, $associatedFiles ) {
		$return = '';
		$return .= <<<IPSCONTENT


<hr class='ipsHr i-margin-top_3'>

IPSCONTENT;

if ( $reactivateUrl or $upgradeDowngradeUrl or \IPS\nexus\Package\Item::load( $package->id )->canReview() ):
$return .= <<<IPSCONTENT

	<ul class='ipsList ipsList--inline'>
		
IPSCONTENT;

if ( $reactivateUrl ):
$return .= <<<IPSCONTENT

			<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reactivateUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class=''>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reactivate_package', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $upgradeDowngradeUrl ):
$return .= <<<IPSCONTENT

			<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $upgradeDowngradeUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class=''>
IPSCONTENT;

$val = "{$upgradeDowngradeLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\nexus\Package\Item::load( $package->id )->canReview() ):
$return .= <<<IPSCONTENT

			<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class=''>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'write_package_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
	<hr class='ipsHr i-margin-bottom_4'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<br><br>

IPSCONTENT;

if ( \count( $associatedFiles ) ):
$return .= <<<IPSCONTENT

	<h2 class='ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'package_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<ul class='ipsCarousel ipsCarousel--commerce-package-downloads i-basis_200' id='commerce-package-downloads' tabindex="0">
		
IPSCONTENT;

foreach ( $associatedFiles as $idx => $file ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "downloads" )->indexBlock( $file );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'commerce-package-downloads' );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


{$customFieldsForm}

IPSCONTENT;

		return $return;
}}