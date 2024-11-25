<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		07 Sep 2018
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class Giphy extends CommunityEnhancementsAbstract
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
	public string $icon	= "giphy.png";

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( Settings::i()->giphy_enabled );
	}

	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$form = new Form;

		$form->add( new Text( 'giphy_apikey', Settings::i()->giphy_apikey ? Settings::i()->giphy_apikey : '', FALSE, array(), NULL, NULL, NULL, 'giphy_apikey' ) );
		$form->add( new Select( 'giphy_rating', Settings::i()->giphy_rating ? Settings::i()->giphy_rating : 'x', FALSE, array(
			'options' => array(
				'x' => Member::loggedIn()->language()->addToStack('giphy_rating_x'),
				'r' => Member::loggedIn()->language()->addToStack('giphy_rating_r'),
				'pg-13' => Member::loggedIn()->language()->addToStack('giphy_rating_pg-13'),
				'pg' => Member::loggedIn()->language()->addToStack('giphy_rating_pg'),
				'g' => Member::loggedIn()->language()->addToStack('giphy_rating_g'),
				'y' => Member::loggedIn()->language()->addToStack('giphy_rating_y'),
			)
		), NULL, NULL, Member::loggedIn()->language()->addToStack('giphy_rating_suffix') ) );

		if ( $values = $form->values() )
		{
			try
			{
				/* Enable giphy automatically on the first submit of the form and add it automatically to all toolbars */
				if ( ! Settings::i()->giphy_enabled AND Settings::i()->giphy_apikey != '' )
				{
					$values['giphy_enabled'] = 1;
				}
				
				unset( $values['giphy_custom_apikey'] );
				
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
				'link'		=> Url::ips( 'docs/giphy' ),
				'target'	=> '_blank'
			),
		);

		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_Giphy', $form );
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
			if ( Settings::i()->giphy_apikey )
			{
				Settings::i()->changeValues( array( 'giphy_enabled' => 1 ) );
			}
			else
			{
				throw new DomainException;
			}
		}
		else
		{
			Settings::i()->changeValues( array( 'giphy_enabled' => 0 ) );
		}
	}
}