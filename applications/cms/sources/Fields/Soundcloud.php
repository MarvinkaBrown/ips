<?php
/**
 * @brief		Soundcloud input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		11 Mar 2013
 */

namespace IPS\cms\Fields;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\cms\Fields;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Soundcloud input class for Form Builder
 */
class Soundcloud extends Text
{
	/**
	 * @brief	Default Options
	 */
	public array $childDefaultOptions = array(
		'parameters' => array()
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
	public function __construct( string $name, mixed $defaultValue=NULL, bool $required=FALSE, array $options=array(), callable $customValidationCode=NULL, string $prefix=NULL, string $suffix=NULL, string $id=NULL )
	{
		$this->childDefaultOptions['placeholder'] = Member::loggedIn()->language()->addToStack('field_placeholder_soundcloud');
		
		/* Call parent constructor */
		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );
		
		$this->formType = 'text';
	}

	/**
	 * Get the display value
	 *
	 * @param mixed $value Stored value from form
	 * @param Fields $customField Custom Field Object
	 * @return string|null
	 */
	public static function displayValue( mixed $value, Fields $customField ): ?string
	{
		if( !$value )
		{
			return '';
		}

		$params = $customField->extra;
		
		if ( ! isset( $params['width'] ) )
		{
			$params['width'] = '100%';
		}
		
		if ( ! isset( $params['height'] ) )
		{
			$params['height'] = 166;
		}
		
		return Theme::i()->getTemplate( 'records', 'cms', 'global' )->soundcloud( urlencode( $value ), $params );
	}
	
	/**
	 * Validate
	 *
	 * @return	bool
	 * @throws	DomainException
	 * @throws	InvalidArgumentException
	 	 */
	public function validate(): bool
	{
		parent::validate();
						
		if ( $this->value )
		{
			/* Check the URL is valid */
			if ( !( $this->value instanceof Url ) )
			{
				throw new InvalidArgumentException('form_url_bad');
			}
			
			/* Check it's a valid Soundcloud URL */
			if ( ! mb_stristr( $this->value->data['host'], 'soundcloud.com' ) )
			{
				throw new InvalidArgumentException('form_url_bad');
			}
		}

		return true;
	}
	
	/**
	 * Get Value
	 *
	 * @return	string
	 */
	public function getValue(): mixed
	{
		$val = parent::getValue();
		if ( $val and !mb_strpos( $val, '://' ) )
		{
			$val = "https://{$val}";
		}
		
		return $val;
	}
	
	/**
	 * Format Value
	 *
	 * @return	Url|string
	 */
	public function formatValue(): mixed
	{
		if ( $this->value and !( $this->value instanceof Url ) )
		{
			try
			{
				return new Url( $this->value );
			}
			catch ( InvalidArgumentException $e )
			{
				return $this->value;
			}
		}
		
		return $this->value;
	}
}