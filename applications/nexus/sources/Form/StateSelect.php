<?php
/**
 * @brief		Country/State input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		12 Feb 2014
 */

namespace IPS\nexus\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\GeoLocation;
use IPS\Helpers\Form\FormAbstract;
use IPS\Request;
use IPS\Theme;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Country/State input class for Form Builder
 */
class StateSelect extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
			'unlimitedLang'	=> 'all_locations',	// Language string to use for "All locations" option. If NULL, will not be available.
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'unlimitedLang' => NULL
	);
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		return Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->stateSelect( $this->name, $this->value, $this->options['unlimitedLang'] );
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{		
		if ( $this->options['unlimitedLang'] )
		{
			$unlimitedName = "{$this->name}_unlimited";
			if ( isset( Request::i()->$unlimitedName ) )
			{
				return '*';
			}
		}
		
		return parent::getValue();
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function formatValue(): mixed
	{
		if ( is_array( $this->value ) )
		{
			$value = array();
			foreach ( $this->value as $k => $v )
			{
				if ( in_array( (string) $k, GeoLocation::$countries ) )
				{
					$value[ $k ] = $v;
				}
				else
				{
					if ( preg_match( '/^([A-Z]{2})-(.*)$/', $v, $matches ) )
					{
						if ( !isset( $value[ $matches[1] ] ) )
						{
							$value[ $matches[1] ] = array();
						}
						$value[ $matches[1] ][] = $matches[2];
					}
					else
					{
						$value[ $v ] = '*';
					}
				}
			}
			return $value;
		}
		else
		{
			return $this->value;
		}
	}
}