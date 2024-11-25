<?php
/**
 * @brief		pagebuilderupload Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Feb 2020
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Widget\Builder;
use IPS\Widget\Customizable;
use IPS\Widget\StaticCache;
use function count;
use function defined;
use function str_ends_with;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * pagebuilderupload Widget
 */
class pagebuilderupload extends StaticCache implements Builder, Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'pagebuilderupload';
	
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
 		
 		$images = array();
 		$captions = array();
 		$urls = array();
 		
 		if ( ! empty( $this->configuration['pagebuilderupload_upload'] ) )
 		{
	 		foreach( explode( ',', $this->configuration['pagebuilderupload_upload'] ) as $img )
			{
				$images[] = File::get( 'core_Attachment', $img );
			}
 		}
 		
 		if ( ! empty( $this->configuration['pagebuilderupload_captions'] ) )
 		{
	 		foreach( $this->configuration['pagebuilderupload_captions'] as $caption )
			{
				$captions[] = $caption;
			}
 		}
 		
 		if ( ! empty( $this->configuration['pagebuilderupload_urls'] ) )
 		{
	 		foreach( json_decode( $this->configuration['pagebuilderupload_urls'], TRUE ) as $url )
			{
				$urls[] = $url;
			}
 		}
 		
 		$form->add( new Upload( 'pagebuilderupload_upload', $images, FALSE, array( 'multiple' => true, 'storageExtension' => 'core_Attachment', 'allowStockPhotos' => TRUE, 'image' => true ) ) );
// 		$form->add( new YesNo( 'pagebuilderupload_slideshow', ( $this->configuration['pagebuilderupload_slideshow'] ?? FALSE ) ) );
 		$form->add( new Stack( 'pagebuilderupload_captions', $captions, FALSE, array( 'stackFieldType' => 'Text', 'removeEmptyValues' => false ) ) );
		$form->add( new Stack( 'pagebuilderupload_urls', $urls, FALSE, array( 'stackFieldType' => 'Url', 'removeEmptyValues' => false ) ) );

		// This is now handled by the page builder
		$form->add( new Number( 'pagebuilderupload_height', ( $this->configuration['pagebuilderupload_height'] ?? 300 ), FALSE, array( 'unlimited' => 0 ) ) );
 		return $form;
 	}

	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete() : void
	{
		foreach( explode( ',', $this->configuration['pagebuilderupload_upload'] ) as $img )
		{
			try
			{
				File::get( 'core_Attachment', $img )->delete();
			}
			catch( Exception $e ) { }
		}
	}
 	
 	 /**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( array $values ): array
 	{
	 	$images = array();
	 	$urls = array();
	 	
	 	foreach( $values['pagebuilderupload_upload'] as $img )
	 	{
		 	$images[] = (string) $img;
	 	}
	 	
	 	foreach( $values['pagebuilderupload_urls'] as $url )
	 	{
		 	$urls[] = (string) $url;
	 	}
	 	
	 	$values['pagebuilderupload_upload'] = implode( ',', $images );
	 	$values['pagebuilderupload_urls'] = json_encode( $urls );
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */ 
	public function render() : string
	{
		$images = array();
		$captions = ( isset( $this->configuration['pagebuilderupload_captions'] ) ) ? $this->configuration['pagebuilderupload_captions'] : array();
		$urls = ( isset( $this->configuration['pagebuilderupload_urls'] ) ) ? json_decode( $this->configuration['pagebuilderupload_urls'], TRUE ) : array();
//		$autoPlay = ( isset( $this->configuration['pagebuilderupload_slideshow'] ) ) ? $this->configuration['pagebuilderupload_slideshow'] : FALSE;
		$maxHeight = ( isset( $this->configuration['pagebuilderupload_height'] ) ) ? $this->configuration['pagebuilderupload_height'] : FALSE;
		
		if ( isset( $this->configuration['pagebuilderupload_upload'] ) )
		{
			foreach( explode( ',', $this->configuration['pagebuilderupload_upload'] ) as $img )
			{
				$images[] = (string) File::get( 'core_Attachment', $img )->url;
			}

			return $this->output( ( count( $images ) === 1 ? $images[0] : $images ), $captions, $urls, $maxHeight );
		}
		
		return '';
	}


	/**
	 * @return string[]
	 */
	public function getSupportedLayouts() : array
	{
		$layouts = parent::getSupportedLayouts();

		/* Remove support for non-carousels; if we have tabs, there is way too much going on here */
		$return = [];
		foreach( $layouts as $k => $v )
		{
			if( str_ends_with( $v, '-carousel' ) )
			{
				$return[] = $v;
			}
		}
		return $return;
	}
}