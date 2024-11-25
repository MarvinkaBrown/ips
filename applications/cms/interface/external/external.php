<?php
/**
 * @brief		Pages External Block Gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		30 Jun 2015
 *
 */

use IPS\cms\Blocks\Block;
use IPS\Dispatcher\External;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/cms/interface/external/external.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
External::i();

$id = Request::i()->blockid;
$k = Request::i()->widgetid;
$blockHtml = Block::display( $id );

Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_external.js', 'cms', 'front' ) );
Output::i()->globalControllers[] = 'cms.front.external.communication';

$cache = FALSE;

try
{
	if ( is_numeric( $id ) )
	{
		$block = Block::load( $id );
	}
	else if ( is_string( $id ) )
	{
		$block = Block::load( $id, 'block_key' );
	}

	if ( $block->active )
	{
		$cache = ( $block->type == 'custom' ) ? $block->cache : TRUE;
	}
}
catch(OutOfRangeException $ex ){}

if( !$cache OR !Settings::i()->widget_cache_ttl )
{
	Output::setCacheTime( false );
	$headers = array();
}
else
{
	$headers = Output::getCacheHeaders( time(), Settings::i()->widget_cache_ttl );
}

/* Remove protection headers. This is fine in this case because we only output */
if( isset( Output::i()->httpHeaders['X-Frame-Options'] ) )
{
	foreach( [ 'Content-Security-Policy','X-Content-Security-Policy', 'X-Frame-Options' ]  as $toRemove )
	{
		unset( Output::i()->httpHeaders[$toRemove] );
	}
}

Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $blockHtml ), 200, 'text/html', $headers );