<?php
/**
 * @brief		Key/Value input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Theme;
use LengthException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Key/Value input class for Form Builder
 */
class KeyValue extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @see        Date
	 * @code
	 	$defaultOptions = array(
	 		'start'			=> array( ... ),
	 		'end'			=> array( ... ),
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'key'		=> array(
			'minLength'			=> NULL,
			'maxLength'			=> NULL,
			'size'				=> 20,
			'disabled'			=> FALSE,
			'autocomplete'		=> NULL,
			'placeholder'		=> NULL,
			'regex'				=> NULL,
			'nullLang'			=> NULL,
			'accountUsername'	=> FALSE,
			'trim'				=> TRUE,
		),
		'value'		=> array(
			'minLength'			=> NULL,
			'maxLength'			=> NULL,
			'size'				=> NULL,
			'disabled'			=> FALSE,
			'autocomplete'		=> NULL,
			'placeholder'		=> NULL,
			'regex'				=> NULL,
			'nullLang'			=> NULL,
			'accountUsername'	=> FALSE,
			'trim'				=> TRUE,
		),
	);

	/**
	 * @brief	Key Object
	 */
	public mixed $keyField = NULL;
	
	/**
	 * @brief	Value Object
	 */
	public mixed $valueField = NULL;

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
		$options = array_merge( $this->defaultOptions, $options );
		
		$this->keyField = new Text( "{$name}[key]", $defaultValue['key'] ?? NULL, FALSE, $options['key'] ?? array() );
		$this->valueField = new Text( "{$name}[value]", $defaultValue['value'] ?? NULL, FALSE, $options['value'] ?? array() );
		
		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );
	}
	
	/**
	 * Format Value
	 *
	 * @return	array
	 */
	public function formatValue(): mixed
	{
		return array(
			'key'	=> $this->keyField->formatValue(),
			'value'	=> $this->valueField->formatValue()
		);
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->keyValue( $this->keyField->html(), $this->valueField->html() );
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @throws	LengthException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		$this->keyField->validate();
		$this->valueField->validate();
		
		if( $this->customValidationCode !== NULL )
		{
			$validationFunction = $this->customValidationCode;
			$validationFunction( $this->value );
		}

		return TRUE;
	}
}