<?php
/**
 * @brief		Abstract Class for input types for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Helpers\Form;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use function array_merge;
use function defined;
use function is_array;
use function is_null;
use function is_object;
use function is_string;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Class for input types for Form Builder
 */
abstract class FormAbstract
{
	/**
	 * @brief	Name
	 */
	protected string $_name = '';
	
	/**
	 * @brief	Label
	 */
	public ?string $label = NULL;
	
	/**
	 * @brief	Description
	 */
	public ?string $description = NULL;
	
	/**
	 * @brief	Default Value
	 */
	public mixed $defaultValue = NULL;
	
	/**
	 * @brief	Value
	 */
	public mixed $value = NULL;
	
	/**
	 * @brief	Unformatted Value
	 */
	public mixed $unformatted = NULL;
	
	/**
	 * @brief	Required?
	 */
	public bool $required = FALSE;
	
	/**
	 * @brief	Appears Required?
	 */
	public bool $appearRequired = FALSE;

	/**
	 * @brief 	Additional CSS classnames to use on the row
	 */
	public array $rowClasses = array();
	
	/**
	 * @brief	Type-Specific Options
	 */
	public array $options = array();
	
	/**
	 * @brief	Default Options
	 */
	protected array $defaultOptions = array(
		'disabled'	=> FALSE,
	);
	
	/**
	 * @brief	Custom Validation Code
	 */
	protected $customValidationCode;
	
	/**
	 * @brief	Prefix (HTML that displays before the input box)
	 */
	public ?string $prefix = NULL;
	
	/**
	 * @brief	Suffix (HTML that displays after the input box)
	 */
	public ?string $suffix = NULL;
	
	/**
	 * @brief	HTML ID
	 */
	public ?string $htmlId = NULL;
	
	/**
	 * @brief	Validation Error
	 */
	public ?string $error = NULL;
	
	/**
	 * @brief	Reload form flag (Can be used by JS disabled fall backs to alter form content on submit)
	 */
	public bool $reloadForm = FALSE;
	
	/**
	 * @brief	Warning
	 */
	public ?string $warningBox = NULL;
	
	/**
	 * @brief	Value has been set?
	 */
	public bool $valueSet = FALSE;

	/**
	 * @brief	Used to position an element within a form
	 *
	 * @var string|null
	 */
	public ?string $afterElement = null;

	/**
	 * Specify the tab for this element
	 *
	 * @var string|null
	 */
	public ?string $tab = null;

	/**
	 * Constructor
	 *
	 * @param string $name					Name
	 * @param mixed|null $defaultValue			Default value
	 * @param bool|null $required				Required? (NULL for not required, but appears to be so)
	 * @param array $options				Type-specific options
	 * @param callback|null $customValidationCode	Custom validation code
	 * @param string|null $prefix					HTML to show before input field
	 * @param string|null $suffix					HTML to show after input field
	 * @param string|null $id						The ID to add to the row
	 * @return	void
	 */
	public function __construct( string $name, mixed $defaultValue=NULL, ?bool $required=FALSE, array $options=array(), callable $customValidationCode=NULL, string $prefix=NULL, string $suffix=NULL, string $id=NULL )
	{
		$this->_name				= $name;
		$this->required				= is_null( $required ) ? FALSE : $required;
		$this->appearRequired		= is_null( $required ) ? TRUE : $required;
		$this->options				= array_merge( $this->defaultOptions, $options );
		$this->customValidationCode	= $customValidationCode;
		$this->prefix				= $prefix;
		$this->suffix				= $suffix;
		$this->defaultValue			= $defaultValue;
		$this->htmlId				= $id ? preg_replace( "/[^a-zA-Z0-9\-_]/", "_", $id ) : NULL;

		if ( isset( $this->options['rowClasses'] ) and is_array( $this->options['rowClasses'] ) and count( $this->options['rowClasses'] ) )
		{
			$this->rowClasses = array_merge( $this->rowClasses, $this->options['rowClasses'] );
		}

		$this->setValue(TRUE);
	}

