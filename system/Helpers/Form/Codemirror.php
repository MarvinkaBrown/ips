<?php
/**
 * @brief		Codemirror class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Jul 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Output;
use IPS\Theme;
use function defined;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Codemirror class for Form Builder
 */
class Codemirror extends TextArea
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'minLength'		=> 1,			// Minimum number of characters. NULL is no minimum. Default is NULL.
	 		'maxLength'		=> 255,			// Maximum number of characters. NULL is no maximum. Default is NULL.
	 		'disabled'		=> FALSE,		// Disables input. Default is FALSE.
	 		'placeholder'	=> 'e.g. ...',	// A placeholder (NB: Will only work on compatible browsers)
	 		'tags'			=> array(),		// An array of extra insertable tags in key => value pair with key being what is inserted and value serving as a description
	 		'mode'			=> 'php'		// Formatting mode. Default is htmlmixed.
	        'height'        => 300      	// Height of code mirror editor
	        'preview'		=> 'http://...'	// A URL where the value can be POSTed (as "value") and will return a preview. Defaults to NULL, which will hide the preview button.
	        'tagLinks'		=> array(),		// An array of links to display next to the headers for tags.
	        'tagSource'		=> \IPS\Http\Url( ... ), // A URL that will fetch tags using AJAX.
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'minLength'		=> NULL,
		'maxLength'		=> NULL,
		'disabled'		=> FALSE,
		'placeholder'	=> NULL,
		'tags'			=> array(),
		'mode'			=> 'htmlmixed',
		'nullLang'		=> NULL,
		'height'        => 300,
		'preview'		=> NULL,
		'tagLinks'		=> array(),
		'tagSource'		=> NULL,
		'simple'		=> TRUE,
		'rows'			=> NULL,
		'class'			=> '',
		'codeMode' 		=> true,
		'codeModeAllowedLanguages' => null
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
		/* Call parent constructor */
		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );

		/* If we have tags, force to full Codemirror */
//		if( $this->options['tags'] or $this->options['tagSource'] or $this->options['tagLinks'] )
//		{
//			$this->options['simple'] = false;
//		}

		/* We don't support this feature */
		$this->options['nullLang']	= NULL;

		/* If we are in simple mode, load Tiptap Editor files */
		if( $this->options['simple'] )
		{
			Editor::loadEditorFiles();
		}
		else
		{
			/* Append our necessary JS/CSS */
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/diff_match_patch.js', 'core', 'interface' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/codemirror.js', 'core', 'interface' ) );
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'codemirror/codemirror.css', 'core', 'interface' ) );
		}
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		if( $this->options['simple'] )
		{
			return parent::html();
		}

		if ( $this->options['height'] )
		{
			$this->options['height'] = is_numeric( $this->options['height'] ) ? $this->options['height'] . 'px' : $this->options['height'];
		}

		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->codemirror( $this->name, $this->value, $this->required, $this->options['maxLength'], $this->options['disabled'], '', $this->options['placeholder'], $this->options['tags'], $this->options['mode'], $this->htmlId ? "{$this->htmlId}-input" : NULL, $this->options['height'], $this->options['preview'], $this->options['tagLinks'], $this->options['tagSource'] );
	}
}