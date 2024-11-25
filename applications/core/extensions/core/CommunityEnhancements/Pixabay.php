<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Jan 2020
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
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
class Pixabay extends CommunityEnhancementsAbstract
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
	public string $icon	= "pixabay.png";

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( Settings::i()->pixabay_enabled );
	}

	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$form = new Form;

		$form->add( new Text( 'pixabay_apikey', Settings::i()->pixabay_apikey ? Settings::i()->pixabay_apikey : '', FALSE, array(), function( $val ) {
			if ( $val )
			{
				/* Check API */
				try
				{
					$response = Url::external( "https://pixabay.com/api/" )->setQueryString( array(
						'key'		=> $val,
						'q'			=> "winning"
					) )->request()->get();

					if ( $response->httpResponseCode == 400 )
					{
						throw new DomainException('pixabay_api_key_invalid');
					}
				}
				catch ( Exception $e )
				{
					throw new DomainException('pixabay_api_key_invalid');
				}
			}
		}, NULL, NULL, 'pixabay_apikey' ) );

		$groups = array();
		foreach ( Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		
		$form->add( new YesNo( 'pixabay_safesearch', Settings::i()->pixabay_safesearch, FALSE, array(), NULL, NULL, NULL, 'pixabay_safesearch' ) );
		$form->add( new CheckboxSet( 'pixabay_editor_permissions', Settings::i()->pixabay_editor_permissions == '*' ? '*' : explode( ',', Settings::i()->pixabay_editor_permissions ), NULL, array( 'multiple' => TRUE, 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'everyone', 'impliedUnlimited' => TRUE ), NULL, NULL, NULL, 'pixabay_editor_permissions_access' ) );

		if ( $values = $form->values() )
		{
			try
			{
				/* Enable giphy automatically on the first submit of the form and add it automatically to all toolbars */
				if ( ! Settings::i()->pixabay_apikey )
				{
					$values['pixabay_enabled'] = 1;
				}

				$values['pixabay_editor_permissions'] = $values['pixabay_editor_permissions'] == '*' ? '*' : implode( ',', $values['pixabay_editor_permissions'] );

				$form->saveAsSettings( $values );

				Output::i()->inlineMessage	= Member::loggedIn()->language()->addToStack('saved');
			}
			catch ( LogicException $e )
			{
				$form->error = $e->getMessage();
			}
		}

		Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'help',
				'icon'		=> 'question-circle',
				'link'		=> Url::ips( 'docs/pixabay' ),
				'target'	=> '_blank'
			),
		);

		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_Pixabay', $form );
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
			if ( Settings::i()->pixabay_apikey )
			{
				Settings::i()->changeValues( array( 'pixabay_enabled' => 1 ) );
			}
			else
			{
				throw new DomainException;
			}
		}
		else
		{
			Settings::i()->changeValues( array( 'pixabay_enabled' => 0 ) );
		}
	}
}