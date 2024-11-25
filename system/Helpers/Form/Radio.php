<?php
/**
 * @brief		Radio Switch class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Member;
use IPS\Request;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Radio Switch class for Form Builder
 */
class Radio extends Select
{
	/**
	 * Constructor
	 *
	 * @param string $name Name
	 * @param mixed $defaultValue Default value
	 * @param bool|NULL $required Required? (NULL for not required, but appears to be so)
	 * @param array $options Type-specific options
	 * @param callable|null $customValidationCode Custom validation code
	 * @param string|null $prefix HTML to show before input field
	 * @param string|null $suffix HTML to show after input field
	 * @param string|null $id The ID to add to the row
	 */
	public function __construct( string $name, mixed $defaultValue=NULL, ?bool $required=FALSE, array $options=array(), callable $customValidationCode=NULL, string $prefix=NULL, string $suffix=NULL, string $id=NULL )
	{
		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );

		if ( !isset( $this->options['descriptions'] ) )
		{
			$this->options['descriptions'] = array();
		}
		if ( !isset( $this->options['warnings'] ) )
		{
			$this->options['warnings'] = array();
		}

		/* If you haven't selected any radio options, then there is no input and the required flag is never checked (validate() is never called) */
		$_key = "radio_" . $this->name . "__empty";

		if( isset( Request::i()->$_key ) )
		{
			try
			{
				$this->value = $this->getValue();
				$this->unformatted = $this->value;
				$this->value = $this->formatValue();
				$this->validate();
			}
			catch ( LogicException $e )
			{
				$this->error = $e->getMessage();
			}
		}
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		$descriptions	= $this->options['descriptions'];
		$warnings		= $this->options['warnings'];
		if ( $this->options['parse'] === 'lang' )
		{
			foreach ( $this->options['options'] as $k => $v )
			{
				$descriptions[ $k ] = Member::loggedIn()->language()->addToStack( "{$v}_desc", FALSE, array( 'returnBlank' => TRUE, 'returnInto' => Theme::i()->getTemplate( 'forms', 'core', 'global' )->rowDesc( NULL, NULL ), $this, NULL ) );

				$warnings[ $k ] = Member::loggedIn()->language()->addToStack( "{$v}_warning", FALSE, array( 'returnBlank' => TRUE, 'returnInto' => Theme::i()->getTemplate( 'forms', 'core', 'global' )->rowWarning( NULL, NULL, FALSE, NULL, NULL, NULL, $this->name . '_' . $k ), $this, NULL ) );
			}
		}
		
		/* Translate label back to key? */
		if ( $this->options['returnLabels'] )
		{
			$value = array_search( $this->value, $this->options['options'] );
			if ( $value === FALSE )
			{
				$value = $this->defaultValue;
			}
		}
		else
		{
			$value = $this->value;
		}
		
		if ( $this->options['parse'] === 'image' )
		{
			return Theme::i()->getTemplate( 'forms', 'core', 'global' )->radioImages( $this->name, $value, $this->required, $this->options['options'], $this->options['disabled'], $this->options['toggles'], $descriptions, $warnings, $this->options['userSuppliedInput'], $this->options['unlimited'], $this->options['unlimitedLang'], $this->htmlId, $this->options['gridspan'] );
		}
		else
		{
			return Theme::i()->getTemplate( 'forms', 'core', 'global' )->radio( $this->name, $value, $this->required, $this->parseOptions(), $this->options['disabled'], $this->options['toggles'], $descriptions, $warnings, $this->options['userSuppliedInput'], $this->options['unlimited'], $this->options['unlimitedLang'], $this->htmlId );
		}
	}

	/**
	 * Validate
	 *
	 * @throws	OutOfRangeException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		if( $this->value === null and $this->required )
		{
			throw new InvalidArgumentException('form_required');
		}
		/* Field is not required and value was not supplied */
		else if( $this->value === null )
		{
			return true;
		}

		return parent::validate();
	}

	/**
	 * Get value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		$value = parent::getValue();
		
		/* Disabled radio fields do not submit a value to the server */
		if( $this->options['disabled'] === TRUE or ( is_array( $this->options['disabled'] ) and in_array( $value, $this->options['disabled'] ) ) )
		{
			return $this->defaultValue;
		}
		
		return $value;
	}
}