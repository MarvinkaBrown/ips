<?php
/**
 * @brief		Custom Blocks Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		17 Oct 2014
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Blocks\Block;
use IPS\cms\Blocks\Container;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Widget\PermissionCache;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom block Widget
 */
class Blocks extends PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'Blocks';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';

	/**
	 * Constructor
	 *
	 * @param String $uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param array|string|null $access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation			Orientation (top, bottom, right, left)
	 * @param string $layout
	 * @return	void
	 */
	public function __construct(string $uniqueKey, array $configuration, array|string $access=null, string $orientation=null, string $layout='table' )
	{
		try
		{
			if (  isset( $configuration['cms_widget_custom_block'] ) )
			{
				$block = Block::load( $configuration['cms_widget_custom_block'], 'block_key' );
				if ( $block->type === 'custom' AND ! $block->cache )
				{
					$this->neverCache = TRUE;
				}
				else if ( $block->type === 'plugin' )
				{
					try
					{
						/* loads and JS and CSS needed */
						$block->orientation = $orientation;
						$block->widget()->init();
					}
					catch( Exception $e ) { }
				}
			}
		}
		catch( Exception $e ) { }
		
		parent::__construct( $uniqueKey, $configuration, $access, $orientation, $layout );
	}
	
	/**
	 * Specify widget configuration
	 *
	 * @param   Form|null   $form       Form Object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
 	{
		$form = parent::configuration( $form );
		
		/* A block may be deleted on the back end */
		$block = NULL;
		try
		{
			if ( isset( $this->configuration['cms_widget_custom_block'] ) )
			{
				$block = Block::load( $this->configuration['cms_widget_custom_block'], 'block_key' );
			}
		}
		catch( OutOfRangeException $e ) { }
		
	    $form->add( new Node( 'cms_widget_custom_block', $block, FALSE, array(
            'class' => '\IPS\cms\Blocks\Container',
            'showAllNodes' => TRUE,
            'permissionCheck' => function( $node )
                {
	                if ( $node instanceof Container )
	                {
		                return FALSE;
	                }

	                return TRUE;
                }
        ) ) );

	    return $form;
 	}

	/**
	 * Pre config
	 *
	 * @param   array   $values     Form values
	 * @return  array
	 */
	public function preConfig( array $values ): array
	{
		$newValues = $values;

		if ( isset( $values['cms_widget_custom_block'] ) )
		{
			$newValues['cms_widget_custom_block'] = $values['cms_widget_custom_block']->key;
		}

		return $newValues;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if ( isset( $this->configuration['cms_widget_custom_block'] ) )
		{
			return (string) Block::display( $this->configuration['cms_widget_custom_block'], $this->orientation );
		}

		return '';
	}
}