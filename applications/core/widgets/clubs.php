<?php
/**
 * @brief		Clubs Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Apr 2017
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Club\CustomField;
use IPS\Settings;
use IPS\Widget;
use IPS\Widget\Customizable;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Clubs Widget
 */
class clubs extends Widget implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'clubs';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
		

	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );
 		
 		$form->add( new Translatable( 'widget_feed_title', isset( $this->configuration['language_key'] ) ? NULL : Member::loggedIn()->language()->addToStack( 'my_clubs' ), FALSE, array( 'app' => 'core', 'key' => ( $this->configuration['language_key'] ?? NULL ) ) ) );

		$form->add( new Radio( 'club_filter_type', $this->configuration['club_filter_type'] ?? 'mine', TRUE, array( 'options' => array(
			'mine'	=> 'user_clubs',
			'all'	=> 'all_clubs',
		) ) ) );
		
		$fields = CustomField::roots();
		foreach ( $fields as $field )
		{
			if ( $field->filterable )
			{
				switch ( $field->type )
				{
					case 'Checkbox':
					case 'YesNo':
						$input = new CheckboxSet( 'field_' . $field->id, isset( $this->configuration['filters'][ $field->id ] ) ? $this->configuration['filters'][ $field->id ] : array( 1, 0 ), FALSE, array( 'options' => array(
							1			=> 'yes',
							0			=> 'no',
						) ) );
						$input->label = $field->_title;
						$form->add( $input );
						break;
						
					case 'CheckboxSet':
					case 'Radio':
					case 'Select':
						$options = json_decode( $field->extra, TRUE );
						$input = new CheckboxSet( 'field_' . $field->id, isset( $this->configuration['filters'][ $field->id ] ) ? $this->configuration['filters'][ $field->id ] : array_keys( $options ), FALSE, array( 'options' => $options ) );
						$input->label = $field->_title;
						$form->add( $input );
						break;
				}
			}
		}

		$form->add( new Radio( 'sort_by', $this->configuration['sort_by'] ?? 'last_activity', TRUE, array( 'options' => array(
			'last_activity'	=> 'clubs_sort_last_activity',
			'members'		=> 'clubs_sort_members',
			'content'		=> 'clubs_sort_content',
			'created'		=> 'clubs_sort_created',
		) ) ) );
		$form->add( new Number( 'number_to_show', $this->configuration['number_to_show'] ?? 10, TRUE ) );
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
	 	if ( !isset( $this->configuration['language_key'] ) )
 		{
	 		$this->configuration['language_key'] = 'widget_title_' . md5( mt_rand() );
 		}
		$values['language_key'] = $this->configuration['language_key'];
		Lang::saveCustom( 'core', $this->configuration['language_key'], $values['widget_feed_title'] );
 		unset( $values['widget_feed_title'] );
 		
 		$fields = CustomField::roots();
 		foreach ( $fields as $field )
		{
			if ( $field->filterable )
			{					
				switch ( $field->type )
				{
					case 'Checkbox':
					case 'YesNo':
						if ( count( $values[ 'field_' . $field->id ] ) === 1 )
						{
							$values['filters'][ $field->id ] = array_pop( $values[ 'field_' . $field->id ] );
						}
						unset( $values[ 'field_' . $field->id ] );
						break;
						
					case 'CheckboxSet':
					case 'Radio':
					case 'Select':
						$options = json_decode( $field->extra, TRUE );
						if ( count( $values[ 'field_' . $field->id ] ) > 0 and count( $values[ 'field_' . $field->id ] ) < count( $options ) )
						{
							$values['filters'][ $field->id ] = array();
							foreach ( $values[ 'field_' . $field->id ] as $v )
							{
								$values['filters'][ $field->id ][] = $v;
							}
						}
						unset( $values[ 'field_' . $field->id ] );
						break;
				}
			}
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
		if( !Member::loggedIn()->member_id and $this->configuration['club_filter_type'] == 'mine' )
		{
			return '';
		}

		if ( Settings::i()->clubs and Member::loggedIn()->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) )
		{
			/* Strip any not existing custom field filters, e.g. when a field was deleted */
			if ( isset( $this->configuration['filters'] ) and \is_array( $this->configuration['filters'] ) )
			{
				$fields = CustomField::roots();
				foreach ( $this->configuration['filters'] as $key => $value )
				{
					if( !isset( $fields[ $key ] ) )
					{
						unset( $this->configuration['filters'][ $key ] );
					}
				}
			}
			$clubsCount = Club::clubs(
				Member::loggedIn(),
				$this->configuration['number_to_show'] ?? 10,
				$this->configuration['sort_by'] ?? 'last_activity',
				!isset( $this->configuration['club_filter_type'] ) or $this->configuration['club_filter_type'] == 'mine',
				$this->configuration['filters'] ?? array(),
				NULL,
				TRUE
			);
			
			if ( $clubsCount )
			{
				$clubs = Club::clubs(
					Member::loggedIn(),
					$this->configuration['number_to_show'] ?? 10,
					$this->configuration['sort_by'] ?? 'last_activity',
					!isset( $this->configuration['club_filter_type'] ) or $this->configuration['club_filter_type'] == 'mine',
					$this->configuration['filters'] ?? array()
				);

				return $this->output(
					$clubs,
					isset( $this->configuration['language_key'] ) ? Member::loggedIn()->language()->addToStack( $this->configuration['language_key'], FALSE, array( 'escape' => TRUE ) ) : Member::loggedIn()->language()->addToStack( 'my_clubs' )
				);
			}
		}
		
		return '';
	}
}