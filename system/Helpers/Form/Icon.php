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


use DomainException;
use ErrorException;
use InvalidArgumentException;
use IPS\Theme;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


class Icon extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	$defaultOptions = array(
		"maxIcons" => 1, // Max number of icons the user can select, or null for no limit
		"emoji" => true, // Whether the user can select emojis
	 	"fa" => true, // Whether the user can select font awesome icons,
	 	"disabled" => false, // Whether this input is disabled
		"useSvgIcon" => false, // whether or not to use the SVG icon instead if <i class=""></i>
	);
	 * @endcode
	 */

	protected array $defaultOptions = array(
		"maxIcons" 	=> 1,
		"emoji" 	=> true,
		"fa" 		=> true,
		"disabled" 	=> false,
		"useSvgIcon" => false,
	);

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
	 * Get HTML
	 *
	 * @return    string
	 * @throws ErrorException
	 */
	public function html(): string
	{
		$allowedTypes = [];
		foreach ( [ 'fa', 'emoji' ] as $type )
		{
			if ( isset( $this->options[$type] ) and $this->options[$type] )
			{
				$allowedTypes[] = $type;
			}
		}
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->icon( $this->name, $this->value, $this->required, $this->options[ 'maxIcons' ], $this->options['disabled'], $this->htmlId, $allowedTypes, $this->options['useSvgIcon'] );
	}

	/**
	 * @inheritdoc
	 */
	public function __construct ( string $name, mixed $defaultValue = NULL, ?bool $required = FALSE, array $options = array(), callable $customValidationCode = NULL, string $prefix = NULL, string $suffix = NULL, string $id = NULL )
	{
		if ( array_key_exists( 'emoji', $options ) AND array_key_exists( 'fa', $options ) AND !$options['emoji'] AND !$options['fa'] )
		{
			throw new InvalidArgumentException( 'Icon elements must allow either emojis or font awesome icons' );
		}

		if ( isset( $options['maxIcons'] ) AND is_int( $options['maxIcons'] ) AND $options['maxIcons'] <= 0 )
		{
			throw new InvalidArgumentException( 'Max icons must be at least 1' );
		}

		if ( is_string( $defaultValue ) )
		{
			$defaultValue = json_decode( $defaultValue, true );
			if ( !is_array( $defaultValue ) or !count( $defaultValue ) )
			{
				$defaultValue = null;
			}
		}

		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );
	}


	/**
	 * Get Value
	 *
	 * @return    array|null
	 * @throws ErrorException
	 */
	public function getValue(): array|null
	{
		$rawValue = parent::getValue();
		$data = !is_string( $rawValue ) ? $rawValue : json_decode( $rawValue, true );
		if ( is_array( $data ) )
		{
			$icons = [];
			foreach ( $data as $datum )
			{
				$datum['html'] = Theme::i()->getTemplate( 'global', 'core', 'global' )->icon( $datum );
				$icons[] = $datum;
			}
			return $icons;
		}

		return null;
	}

	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @throws	DomainException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		parent::validate();
		if ( $this->required AND ( empty( $this->value ) OR !is_array( $this->value ) ) )
		{
			throw new DomainException( 'form_required' );
		}

		if ( is_array( $this->value ) )
		{
			$count = 0;
			$badTypes = [];
			if ( array_key_exists( 'emoji', $this->options ) and !$this->options['emoji'] )
			{
				$badTypes['emoji'] = 'emoji';
			}

			if ( array_key_exists( 'fa', $this->options ) and !$this->options['fa'] )
			{
				$badTypes['fa'] = 'fa';
			}

			foreach ( $this->value as $item )
			{
				$count++;
				if ( !isset( $item['type'] ) or isset( $badTypes[$item['type']] ) )
				{
					throw new InvalidArgumentException( 'form_bad_value' );
				}
			}

			if ( isset( $this->options['maxIcons'] ) and is_int( $this->options['maxIcons'] ) and $this->options['maxIcons'] < $count )
			{
				throw new InvalidArgumentException( 'form_bad_value' );
			}
		}

		return true;
	}


	/**
	 * String Value
	 *
	 * @param	mixed	$value	The value
	 * @return    string|int|null
	 */
	public static function stringValue( mixed $value ): string|int|null
	{
		return json_encode( $value );
	}

}