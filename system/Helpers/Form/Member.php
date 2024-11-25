<?php
/**
 * @brief		Member input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Mar 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Member as SystemMember;
use IPS\Theme;
use function array_slice;
use function count;
use function defined;
use function is_array;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member input class for Form Builder
 */
class Member extends Text
{
	/**
	 * @brief	Default Options
	 * @code
	 	$childDefaultOptions = array(
	 		'multiple'	=> 1,	// Maximum number of members. NULL for any. Default is 1.
	 	);
	 * @endcode
	 */
	public array $childDefaultOptions = array(
		'multiple'	=> 1,
	);


	/**
	 * Constructor
	 *
	 * @param string $name Name
	 * @param mixed $defaultValue Default value
	 * @param bool|null $required Required? (NULL for not required, but appears to be so)
	 * @param array $options Type-specific options
	 * @param callable|null $customValidationCode Custom validation code
	 * @param string|null $prefix HTML to show before input field
	 * @param string|null $suffix HTML to show after input field
	 * @param string|null $id The ID to add to the row
	 */
	public function __construct( string $name, mixed $defaultValue=NULL, ?bool $required=FALSE, array $options=array(), callable $customValidationCode=NULL, string $prefix=NULL, string $suffix=NULL, string $id=NULL )
	{
		$this->defaultOptions['autocomplete'] = array(
			'source' 				=> 'app=core&module=system&controller=ajax&do=findMember',
			'resultItemTemplate' 	=> 'core.autocomplete.memberItem',
			'commaTrigger'			=> false,
			'unique'				=> true,
			'minAjaxLength'			=> 3,
			'disallowedCharacters'  => array(),
			'lang'					=> 'mem_optional',
			'suggestionsOnly'		=> true
		);
		if( count( $options ) and array_key_exists( 'multiple', $options ) and $options['multiple'] > 0 )
		{
			$this->defaultOptions['autocomplete']['maxItems'] = $options['multiple'];
		}
		elseif ( !array_key_exists( 'multiple', $options ) )
		{
			$this->defaultOptions['autocomplete']['maxItems'] = $this->childDefaultOptions['multiple'];
		}

		/* Explicitly merge autocomplete options */
		if ( array_key_exists( 'autocomplete', $options ) )
		{
			$options['autocomplete'] = array_merge( $this->defaultOptions['autocomplete'], $options['autocomplete'] );
			$options['suggestionsOnly'] = true;
		}

		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		$value = $this->value;
		if ( is_array( $this->value ) )
		{
			$value = array();
			foreach ( $this->value as $v )
			{
				$value[] = ( $v instanceof SystemMember ) ? $v->name : $v;
			}
			$value = implode( "\n", $value );
		}
		elseif ( $value instanceof SystemMember )
		{
			$value = $value->name;
		}
		
		/* This value is decoded by the JS widget before use. */
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->text( $this->name, 'text', ( $this->options['autocomplete'] AND !is_null( $value ) ) ? htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) : $value, $this->required, $this->options['maxLength'], $this->options['size'], $this->options['disabled'], $this->options['autocomplete'], $this->options['placeholder'], NULL, $this->options['nullLang'] );
	}
	
	/**
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue(): mixed
	{
		if ( $this->value !== '' and !( $this->value instanceof SystemMember ) )
		{
			$return = array();
			
			foreach ( is_array( $this->value ) ? $this->value : explode( "\n", $this->value ) as $v )
			{
				if ( $v instanceof SystemMember )
				{
					$return[ $v->member_id ] = $v;
				}
				elseif( $v !== '' )
				{
					$v = html_entity_decode( $v, ENT_QUOTES, 'UTF-8' );

					$member = SystemMember::load( $v, 'name' );
					if ( $member->member_id )
					{
						if ( $this->options['multiple'] === 1 )
						{
							return $member;
						}
						$return[ $member->member_id ] = $member;
					}
				}
			}

			if ( !empty( $return ) )
			{
				return ( $this->options['multiple'] === NULL or $this->options['multiple'] == 0 ) ? $return : array_slice( $return, 0, $this->options['multiple'] );
			}
		}
		
		return $this->value;
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		parent::validate();
		
		if ( $this->value !== '' and !( $this->value instanceof SystemMember ) and !is_array( $this->value ) )
		{
			throw new InvalidArgumentException('form_member_bad');
		}
		else if ( is_array( $this->value ) )
		{
			foreach( $this->value AS $value )
			{
				if ( $value !== '' AND !( $value instanceof SystemMember ) )
				{
					throw new InvalidArgumentException( SystemMember::loggedIn()->language()->addToStack( 'form_member_bad_multiple', FALSE, array( 'sprintf' => array( $value ) ) ) );
				}
			}
		}

		return TRUE;
	}
	
	
	/**
	 * String Value
	 *
	 * @param	mixed	$value	The value
	 * @return    string|int|null
	 */
	public static function stringValue( mixed $value ): string|int|null
	{
		if( !is_array( $value ) )
		{
			if( $value instanceof SystemMember )
			{
				$value = array( $value );
			}
			elseif( $value )
			{
				$value = explode( "\n", $value );
			}
			else
			{
				$value = array();
			}
		}

		if ( !count( $value ) )
		{
			return NULL;
		}
		
		return implode( "\n", array_map( function( $v )
		{
			return $v->member_id;
		}, $value ) );
	}
}