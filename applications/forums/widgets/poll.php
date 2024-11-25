<?php
/**
 * @brief		poll Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		21 Jul 2015
 */

namespace IPS\forums\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\forums\Topic;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Item;
use IPS\Theme;
use IPS\Widget;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * poll Widget
 */
class poll extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'poll';
	
	/**
	 * @brief	App
	 */
	public string $app = 'forums';

	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );

 		$form->add( new Item( 'widget_poll_tid', ( $this->configuration['widget_poll_tid'] ?? NULL ), TRUE, array(
		    'class'     => '\IPS\forums\Topic',
		    'maxItems'  => 1,
		    'where'     => array( array( '(poll_state<>0 AND poll_state IS NOT NULL)' ) )
	    ), function( $val ) {
			/* Even though we only allow one item, $val here is always an array. */
			foreach( $val AS $id => $topic )
			{
				$poll 	= $topic->getPoll();
				if ( $poll === NULL )
				{
				    /* poll_state is set to something other than 0 or NULL, but the poll doesn't exist, so let the user know. */
				    throw new DomainException( 'poll_widget_no_poll' );
				}
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
	    $item = array_pop( $values['widget_poll_tid'] );
	    $values['widget_poll_tid'] = $item->tid;
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if ( empty( $this->configuration['widget_poll_tid'] ) )
		{
			return '';
		}

		try
		{
			$topic = Topic::loadAndCheckPerms( $this->configuration['widget_poll_tid'] );
			$poll  = $topic->getPoll();
			
			if ( $poll )
			{
				$poll->displayTemplate = array( Theme::i()->getTemplate( 'widgets', 'forums', 'front' ), 'pollWidget' );
				$poll->url = $topic->url();
	
				return $this->output( $topic, $poll );
			}
			else
			{
				return '';
			}
		}
		catch( Exception $ex )
		{
			return '';
		}
	}
}