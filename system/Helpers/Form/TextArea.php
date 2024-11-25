<?php
/**
 * @brief		Text input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use LengthException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Text input class for Form Builder
 */
class TextArea extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'minLength'		=> 1,						// Minimum number of characters. NULL is no minimum. Default is NULL.
	 		'maxLength'		=> 255,						// Maximum number of characters. NULL is no maximum. Default is NULL.
	 		'disabled'		=> FALSE,					// Disables input. Default is FALSE.
	 		'placeholder'	=> 'e.g. ...',				// A placeholder (NB: Will only work on compatible browsers)
	 		'nullLang'		=> 'no_value',				// If provided, an "or X" checkbox will appear with X being the value of this language key. When checked, NULL will be returned as the value.
	 		'tags'			=> array(),					// An array of extra insertable tags in key => value pair with key being what is inserted and value serving as a description
	 		'class'			=> 'ipsField_codeInput',	// Additional CSS class
			'tagLinks'		=> array(),					// An array of links to display next to the headers for tags.
			'tagSource'		=> \IPS\Http\Url( ... ), 	// A URL that will fetch tags using AJAX.
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'minLength'		=> NULL,
		'maxLength'		=> NULL,
		'disabled'		=> FALSE,
		'placeholder'	=> NULL,
		'nullLang'		=> NULL,
		'tags'			=> array(),
		'tagSource'		=> null,
		'tagLinks'		=> array(),
		'rows'			=> NULL,
		'class'			=> '',
		'codeMode' 		=> false,
		'codeModeAllowedLanguages' => null
	);

	/**
	 * Constructor
	 *
	 * @param string 		$name 					Name
	 * @param mixed 		$defaultValue 			Default value
	 * @param bool|null 	$required 				Required? (NULL for not required, but appears to be so)
	 * @param array{
	 *     minLength?: 		null|string,
	 *     maxLength?:		null|string,
	 *     disabled?:		boolean,
	 *     placeholder?:	null|string,
	 *     nullLang?:		null|string,
	 *     tags?:			array,
	 *     tagSource?:		null|string|\IPS\Http\Url,
	 *     tagLinks?:		array,
	 *     rows?:			null|mixed,
	 *     class?:			string,
	 *     codeMode?:		boolean,
	 *     codeModeAllowedLanguages?: null|string[]
	 * } 					$options 				Type-specific options
	 * @param callable|null $customValidationCode 	Custom validation code
	 * @param string|null 	$prefix 				HTML to show before input field
	 * @param string|null 	$suffix 				HTML to show after input field
	 * @param string|null 	$id 					The ID to add to the row
	 */
	public function __construct( string $name, mixed $defaultValue=NULL, ?bool $required=FALSE, array $options=array(), callable $customValidationCode=NULL, string $prefix=NULL, string $suffix=NULL, string $id=NULL )
	{
		/* Call parent constructor */
		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );
	
		/* If we have a minimum length, the field is required */
		if( $this->options['minLength'] >= 1 )
		{
			$this->required = TRUE;
		}
		elseif( !$this->options['minLength'] and $this->required )
		{
			$this->options['minLength'] = 1;
		}

		/* Append needed javascript if appropriate */
		if( !empty( $this->options['tags'] ) )
		{
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'jquery/jquery.rangyinputs.js', 'core', 'interface' ) );
		}
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->textarea( $this->name, $this->value, $this->required, $this->options['maxLength'], $this->options['disabled'], $this->options['class'], $this->options['placeholder'], $this->options['nullLang'], $this->options['tags'], $this->options['rows'], $this->options );
	}

	/**
	 * Get value
	 *
	 * @return mixed
	 */
	public function getValue(): mixed
	{
		$nullName = "{$this->name}_null";
		if ( $this->options['nullLang'] !== NULL and isset( Request::i()->$nullName ) )
		{
			return NULL;
		}
		
		return parent::getValue();
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
		
		/* Tags are stored as an array so we can't do things like mb_strlen() against them */
		if( is_array( $this->value ) )
		{
			return TRUE;
		}

		if( $this->options['minLength'] !== NULL and mb_strlen( $this->value ) < $this->options['minLength'] )
		{
			throw new LengthException( Member::loggedIn()->language()->addToStack( 'form_minlength', FALSE, array( 'pluralize' => array( $this->options['minLength'] ) ) ) );
		}
		if( $this->options['maxLength'] !== NULL and mb_strlen( $this->value ) > $this->options['maxLength'] )
		{
			throw new LengthException( Member::loggedIn()->language()->addToStack( 'form_maxlength', FALSE, array( 'pluralize' => array( $this->options['maxLength'] ) ) ) );
		}
		
		return TRUE;
	}
}