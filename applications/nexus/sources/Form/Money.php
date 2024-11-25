<?php
/**
 * @brief		Money input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		12 Feb 2014
 */

namespace IPS\nexus\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Helpers\Form\FormAbstract;
use IPS\nexus\Money as MoneyClass;
use IPS\Theme;
use function count;
use function defined;
use function is_array;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Money input class for Form Builder
 */
class Money extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'unlimitedLang'			=> 'unlimited',	// Language string to use for unlimited checkbox label
	 		'unlimitedTogglesOn'	=> array(),		// IDs to show when unlimited box is ticked
	 		'unlimitedTogglesOff'	=> array(),		// IDs to show when unlimited box is not ticked
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'unlimitedLang'			=> NULL,
		'unlimitedTogglesOn'	=> array(),
		'unlimitedTogglesOff'	=> array(),
	);
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		return Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->money( $this->name, $this->value, $this->options );
	}
	
	/**
	 * Format Value
	 *
	 * @return	array|string
	 */
	public function formatValue(): array|string
	{
		if ( ( $this->options['unlimitedLang'] and isset( $this->value['__unlimited'] ) ) or $this->value === '*' )
		{
			return '*';
		}
		
		$_value = is_array( $this->value ) ? $this->value : @json_decode( $this->value, TRUE );
		$value = array();
		if ( is_array( $_value ) )
		{
			foreach ( $_value as $currency => $amount )
			{
				if ( $amount !== '' )
				{
					if ( $amount instanceof MoneyClass )
					{
						$value[ $currency ] = $amount;
					}
					elseif ( is_array( $amount ) and isset( $amount['amount'] ) )
					{
						$value[ $currency ] = new MoneyClass( $amount['amount'], $currency );
					}
					elseif ( is_numeric( $amount ) )
					{
						$value[ $currency ] = new MoneyClass( $amount, $currency );
					}
				}
			}
		}
		return $value;
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		if ( $this->required )
		{
			if ( ( $this->options['unlimitedLang'] and $this->value === '*' ) or count( $this->value ) )
			{
				return TRUE;
			}
			
			throw new InvalidArgumentException('form_required');
		}
		return parent::validate();
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