	/**
	 * Set the value of the element
	 *
	 * @param bool $initial	Whether this is the initial call or not. Do not reset default values on subsequent calls.
	 * @param bool $force		Set the value even if one was not submitted (done on the final validation when getting values)?
	 * @return    void
	 */
	public function setValue( bool $initial=FALSE, bool $force=FALSE ): void
	{
		$name			= $this->name;
		$unlimitedKey	= "{$name}_unlimited";
		$nullKey        = "{$name}_null";
		
		if( $force or ( mb_substr( $name, 0, 8 ) !== '_new_[x]' and ( mb_strpos( $name, '[' ) ? Request::i()->valueFromArray( $name ) !== NULL : ( isset( Request::i()->$name ) OR isset( Request::i()->$unlimitedKey ) OR isset( Request::i()->$nullKey ) ) ) ) )
		{
			try
			{
				$this->value = $this->getValue();
				$this->unformatted = $this->value;
				$this->value = $this->formatValue();
				$this->validate();
				$this->valueSet = TRUE;
			}
			catch ( LogicException $e )
			{
				$this->valueSet = TRUE;
				$this->error = $e->getMessage();
			}
		}
		else
		{
			if( $initial )
			{
				$this->value = $this->defaultValue;
				try
				{
					$this->value = $this->formatValue();
				}
				catch ( LogicException $e )
				{
					$this->error = $e->getMessage();
				}
			}
		}
	}

	/**
	 * Magic get method
	 *
	 * @param string $property	Property requested
	 * @return	mixed
	 */
	public function __get( string $property ) : mixed
	{
		if( $property === 'name' )
		{
			return $this->_name;
		}
		
		return NULL;
	}

	/**
	 * Magic set method
	 *
	 * @param string $property	Property requested
	 * @param	mixed	$value		Value to set
	 * @return	void
	 * @note	We are operating on the 'name' property so that if an element's name is reset after the element is initialized we can reinitialize the value
	 */
	public function __set( string $property, mixed $value ) : void
	{
		if( $property === 'name' )
		{
			$this->_name	= $value;
			$this->setValue();
		}
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->rowHtml();
	}
	
	/**
	 * Get HTML
	 *
	 * @param Form|null $form	Form helper object
	 * @return	string
	 */
	public function rowHtml( Form $form=NULL ): string
	{
		try
		{
			if ( $this->label )
			{
				$label = $this->label;
			}
			else
			{
				$label = $this->name;
				if ( isset( $this->options['labelSprintf'] ) )
				{
					$label = Member::loggedIn()->language()->addToStack( $label, FALSE, array( 'sprintf' => $this->options['labelSprintf'] ) );
				}
				else if ( isset( $this->options['labelHtmlSprintf'] ) )
				{
					$label = Member::loggedIn()->language()->addToStack( $label, FALSE, array( 'htmlsprintf' => $this->options['labelHtmlSprintf'] ) );
				}
				else
				{
					$label = Member::loggedIn()->language()->addToStack( $label );
				}
			}
			
			$html = $this->html();
			
			if ( $this->description )
			{
				$desc = $this->description;
			}
			else
			{
				$desc = $this->name . '_desc';
				$desc = Member::loggedIn()->language()->addToStack( $desc, FALSE, array( 'returnBlank' => TRUE, 'returnInto' => Theme::i()->getTemplate( 'forms', 'core', 'global' )->rowDesc( $label, $html, $this->appearRequired, $this->error, $this->prefix, $this->suffix, $this->htmlId ?: ( $form ? "{$form->id}_{$this->name}" : NULL ), $this, $form ) ) );
			}

			if ( $this->warningBox )
			{
				$warning = $this->warningBox;
			}
			else
			{
				$warning = $this->name . '_warning';
				$warning = Member::loggedIn()->language()->addToStack( $warning, FALSE, array( 'returnBlank' => TRUE, 'returnInto' => Theme::i()->getTemplate( 'forms', 'core', 'global' )->rowWarning( $label, $html, $this->appearRequired, $this->error, $this->prefix, $this->suffix, $this->htmlId ?: ( $form ? "{$form->id}_{$this->name}" : NULL ), $this, $form ) ) );
			}
			
			if( array_key_exists( 'endSuffix', $this->options ) )
			{ 
				$this->suffix	= $this->options['endSuffix'];
			}

			/* Some elements support an array for suffix, such as Number which supports preUnlimited and postUnlimited. We need to wipe out
				the suffix here before calling the row() template, however, which only supports a string and throws an Array to string conversion error.
				By this point, the element template has already ran and used the suffix if designed to */
			if( is_array( $this->suffix ) )
			{
				$this->suffix = '';
			}

			return Theme::i()->getTemplate( 'forms', 'core' )->row( $label, $html, $desc, $warning, $this->appearRequired, $this->error, $this->prefix, $this->suffix, $this->htmlId ?: ( $form ? "{$form->id}_{$this->name}" : NULL ), $this, $form, $this->rowClasses );
		}
		catch ( Exception $e )
		{
			if ( \IPS\IN_DEV )
			{
				echo '<pre>';
				var_dump( $e );
				exit;
			}
			
			throw $e;
		}
	}

