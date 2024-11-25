<?php
/**
 * @brief		Upgrader: Custom Upgrade Options
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Jul 2019
 */

use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Radio;
use IPS\Theme;
use const IPS\CACHE_METHOD;

$options = array();
require "../upgrade/lang.php";

/* Show us what is deprecated */
if ( CACHE_METHOD != 'None' and CACHE_METHOD != 'Redis')
{
	$options[] = new Custom( '106000_deprecated', null, FALSE, array('getHtml' => function ( $element ) {
		return Theme::i()->getTemplate( 'global' )->deprecated460();
	}), function ( $val ) {
	}, NULL, NULL, '106000_deprecated' );
}

/* Use new rules or keep old ones? */
$options[]	= new Radio( '106000_rule_option', 'new', TRUE, array( 'options' => array( 'new' => '106000_rule_option_new', 'old' => '106000_rule_option_old' ) ) );



