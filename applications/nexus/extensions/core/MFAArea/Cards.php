<?php
/**
 * @brief		Multi-Factor Authentication Area
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		30 Sep 2016
 */

namespace IPS\nexus\extensions\core\MFAArea;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\MFAAreaAbstract;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Multi-Factor Authentication Area
 */
class Cards extends MFAAreaAbstract
{
	/**
	 * Is this area available and should show in the ACP configuration?
	 *
	 * @return	bool
	 */
	public function isEnabled(): bool
	{
		return Settings::i()->card_storage_gateways;
	}
}