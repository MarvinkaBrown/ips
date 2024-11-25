<?php
/**
 * @brief		Loyalty discount input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		2 May 2014
 */

namespace IPS\nexus\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Node;
use IPS\nexus\Package;
use IPS\nexus\Package\Group;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Loyalty discount input class for Form Builder
 */
class DiscountLoyalty extends FormAbstract
{
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		$selectedPackage = NULL;
		try
		{
			$selectedPackage = ( is_array( $this->value ) AND isset( $this->value['package'] ) ) ? Package::load( $this->value['package'] ) : NULL;
		}
		catch ( OutOfRangeException ) {}
		
		$nodeSelect = new Node( "{$this->name}[package]", $selectedPackage, FALSE, array( 'class' => 'IPS\nexus\Package\Group', 'permissionCheck' => function( $node )
		{
			return !( $node instanceof Group );
		} ) );
		
		return Theme::i()->getTemplate( 'discountforms' )->loyalty( $this, $nodeSelect->html() );
	}
}