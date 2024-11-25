<?php
/**
 * @brief		Number input class for Form Builder
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
use LengthException;
use function defined;
use function floatval;
use function intval;
use function is_int;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Text input class for Form Builder
 */
class Number extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'min'				=> 0,			// Minimum number. NULL is no minimum. Default is 0.
	 		'max'				=> 100,			// Maximum number. NULL is no maximum. Default is NULL.
	 		'unlimited'			=> -1,			// If any value other than NULL is provided, an "Unlimited" checkbox will be displayed. If checked, the value specified will be sent.
	 		'unlimitedLang'		=> 'unlimited',	// Language string to use for unlimited checkbox label
	 		'unlimitedToggles'	=> array(...),	// Names of other input fields that should show/hide when the "Unlimited" checkbox is toggled.
	 		'unlimitedToggleOn'	=> TRUE,		// Whether the toggles should show on unlimited checked (TRUE) or unchecked(FALSE). Default is TRUE
	 		'valueToggles'		=> array(...),	// Names of other input fields that should show/hide if the value is/isn't 0.
	 		'decimals'			=> 2,			// Number of decimal places (or TRUE to allow any number)
	 		'step'				=> NULL,		// Increase step for supported browsers. NULL will cause the value to be based off "decimals". Default is NULL.
	 		'range'				=> TRUE,		// Use a range selection for supported browsers? Default is FALSE.
	 		'disabled'			=> FALSE,		// Disables input. Default is FALSE.
	 		'endSuffix'			=> '...',		// A suffix to go after the unlimited checkbox
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'min'				=> 0,
		'max'				=> NULL,
		'unlimited'			=> NULL,
		'unlimitedLang'		=> 'unlimited',
		'unlimitedToggles'	=> array(),
		'unlimitedToggleOn'	=> TRUE,
		'valueToggles'		=> array(),
		'decimals'			=> 0,
		'step'				=> NULL,
		'range'				=> FALSE,
		'disabled'			=> FALSE,
		'endSuffix'			=> NULL,
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
		/* Specify there's a value if the unlimited element contains a value */
		$unlimitedName = "{$name}_unlimited";		
		if( isset( $options['unlimited'] ) and isset( Request::i()->$unlimitedName ) )
		{
			Request::i()->$name = $options['unlimited'];
		}
		
		/* Work out the step */
		if ( !isset( $options['step'] ) and isset( $options['decimals'] ) )
		{
			if ( $options['decimals'] === TRUE )
			{
				$options['step'] = 'any';
			}
			else
			{
				$options['step'] = 1 / ( pow( 10, $options['decimals'] ) );
			}
		}
		
		/* Call parent constructor */
		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );
	}
	
	/**
	 * Generated HTML store
	 */
	protected static array $generatedHtml = array();
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		/* Display */
		if ( ! isset( static::$generatedHtml[ $this->_name ] ) )
		{
			static::$generatedHtml[ $this->_name ] = Theme::i()->getTemplate( 'forms', 'core', 'global' )->number( $this->name, $this->value, $this->required, $this->options['unlimited'], $this->options['range'], $this->options['min'], $this->options['max'], $this->options['step'], $this->options['decimals'], $this->options['unlimitedLang'], $this->options['disabled'], $this->suffix, $this->options['unlimitedToggles'], $this->options['unlimitedToggleOn'], $this->options['valueToggles'], NULL, $this->prefix );
		}
		
		/* Because we mess around with suffixes in the Number field, we store the HTML here otherwise when it is regenerated, the suffix is missing */
		return static::$generatedHtml[ $this->_name ];
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		$name = $this->name;
		
		if ( $pos = mb_strpos( $name, '[' ) )
		{
			$_name = mb_substr( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), 0, $pos );
			$val = Request::i()->$_name;
			preg_match_all( '/\[(.+?)\]/', $name, $matches );
			foreach ( $matches[1] as $_name )
			{
				if ( isset( $val[ $_name ] ) )
				{
					$val = $val[ $_name ];
				}
				else
				{
					$val = FALSE;
					break;
				}
			}
						
			return $val;
		}
		else
		{
			$unlimitedName = "{$name}_unlimited";
			$unlimitedChecked = isset( Request::i()->$unlimitedName );
		}
				
		/* Unlimited? */
		if ( $this->options['unlimited'] !== NULL and isset( Request::i()->$unlimitedName ) )
		{
			return $this->options['unlimited'];
		}
		
		/* Get value */
		return Request::i()->$name;
	}
	
	/**
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue(): mixed
	{		
		/* Get value */
		$value = $this->value;
		
		/* If this is the "unlimited" value, we don't need to format it */
		if ( $this->options['unlimited'] !== NULL and $value === $this->options['unlimited'] )
		{
			return $value;
		}
	
		/* Browsers *might* send in a normal "10.00" format, or because they like to keep us on our
			toes, they might send in a locale-specific "10,00" format. We convert the decimal point
			of the locale to . in an attempt to get us to the former from the latter, but we don't 
			do the thousand seperator in case what we received was the former alredy, which would turn
			for example "10,00" into "1000" */
		$value = str_replace( trim( Member::loggedIn()->language()->locale['decimal_point'] ), '.', $value );

		/* Convert to int/float */
		if ( $this->options['decimals'] and $value != '' )
		{
			$value = floatval( $value );
			if ( is_int( $this->options['decimals'] ) )
			{
				$value = round( $value, $this->options['decimals'] );
			}
		}
		else
		{
			$value = ( $value != '' ) ? intval( $value ) : '';
		}

		/* Return */
		return $value;
	}
	
	/**
	 * Validate
	 *
	 * @throws	LengthException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		parent::validate();
						
		if ( $this->options['unlimited'] === NULL or $this->value !== $this->options['unlimited'] )
		{
			/* If it's not numeric, throw an exception */
			if ( ( !is_numeric( $this->value ) and $this->value !== '' and $this->required === FALSE ) or ( !is_numeric( $this->value ) and $this->required === TRUE ) )
			{
				throw new InvalidArgumentException( 'form_number_bad' );
			}

			$valueToCheck = ( isset( $this->options['decimals'] ) and $this->options['decimals'] ) ? floatval( $this->value ) : (int) $this->value;
			if ( $this->options['min'] !== NULL and $valueToCheck < $this->options['min'] )
			{
				throw new LengthException( Member::loggedIn()->language()->addToStack('form_number_min', FALSE, array( 'sprintf' => array( $this->options['min'] ) ) ) );
			}
			if ( $this->options['max'] !== NULL and $valueToCheck > $this->options['max'] )
			{
				throw new LengthException( Member::loggedIn()->language()->addToStack('form_number_max', FALSE, array( 'sprintf' => array( $this->options['max'] ) ) ) );
			}
		}

		return TRUE;
	}
}