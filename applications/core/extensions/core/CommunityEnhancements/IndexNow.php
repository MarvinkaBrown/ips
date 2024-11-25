<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		11 Jan 2022
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Login;
use IPS\Settings;
use LogicException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class IndexNow extends CommunityEnhancementsAbstract
{
	/**
	 * @brief	Enhancement is enabled?
	 */
	public bool $enabled	= FALSE;

	/**
	 * @brief	IPS-provided enhancement?
	 */
	public bool $ips	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public bool $hasOptions	= FALSE;

	/**
	 * @brief	Icon data
	 */
	public string $icon	= "";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = Settings::i()->indexnow_enabled;
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 * @throws	LogicException
	 */
	public function toggle( bool $enabled ) : void
	{
		/* If we're disabling, just disable */
		if( !$enabled )
		{
			Settings::i()->changeValues( array( 'indexnow_enabled' => 0 ) );
			Settings::i()->changeValues( array( 'indexnow_key' => '' ) );
		}
		else
		{
			$key = Login::generateRandomString();

			Settings::i()->changeValues( array( 'indexnow_enabled' => 1 ) );
			Settings::i()->changeValues( array( 'indexnow_key' => $key ) );
		}
	}

	/**
	 * Edit
	 *
	 * @return    void
	 */
	public function edit(): void
	{

	}
}