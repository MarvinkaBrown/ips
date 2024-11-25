<?php
/**
 * @brief		Community Enhancements: Spam Monitoring Service
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Apr 2013
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use LogicException;
use RuntimeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancements: Spam Monitoring Service
 */
class SpamMonitoring extends CommunityEnhancementsAbstract
{
	/**
	 * @brief	IPS-provided enhancement?
	 */
	public bool $ips	= TRUE;

	/**
	 * @brief	Enhancement is enabled?
	 */
	public bool $enabled	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public bool $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public string $icon	= "ips.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$licenseData = IPS::licenseKey();
		if( !$licenseData or !isset( $licenseData['products']['spam'] ) or !$licenseData['products']['spam'] or ( !$licenseData['cloud'] AND strtotime( $licenseData['expires'] ) < time() ) )
		{
			$this->enabled	= false;
		}
		else
		{
			$this->enabled = Settings::i()->spam_service_enabled;
		}
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		try
		{
			$this->testSettings();
		}
		catch ( RuntimeException $e )
		{
			Output::i()->error( 'spam_service_error', '3C116/3', 500, '' );
		}
		catch ( LogicException $e )
		{
			Output::i()->error( $e->getMessage(), '2C116/2', 403, '' );
		}
		
		Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=spam&tab=service' ) );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 * @throws	Exception
	 */
	public function toggle( bool $enabled ) : void
	{
		if ( $enabled )
		{
			$this->testSettings();
		}
		
		Settings::i()->changeValues( array( 'spam_service_enabled' => $enabled ) );
	}
	
	/**
	 * Test Settings
	 *
	 * @return	void
	 * @throws	Exception
	 */
	protected function testSettings() : void
	{
		$licenseData = IPS::licenseKey();
			
		if ( !$licenseData )
		{
			throw new DomainException( Member::loggedIn()->language()->addToStack('spam_service_nokey', FALSE, array( 'sprintf' => array( Url::internal( 'app=core&module=settings&controller=licensekey' ) ) ) ) );
		}
		if ( !$licenseData['cloud'] AND strtotime( $licenseData['expires'] ) < time() )
		{
			throw new DomainException('licensekey_expired');
		}
		if ( !$licenseData['products']['spam'] )
		{
			throw new DomainException('spam_service_noservice');
		}		
	}
}