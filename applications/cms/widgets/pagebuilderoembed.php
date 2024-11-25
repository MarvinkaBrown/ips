<?php
/**
 * @brief		oembed Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Sep 2019
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Text\Parser;
use IPS\Widget\Builder;
use IPS\Widget\StaticCache;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * oembed Widget
 */
class pagebuilderoembed extends StaticCache implements Builder
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'pagebuilderoembed';
	
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

 		$form->add( new Form\Url( 'video_url', ( isset( $this->configuration['video_url'] )  )? $this->configuration['video_url'] : NULL, TRUE, array(), function( $url ) {
	 		if ( Parser::embeddableMedia( Url::external( $url ) ) === NULL )
	 		{
		 		throw new DomainException('video_cannot_embed');
	 		}
 		} ) );
 		return $form;
 	} 
 	
 	 /**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( array $values ): array
 	{
	 	$values['video_url'] = (string) $values['video_url'];
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		try
		{
			if ( isset( $this->configuration['video_url'] ) AND $embed = Parser::embeddableMedia( Url::external( $this->configuration['video_url'] ) ) )
			{
				return $this->output( $embed );
			}
		}
		catch( UnexpectedValueException $e ){}
		
		return '';
	}
}