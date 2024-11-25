<?php
namespace IPS\Theme;
class class_core_admin_advancedsettings extends \IPS\Theme\Template
{	function ruleCriteriaForm( $name, $field, $type, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[criteria_field]">
	<option value='to'
IPSCONTENT;

if ( $field == 'to'  ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ie_cf_to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value='from'
IPSCONTENT;

if ( $field == 'from'  ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ie_cf_from', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value='sbjt'
IPSCONTENT;

if ( $field == 'sbjt'  ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ie_cf_sbjt', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value='body'
IPSCONTENT;

if ( $field == 'body'  ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ie_cf_body', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[criteria_type]">
	<option value='ctns'
IPSCONTENT;

if ( $field == 'ctns'  ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ie_ct_ctns', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value='eqls'
IPSCONTENT;

if ( $field == 'eqls'  ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ie_ct_eqls', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value='regx'
IPSCONTENT;

if ( $field == 'regx'  ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ie_ct_regx', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
<input type="text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[criteria_value]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" required>
IPSCONTENT;

		return $return;
}

	function taskResult( $success, $message, $id ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $success ):
$return .= <<<IPSCONTENT

	<div class="i-background_2 i-padding_3">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'task_manager_ran_log', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		<pre>
IPSCONTENT;

if ( \is_string( $message ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $message, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( var_export( $message, TRUE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</pre>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class="ipsMessage ipsMessage--error">
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $message, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="i-background_3 i-padding_3 i-text-align_center">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=runTask&id={$id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'task_manager_run_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=tasks", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'task_manager_return', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}}