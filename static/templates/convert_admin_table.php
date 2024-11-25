<?php
namespace IPS\Theme;
class class_convert_admin_table extends \IPS\Theme\Template
{	function convertMenuRow( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$softwareClass = $table->extra['softwareClass'];
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $r ):
$return .= <<<IPSCONTENT

	<form action='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=runStep&id={$table->extra['appClass']->app_id}&method={$r['step_method']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' method='post' data-controller='convert.admin.convert.menu'>
		<tr class='' data-keyNavBlock>
			
IPSCONTENT;

foreach ( $r as $k => $v ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $k === 'per_cycle' ):
$return .= <<<IPSCONTENT

					<td>
						<input type='text' name='per_cycle' value='{$v}' size='5' style='width:50%'><br>
					</td>
				
IPSCONTENT;

elseif ( $k === 'empty_local_data' ):
$return .= <<<IPSCONTENT

					<td>
						<input type='checkbox' name='empty_local_data' id='empty_local_data_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['step_method'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='1' /> <label for='empty_local_data_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['step_method'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'empty_local_data', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
					</td>
				
IPSCONTENT;

elseif ( $k === 'step_method' ):
$return .= <<<IPSCONTENT

					<td>
                        <input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

if ( \in_array( $r['step_method'], $table->extra['sessionData']['completed'] ) ):
$return .= <<<IPSCONTENT

							<input class='ipsButton ipsButton--secondary ipsButton--tiny' type='submit' name='submit' value='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'convert_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' />
							
IPSCONTENT;

if ( \IPS\IN_DEV == TRUE ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=emptyData&id={$table->extra['appClass']->app_id}&method={$r['step_method']}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary ipsButton--tiny ipsButton--secondary' data-action='remove_converted_data'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_converted_data', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \in_array( $r['step_method'], $softwareClass::checkConf() ) ):
$return .= <<<IPSCONTENT

								<br><input type='checkbox' name='reconfigure' id='reconfigure_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['step_method'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='1' /> <label for='reconfigure_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['step_method'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reconfigure', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( \count( array_filter( $table->extra['menuRows'][$r['step_method']]['dependencies'], array( $table->extra['appClass'], 'dependencies' ) ) ) ):
$return .= <<<IPSCONTENT

							<a href='#' class='ipsButton ipsButton--secondary ipsButton--disabled ipsButton--tiny' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cannot_convert_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cannot_convert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

elseif ( array_key_exists( $r['step_method'], $table->extra['sessionData']['working'] ) ):
$return .= <<<IPSCONTENT

							<input type='hidden' name='continue' value='1' />
							<input type='submit' class='ipsButton ipsButton--secondary ipsButton--tiny' name='submit' value='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_conversion', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' />
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<input class='ipsButton ipsButton--secondary ipsButton--tiny' type='submit' name='submit' value='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'begin_conversion', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' />
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div id='elReconvertForm_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-padding_3 ipsHide'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'confirm_reconvert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
							<ul>
								
IPSCONTENT;

foreach ( $table->extra['menuRows'] AS $step ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( \in_array( $r['step_method'], $step['dependencies'] ) ):
$return .= <<<IPSCONTENT

										<li>
IPSCONTENT;

$val = "{$step['step_method']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</ul><br>
							<input type='submit' class='ipsButton' name='submit' value='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reconvert_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' />
						</div>
					</td>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<td class='
IPSCONTENT;

if ( $k === 'photo' ):
$return .= <<<IPSCONTENT
ipsTable_icon
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $k === $table->mainColumn ):
$return .= <<<IPSCONTENT
ipsTable_primary
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $k === '_buttons' ):
$return .= <<<IPSCONTENT
ipsTable_controls
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $k !== $table->mainColumn && $k !== '_buttons' && $k !== 'photo' ):
$return .= <<<IPSCONTENT
data-title="
IPSCONTENT;

$val = "{$table->langPrefix}{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

if ( $k === '_buttons' ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( $v );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							{$v}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</td>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</tr>
	</form>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function postConversionInformation( $info ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsMessage ipsMessage--info'>
	{$info}
</div>
IPSCONTENT;

		return $return;
}

	function settingsMessage( $app ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$url = \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=settings&id={$app->app_id}" );
$return .= <<<IPSCONTENT

<div class='ipsMessage ipsMessage--info'>
	
IPSCONTENT;

$sprintf = array($url); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'converter_supports_settings_link', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}}