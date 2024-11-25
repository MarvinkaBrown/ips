<?php
/**
 * @brief		latestProducts Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	nexus
 * @since		16 Jul 2018
 */

namespace IPS\nexus\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
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
 * latestProducts Widget
 */
class latestProducts extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'latestProducts';
	
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
		Output::i()->cssFiles = array_merge(
			Output::i()->cssFiles,
			Theme::i()->css( 'widgets.css', 'nexus' ),
			Theme::i()->css( 'store.css', 'nexus' )
		);
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

		$form->add( new Number( 'number_to_show', $this->configuration['number_to_show'] ?? 5, TRUE ) );

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
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		//Load the products
		$select = Db::i()->select( '*', 'nexus_packages', array(
			array( 'p_store=1' ),
			array( "( p_member_groups='*' OR " . Db::i()->findInSet( 'p_member_groups', Member::loggedIn()->groups ) . ' )' )
		), "p_date_added DESC", array( 0, ( isset( $this->configuration['number_to_show'] ) AND $this->configuration['number_to_show'] > 0 ) ? $this->configuration['number_to_show'] : 5 ) );

		if ( !count( $select ) )
		{
			return "";
		}

		$packages = new ActiveRecordIterator( $select, 'IPS\nexus\Package' );

		return $this->output( $packages );
	}
}