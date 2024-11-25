<?php
/**
 * @brief		Username & Password input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		17 Apr 2014
 */

namespace IPS\nexus\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form\FormAbstract;
use IPS\Text\Encrypt;
use IPS\Theme;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Username & Password input class for Form Builder
 */
class UserPass extends FormAbstract
{	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		$value = is_array( $this->value ) ? $this->value : json_decode( Encrypt::fromTag( $this->value )->decrypt(), TRUE );
		$defaultValue = is_array( $this->defaultValue ) ? $this->defaultValue : json_decode( Encrypt::fromTag( $this->defaultValue )->decrypt(), TRUE );
		if ( isset( $value['pw'] ) and isset( $defaultValue['pw'] ) and $value['pw'] and $value['pw'] === $defaultValue['pw'] and !$this->error )
		{
			$value['pw'] = '********';
		}
		
		return Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->usernamePassword( $this->name, $value );
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		$value = parent::getValue();
		
		if ( isset( $value['pw'] ) and $value['pw'] === '********' )
		{
			$defaultValue = is_array( $this->defaultValue ) ? $this->defaultValue : json_decode( Encrypt::fromTag( $this->defaultValue )->decrypt(), TRUE );
			$value['pw'] = $defaultValue['pw'];
		}
		
		return $value;
	}
	
	/**
	 * String Value
	 *
	 * @param	mixed	$value	The value
	 * @return    string|int|null
	 */
	public static function stringValue( mixed $value ): string|int|null
	{
		return Encrypt::fromPlaintext( json_encode( $value ) )->tag();
	}
}