	/**
	 * Get the value to use in the label 'for' attribute
	 *
	 * @return	mixed
	 */
	public function getLabelForAttribute(): mixed
	{
		return $this->htmlId ?? $this->name;
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		$name	= $this->name;
		$value	= ( mb_strpos( $name, '[' ) OR ( isset( $this->options['multiple'] ) AND $this->options['multiple'] === TRUE ) ) ? Request::i()->valueFromArray( $name ) : Request::i()->$name;

		if( isset( $this->options['disabled'] ) AND $this->options['disabled'] === TRUE AND $value === NULL )
		{
			$value = $this->defaultValue;
		}

		return $value;
	}
	
	/**
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue(): mixed
	{
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
		if( ( $this->value === '' OR ( is_array( $this->value ) AND empty( $this->value ) ) ) and $this->required )
		{
			throw new InvalidArgumentException('form_required');
		}
		
		if ( Settings::i()->getFromConfGlobal('sql_utf8mb4') !== TRUE )
		{
			if ( !static::utf8mb4Check( $this->value ) )
			{
				throw new DomainException( Member::loggedIn()->isAdmin() ? ( CIC ? 'form_multibyte_unicode_admin_cic' : 'form_multibyte_unicode_admin' ) : 'form_multibyte_unicode' );
			}
		}
		
		if( $this->customValidationCode !== NULL )
		{
			$validationFunction = $this->customValidationCode;
			$validationFunction( $this->value );
		}
		
		return TRUE;
	}
		
	/**
	 * Check if a value is okay to be stored in a non-utf8mb4 database
	 *
	 * @param	mixed	$value	The value
	 * @return	bool
	 */
	public static function utf8mb4Check( mixed $value ): bool
	{
		if ( is_array( $value ) )
		{
			foreach ( $value as $_value )
			{
				if ( !static::utf8mb4Check( $_value ) )
				{
					return FALSE;
				}
			}
		}
		elseif ( is_string( $value ) )
		{
			return !preg_match( '/[\x{10000}-\x{10FFFF}]/u', $value );
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
		if ( is_array( $value ) )
		{
			return implode( ',', array_map( function( $v )
			{
				if ( is_object( $v ) )
				{
					return (string) $v;
				}
				return $v;
			}, $value ) );
		}
		
		return (string) $value;
	}

	/**
	 * Set the position of the element within the form
	 *
	 * @param string|null $afterElement
	 * @param string|null $tab
	 * @return $this
	 */
	public function setPosition( ?string $afterElement=null, ?string $tab=null ) : static
	{
		if( $afterElement )
		{
			$this->afterElement = $afterElement;
		}
		if( $tab )
		{
			$this->tab = $tab;
		}

		/* Daisy chaining */
		return $this;
	}
}