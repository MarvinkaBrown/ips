<?php
/**
 * @brief		Recent event reviews Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		19 Feb 2014
 */

namespace IPS\calendar\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\calendar\Event\Review;
use IPS\Content\Filter;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Recent event reviews Widget
 */
class recentReviews extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'recentReviews';
	
	/**
	 * @brief	App
	 */
	public string $app = 'calendar';

	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
 	{
		$form = parent::configuration( $form );
 		
 		/* Container */
		$form->add( new Node( 'widget_calendar', $this->configuration['widget_calendar'] ?? 0, FALSE, array(
			'class'           => '\IPS\calendar\Calendar',
			'zeroVal'         => 'all',
			'permissionCheck' => 'view',
			'multiple'        => true
		) ) );
 		
		$form->add( new Number( 'review_count', $this->configuration['review_count'] ?? 5, TRUE ) );

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
 		if ( is_array( $values['widget_calendar'] ) )
 		{
	 		$values['widget_calendar'] = array_keys( $values['widget_calendar'] );
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
		$where = array();
		
		if ( ! empty( $this->configuration['widget_calendar'] ) )
		{
			$where['item'][] = array( Db::i()->in( 'event_calendar_id', $this->configuration['widget_calendar'] ) );
		}
		
		$reviews = Review::getItemsWithPermission( $where, null, ( isset( $this->configuration['review_count'] ) and $this->configuration['review_count'] > 0 ) ? $this->configuration['review_count'] : 5, 'read', Filter::FILTER_PUBLIC_ONLY );

		return $this->output( $reviews );
	}
}