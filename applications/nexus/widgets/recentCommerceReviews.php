<?php
/**
 * @brief		recentCommerceReviews Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	nexus
 * @since		17 Jul 2018
 */

namespace IPS\nexus\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Filter;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\nexus\Package\Review;
use IPS\Output;
use IPS\Theme;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * recentCommerceReviews Widget
 */
class recentCommerceReviews extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'recentCommerceReviews';
	
	/**
	 * @brief	App
	 */
	public string $app = 'nexus';

	/**
	 * Init widget
	 *
	 * @return	void
	 */
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'nexus', 'front' ) );
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

		/* Container */
		$form->add( new Node( 'widget_group', $this->configuration['widget_group'] ?? 0, FALSE, array(
			'class'           => '\IPS\nexus\Package\Group',
			'zeroVal'         => 'all',
			'permissionCheck' => 'view',
			'multiple'        => true,
			'subnodes'		  => false,
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
		if ( is_array( $values['widget_group'] ) )
		{
			$values['widget_group'] = array_keys( $values['widget_group'] );
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

		if ( ! empty( $this->configuration['widget_group'] ) )
		{
			$where['item'][] = array( Db::i()->in( 'p_group', $this->configuration['widget_group'] ) );
		}

		$reviews = Review::getItemsWithPermission( $where, null, ( isset( $this->configuration['review_count'] ) and $this->configuration['review_count'] > 0 ) ? $this->configuration['review_count'] : 5, 'read', Filter::FILTER_PUBLIC_ONLY );

		if ( !count( $reviews ) )
		{
			return "";
		}

		return $this->output( $reviews );
	}
}