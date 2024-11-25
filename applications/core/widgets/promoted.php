<?php
/**
 * @brief		Promoted Content Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 March 2017
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Feature;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Output;
use IPS\Theme;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Promoted Content Widget
 */
class promoted extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'promoted';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
	


	/**
	 * Initialize widget
	 *
	 * @return	void
	 */
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/promote.css', 'core' ) );

		parent::init();
	}

	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
 	{
 		$form = parent::configuration( $form );
 		
		$form->add( new Number( 'toshow', $this->configuration['toshow'] ?? 5, TRUE ) );
		
		return $form;
 	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$limit = $this->configuration['toshow'] ?? 5;
		$stream = Feature::internalStream( $limit );

		if ( ! count( $stream ) )
		{
			return '';
		}

		return $this->output( $stream );
	}
}