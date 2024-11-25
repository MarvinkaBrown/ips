<?php
/**
 * @brief		Translatable text input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Mar 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Db;
use IPS\Lang;
use IPS\Output;
use IPS\Theme;
use ValueError;
use function defined;
use function is_array;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Translatable text input class for Form Builder
 */
class Translatable extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'key'			=> 'foo',		// Language key
	 		'editor'		=> array(...),	// If this needs to be an editor rather than a textbox, all the options of \IPS\Helpers\Form\Editor are available here
	 		'textArea'		=> FALSE,		// Makes a textarea rather than a textbox
	 		'placeholder'	=> 'Example',	// Placeholder
	 		'sprintf'		=> array(),		// sprintf options - useful when doing dynamic replacements (such as {var} which should be converted to %1$s or %s on save).
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'key'			=> NULL,
		'editor'		=> NULL,
		'textArea'		=> FALSE,
		'placeholder'	=> NULL,
		'sprintf'		=> NULL
	);
	
	/**
	 * @brief	Editors
	 */
	protected ?array $editors = NULL;

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
		
		/* Get current values */
		if ( $this->value === NULL )
		{
			$values = array();
			if ( $this->options['key'] )
			{
				foreach( Db::i()->select( '*', 'core_sys_lang_words', array( 'word_key=?', $this->options['key'] ) )->setKeyField('lang_id') as $k => $v )
				{
					$v = $v['word_custom'] ?? $v['word_default'];
					if ( $v or !isset( $values[ $k ] ) )
					{
						if ( is_array( $this->options['sprintf'] ) )
						{
							try
							{
								$values[ $k ] = vsprintf( $v, $this->options[ 'sprintf' ] );
							}
							catch ( ValueError $e )
							{
								/* Note in pre-PHP8, vsprintf() returns FALSE */
								$values[ $k ] = FALSE;
							}
						}
						else
						{
							$values[ $k ] = $v;
						}
					}
				}
			}
			
			$this->value = $values;
		}
		elseif ( is_string( $this->value ) )
		{
			$values = array();
			foreach ( Lang::getEnabledLanguages() as $lang )
			{
				$values[ $lang->id ] = $this->value;
			}
			$this->value = $values;
		}
		
		/* Add flags.css */
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'flags.css', 'core', 'global' ) );
	}
	
	/**
	 * Get editors
	 *
	 * @return	array|null
	 */
	protected function _getEditors(): ?array
	{
		if ( isset( $this->options['editor'] ) )
		{
			if ( $this->editors === NULL )
			{
				foreach ( Lang::getEnabledLanguages() as $lang )
				{
					$options = $this->options['editor'];
					$options['autoSaveKey'] .= $lang->id;
					$options['attachIdsLang'] = $lang->id;		
					$this->editors[ $lang->id ] = new Editor( "{$this->name}[{$lang->id}]", $this->value[$lang->id] ?? NULL, $this->required, $options );
				}
			}
			return $this->editors;
		}
		else
		{
			return array();
		}
	}
		
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{		
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->translatable( $this->name, Lang::getEnabledLanguages(), $this->value, $this->_getEditors(), $this->options['placeholder'], $this->options['textArea'], $this->required );
	}
	
		
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		if ( isset( $this->options['editor'] ) )
		{
			$return = array();
			foreach ( $this->_getEditors() as $languageId => $editor )
			{
				$return[ $languageId ] = $editor->value;
			}
			return $return;
		}
		else
		{
			return parent::getValue();
		}
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
		
		if ( $this->required )
		{
			if ( ! trim( $this->value[ Lang::defaultLanguage() ] ) )
			{
				throw new InvalidArgumentException('form_required');
			}
		}
		
		return TRUE;
	}
}