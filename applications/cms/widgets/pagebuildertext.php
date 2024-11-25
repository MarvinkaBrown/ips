<?php
/**
 * @brief		pagebuildertext Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Feb 2020
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;
use IPS\Helpers\Form\TextArea;
use IPS\Widget\Builder;
use IPS\Widget\StaticCache;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * pagebuildertext Widget
 */
class pagebuildertext extends StaticCache implements Builder
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'pagebuildertext';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';
	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
 		$form = parent::configuration( $form );

 		$form->add( new TextArea( 'pagebuilder_text', ( $this->configuration['pagebuilder_text'] ?? '' ), FALSE, array( 'rows' => 10 ) ) );
 		return $form;
 	} 

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if ( ! empty( $this->configuration['pagebuilder_text'] ) )
		{
			return $this->output( $this->configuration['pagebuilder_text'] );
		}
		
		return '';
	}
}