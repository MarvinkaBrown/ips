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
use IPS\Helpers\Form\Text;
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
class GoogleTagManager extends CommunityEnhancementsAbstract
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
	public string $icon	= "google_tag_manager.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( Settings::i()->googletag_enabled and Settings::i()->googletag_head_code and Settings::i()->googletag_noscript_code );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		
		$validation = function( $val ) {
			if ( $val and ( !Request::i()->googletag_head_code or !Request::i()->googletag_noscript_code ) )
			{
				throw new DomainException('googletag_code_required');
			}
		};
		
		$form = new Form;

		$form->add( new YesNo( 'googletag_enabled', Settings::i()->googletag_enabled, FALSE, array(), $validation ) );
		if ( Settings::i()->core_datalayer_enabled )
		{
			$form->add( new YesNo( 'core_datalayer_use_gtm', Settings::i()->core_datalayer_use_gtm, FALSE ) );
			$form->add( new Text( 'core_datalayer_gtmkey', Settings::i()->core_datalayer_gtmkey, FALSE ) );
		}
		$form->add( new Codemirror( 'googletag_head_code', Settings::i()->googletag_head_code, FALSE, array( 'height' => 150, 'codeModeAllowedLanguages' => [ 'xml' ] ), NULL, NULL, NULL, 'googletag_head_code' ) );
		$form->add( new Codemirror( 'googletag_noscript_code', Settings::i()->googletag_noscript_code, FALSE, array( 'height' => 150, 'codeModeAllowedLanguages' => [ 'xml' ] ), NULL, NULL, NULL, 'googletag_noscript_code' ) );
		
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
				'link'		=> Url::ips( 'docs/googletagmanager' ),
				'target'	=> '_blank'
			),
		);
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_GoogleTagManager', $form );
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
			if ( Settings::i()->googletag_head_code and Settings::i()->googletag_noscript_code  )
			{
				Settings::i()->changeValues( array( 'googletag_enabled' => 1 ) );
			}
			else
			{
				throw new DomainException;
			}
		}
		else
		{
			Settings::i()->changeValues( array( 'googletag_enabled' => 0 ) );
		}
	}
}