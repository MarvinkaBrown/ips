<?php
/**
 * @brief		Community Enhancements: IPS GeoIP Service
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Apr 2013
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class GeoIP extends CommunityEnhancementsAbstract
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
	public bool $hasOptions	= FALSE;

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
		$this->enabled = Settings::i()->ipsgeoip;
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$form = new Form;
		$form->add( new YesNo( 'ipsgeoip', Settings::i()->ipsgeoip ) );
		if ( $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_GeoIP' => TRUE ) );
			Output::i()->inlineMessage	= Member::loggedIn()->language()->addToStack('saved');
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_GeoIP', $form );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 */
	public function toggle( bool $enabled ) : void
	{
		Settings::i()->changeValues( array( 'ipsgeoip' => 0 ) );
	}
}