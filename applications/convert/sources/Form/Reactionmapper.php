<?php

/**
 * @brief		Custom Reactions Form Helper
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		02 Oct 2019
 */

namespace IPS\convert\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Content\Reaction;
use IPS\File;
use IPS\Helpers\Form\FormAbstract;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


class Reactionmapper extends FormAbstract
{

	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html() : string
	{
		/* Setup our local reaction descriptions and options */
		foreach( Reaction::getStore() as $ipsReaction )
		{
			$this->options['options'][ $ipsReaction['reaction_id'] ] = (string) File::get( 'core_Reaction', $ipsReaction['reaction_icon'] )->url;
			$this->options['descriptions'][ $ipsReaction['reaction_id'] ] = Member::loggedIn()->language()->addToStack('reaction_title_' . $ipsReaction['reaction_id'] );
		}

		/* Specific options for creating a new reaction */
		$this->options['options'][ 'none' ] = FALSE;
		$this->options['descriptions']['none'] = Member::loggedIn()->language()->addToStack('convert_create_reaction' );

		/* Get our controller */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_forms.js', 'convert' ) );

		/* Sort out the pre-selected value */
		$value = $this->value;
		if ( !is_array( $this->value ) AND $this->value !== NULL AND $this->value !== '' )
		{
			$value = array( $this->value );
		}

		return Theme::i()->getTemplate( 'forms' )->reactionmapper( $this->name, $value, $this->options['reactions'], $this->options['options'], $this->options['descriptions'] );
	}

	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		$name = $this->name;
		return Request::i()->$name;
	}

	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @throws	DomainException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		parent::validate();

		/* Check all of the values are present */
		if( count( $this->value ) !== count( $this->options['reactions'] ) )
		{
			throw new InvalidArgumentException( 'err_convert_reaction' );
		}

		/* Check that the values are valid reactions, or 'none' */
		foreach( $this->value as $value )
		{
			/* None is a special value indicating that we create a new reaction */
			if( $value === 'none' )
			{
				continue;
			}

			/* Zero is an unselected reaction */
			if( (int) $value === 0 )
			{
				throw new InvalidArgumentException( 'err_convert_reaction_not_selected' );
			}

			/* Any value greater than zero is an IPS reaction ID */
			if( (int) $value > 0 )
			{
				try
				{
					Reaction::load( $value );
				}
				catch( OutOfRangeException $e )
				{
					throw new InvalidArgumentException( 'err_convert_reaction_not_exist' );
				}
			}
		}

		return TRUE;
	}
}