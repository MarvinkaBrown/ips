<?php
/**
 * @brief		Recent Entries Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		10 Mar 2014
 */

namespace IPS\blog\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Entry;
use IPS\Content\Filter;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
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
 * recentEntries Widget
 */
class recentEntries extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'recentEntries';
	
	/**
	 * @brief	App
	 */
	public string $app = 'blog';

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
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$entries = Entry::getItemsWithPermission( array( array( 'entry_status!=?', 'draft' ) ), NULL, $this->configuration['number_to_show'] ?? 5, 'read', Filter::FILTER_PUBLIC_ONLY );
		if ( count( $entries ) )
		{
			return $this->output( $entries );
		}
		else
		{
			return '';
		}

	}
}