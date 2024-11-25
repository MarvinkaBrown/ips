<?php
/**
 * @brief		featuredProduct Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	nexus
 * @since		18 Jul 2018
 */

namespace IPS\nexus\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\nexus\Package;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Theme;
use IPS\Widget\PermissionCache;
use OutOfRangeException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * featuredProduct Widget
 */
class featuredProduct extends PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'featuredProduct';
	
	/**
	 * @brief	App
	 */
	public string $app = 'nexus';

	/**
	 * Initialise this widget
	 *
	 * @return void
	 */ 
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'nexus' ) );
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
		
		$value = 0;
		if ( isset( $this->configuration['package'] ) )
		{
			if ( is_array( $this->configuration['package'] ) )
			{
				$value = $this->configuration['package'];
			}
			else
			{
				$value = array( $this->configuration['package'] );
			}
		}
		
		$form->add( new Node( 'package', $value, FALSE, array(
			'class'           => '\IPS\nexus\Package',
			'permissionCheck' => function( $node )
			{
				if ( $node->canView() and $node->store )
				{
					return TRUE;
				}
				return FALSE;
			},
			'multiple'        => true,
			'subnodes'		  => false,
		) ) );

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
		if ( is_array( $values['package'] ) )
		{
			$save = array();
			foreach( $values['package'] AS $pkg )
			{
				$save[] = $pkg->id;
			}
			$values['package'] = $save;
		}
		else
		{
			$values['package'] = array( $values['package']->id );
		}

		return $values;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		//Load the product
		$packages = array();
		if( isset( $this->configuration['package'] ) )
		{
			if ( is_array( $this->configuration['package'] ) )
			{
				$packages = new ActiveRecordIterator( Db::i()->select( '*', 'nexus_packages', array( array( 'p_store=1' ), array( Db::i()->in( 'p_id', $this->configuration['package'] ) ) ) ), 'IPS\nexus\Package' );
			}
			else
			{
				try
				{
					$packages = array( Package::load( $this->configuration['package'] ) );
				}
				catch ( OutOfRangeException ){}
			}
		}

		if ( !count( $packages ) )
		{
			return "";
		}

		return $this->output( $packages );
	}
}