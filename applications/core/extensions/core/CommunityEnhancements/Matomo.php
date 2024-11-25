<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		13 Sep 2021
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
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
class Matomo extends CommunityEnhancementsAbstract
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
	public bool $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public string $icon	= "matomo.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( Settings::i()->matomo_enabled and Settings::i()->matomo_code );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		
		$validation = function( $val ) {
			if ( $val and !Request::i()->matomo_code )
			{
				throw new DomainException('matomo_code_required');
			}
		};
		
		$form = new Form;
		
		$form->add( new YesNo( 'matomo_enabled', Settings::i()->matomo_enabled, FALSE, array(), $validation ) );
		$form->add( new Codemirror( 'matomo_code', Settings::i()->matomo_code, FALSE, array( 'height' => 150, 'codeModeAllowedLanguages' => [ 'xml' ] ), NULL, NULL, NULL, 'matomo_code' ) );
		
		if ( $form->values() )
		{
			try
			{
				$form->saveAsSettings();

				Output::i()->inlineMessage	= Member::loggedIn()->language()->addToStack('saved');
			}
			catch ( LogicException $e )
			{
				$form->error = $e->getMessage();
			}
		}
		
		Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> Url::ips( 'docs/matomo' ),
				'target'	=> '_blank'
			),
		);
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_Matomo', $form );
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
		if ( $enabled )
		{
			if ( Settings::i()->matomo_code )
			{
				Settings::i()->changeValues( array( 'matomo_enabled' => 1 ) );
			}
			else
			{
				throw new DomainException;
			}
		}
		else
		{
			Settings::i()->changeValues( array( 'matomo_enabled' => 0 ) );
		}
	}
}