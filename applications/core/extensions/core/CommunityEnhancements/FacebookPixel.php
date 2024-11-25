<?php
/**
 * @brief		Community Enhancement: Facebook Pixel
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		16 May 2017
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
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
 * Community Enhancement: Facebook Pixel
 */
class FacebookPixel extends CommunityEnhancementsAbstract
{
	/**
	 * @brief	IPS-provided enhancement?
	 */
	public bool $ips	= FALSE;

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
	public string $icon	= "facebook.png";

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( Settings::i()->fb_pixel_enabled and Settings::i()->fb_pixel_id );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$validation = function( $val ) {
			if ( $val and !Request::i()->fb_pixel_id )
			{
				throw new DomainException('fb_pixel_id_req');
			}
		};
		
		$form = new Form;
		$form->add( new Text( 'fb_pixel_id', Settings::i()->fb_pixel_id ? Settings::i()->fb_pixel_id : '', FALSE ) );
		$form->add( new YesNo( 'fb_pixel_enabled', Settings::i()->fb_pixel_enabled, FALSE, array(), $validation ) );
		$form->add( new Number( 'fb_pixel_delay', Settings::i()->fb_pixel_delay, FALSE, array(), $validation, NULL, Member::loggedIn()->language()->addToStack('fb_pixel_delay_seconds') ) );
		
		if ( $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_FacebookPixel' => TRUE ) );
			Output::i()->inlineMessage	= Member::loggedIn()->language()->addToStack('saved');
		}
		
		Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> Url::ips( 'docs/facebookpixel' ),
				'target'	=> '_blank'
			),
		);
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_FacebookPixel', $form );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 */
	public function toggle( bool $enabled ) : void
	{
		if ( $enabled )
		{
			if ( Settings::i()->fb_pixel_id )
			{
				Settings::i()->changeValues( array( 'fb_pixel_enabled' => 1 ) );
			}
			else
			{
				throw new DomainException;
			}
		}
		else
		{
			Settings::i()->changeValues( array( 'fb_pixel_enabled' => 0 ) );
		}
	}
}