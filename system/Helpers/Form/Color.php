<?php
/**
 * @brief		Color input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Mar 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use const IPS\ROOT_PATH;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function array_merge;
use function defined;
use function file_get_contents;
use function json_decode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Color input class for Form Builder
 */
class Color extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'disabled'		=> FALSE,		// Disables input. Default is FALSE.
	 		'swatches'		=> FALSE		// Shows colour swatches
	 		'rgba'			=> FALSE		// Show RGBA mode
	 		'allowNone'		=> FALSE		// Allow user to select no colour
	 		'allowNoneLanguage' => 'colorpicker_use_none'	// Language string for "Use no colour"
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'disabled'	=> FALSE,
		'swatches'  => FALSE,
		'rgba'		=> FALSE,
		'allowNone' => FALSE,
		'allowNoneLanguage' => 'colorpicker_use_none',
	);
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		$swatches = NULL;

		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->color( $this->name, $this->value, $this->required, $this->options['disabled'], $this->options['swatches'], $this->options['rgba'], $this->options['allowNone'], $this->options['allowNoneLanguage'] );
	}
	
	/**
	 * Format Value
	 *
	 * @return    mixed
	 */
	public function formatValue(): mixed
	{
		$doNotUseName = $this->name . '_none';
		$manualName = $this->name . '_manual';

		if ( isset( Request::i()->$doNotUseName ) and Request::i()->$doNotUseName )
		{
			return null;
		}

		/* If a manual value has been supplied, use that instead */
		if ( isset( Request::i()->$manualName ) )
		{
			$value = Request::i()->$manualName;
		}
		else
		{
			$value = $this->value;
		}

		if ( ! $this->options['rgba'] and ( $value and mb_substr( $value, 0, 1 ) !== '#' ) )
		{
			$value = '#' . $value;
		}
		
		return mb_strtolower( $value );
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

		if( !$this->required AND ! $this->options['rgba'] AND ( !$this->value OR $this->value == '#' ) )
		{
			return TRUE;
		}
		
		$hexPass = preg_match( '/^(?:#)?(([a-f0-9]{3})|([a-f0-9]{6}))$/i', $this->value );
		$namePass = preg_match( '/^([a-z]*)$/i', $this->value );
		$rgbaPass = preg_match( '/^rgba\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)$/mi', $this->value );
		
		if ( $this->options['rgba'] )
		{
			if ( ! $rgbaPass and ! $namePass and ! $hexPass ) 
			{
				throw new InvalidArgumentException('form_color_bad_rgba');
			}
		}
		else
		{
			if ( ! $namePass and ! $hexPass ) 
			{
				throw new InvalidArgumentException('form_color_bad');
			}
		}

		return TRUE;
	}

	public static function loadJS() : void
	{
		static $loaded = false;
		if ( $loaded === FALSE )
		{
			$dir = Application::load( 'core' )->directory;
			$manifest = json_decode( file_get_contents( ROOT_PATH . "/applications/{$dir}/data/iroManifest.json" ), true );
			foreach( $manifest as $key => $module )
			{
				if ( @$module['isEntry'] and isset( $module['file'] ) )
				{
					Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'iro/' . $module['file'], 'core', 'interface' ) );
				}
			}
			$loaded = true;
		}
	}
}