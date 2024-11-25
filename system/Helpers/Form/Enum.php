<?php
/**
 * @brief		Enumeration class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Feb 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use OutOfRangeException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Enumeration class for Form Builder
 */
class Enum extends FormAbstract
{
	/**
	 * @brief	Default Options
	 @code
	 array(
		 'threshold'		=> 25, // Number of options before switching to a Multi-Select
	 )
	 @encode
	 */
	protected array $defaultOptions = array(
		'threshold'		=> 25
	);
	
	/**
	 * @brief	Form Class
	 */
	protected mixed $class = NULL;
	
	/**
	 * @brief	Threshold
	 */
	protected mixed $threshold = NULL;

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
		$options['multiple'] = TRUE;
		
		$this->threshold = $options['threshold'] ?? $this->defaultOptions['threshold'];
		
		if ( count( $options['options'] ) >= $this->threshold )
		{
			$this->class = new Select( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );
		}
		else
		{
			$this->class = new CheckboxSet( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );
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
		return $this->class->html();
	}
	
	/**
	 * Get value
	 *
	 * @return	array
	 */
	public function getValue(): mixed
	{
		return $this->class->getValue();
	}
	
	/**
	 * Validate
	 *
	 * @throws	OutOfRangeException
	 * @return	bool
	 */
	public function validate(): bool
	{
		return $this->class->validate();
	}
}