<?php
/**
 * @brief		Return Custom Image SVGs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community 5
 * @since		Feb 2024
 */

use IPS\core\CustomBadge;
use IPS\Data\Store;
use IPS\Output;
use IPS\Request;

define( 'REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';

$badgeID = Request::i()->icon;
$cacheHeaders = ( !\IPS\IN_DEV ) ? Output::getCacheHeaders( time(), 360 ) : array();

if ( !is_int( (int) $badgeID ) or $badgeID < 0 )
{
	Output::i()->sendOutput( '', 400, 'text/plain', $cacheHeaders );
}

try
{
	if ( !\IPS\IN_DEV and $cacheKey = ( 'ips__custom_badge_' . $badgeID ) and $data = Store::i()->$cacheKey )
	{
		Output::i()->sendOutput( $data, 200, 'image/svg+xml', $cacheHeaders );
	}
}
catch ( OutOfRangeException ) {}

try
{
	$badge = CustomBadge::load( (int) $badgeID );
	$data = (string) $badge;
	if ( !empty( $cacheKey ) )
	{
		Store::i()->$cacheKey = $data;
	}
	Output::i()->sendOutput( $data, 200, 'image/svg+xml', $cacheHeaders );
}
catch ( OutOfRangeException )
{
	Output::i()->sendOutput( '', 404, 'text/plain', $cacheHeaders );
}