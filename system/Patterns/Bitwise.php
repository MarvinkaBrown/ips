<?php
/**
 * @brief		Bitwise Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Apr 2013
 */

namespace IPS\Patterns;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ArrayAccess;
use function defined;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Bitwise Class
 */
class Bitwise implements ArrayAccess
{
	/**
	 * @brief	Values
	 */
	public array $values = array();
	
	/**
	 * @brief	Original Values
	 */
	public int|array $originalValues = 0;
	
	/**
	 * @brief	Keys
	 */
	protected array $keys = array();
	
	/**
	 * @brief	Keys Lookup
	 */
	protected array $lookup = array();
	
	/**
	 * @brief	Callback when value is changed
	 */
	protected ?string $callback = NULL;
	
	/**
	 * Constructor
	 *
	 * @param	array			$values		The numbers
	 * @param	array			$keys		Multi-dimensional array. Keys match keys in $value, value is associative array with keys and representive value, or just a list of keys in order
	 * @param callback|null $callback	Callback when value is changed
	 * @return	void
	 */
	public function __construct(array $values, array $keys, callable $callback=NULL )
	{
		$this->values = $values;
		$this->originalValues = $values;
		
		foreach ( $keys as $groupKey => $fields )
		{
			$i = 1;
			foreach ( $fields as $k => $v )
			{
				if ( is_string( $v ) )
				{
					$this->keys[ $groupKey ][ $v ] = $i;
					$this->lookup[ $k ] = $groupKey;
				}
				else
				{
					while( $v != $i )
					{
						$this->keys[ $groupKey ][] = $i;
						$this->lookup[] = $groupKey;
						$i *= 2;
					}
					
					$this->keys[ $groupKey ][ $k ] = $v;
					$this->lookup[ $k ] = $groupKey;
				}
				
				$i *= 2;
			}
		}
				
		$this->callback = $callback;
	}
		
	/**
	 * Offset exists?
	 *
	 * @param	string	$offset	Offset
	 * @return	bool
	 */
	public function offsetExists( $offset ): bool
	{
		return isset( $this->lookup[ $offset ] );
	}
	
	/**
	 * Get offset
	 *
	 * @param	string	$offset	Offset
	 * @return	bool
	 */
	public function offsetGet( $offset ): bool
	{
		$group = $this->lookup[ $offset ];
		return (bool) ( $this->values[ $group ] & $this->keys[ $group ][ $offset ] );
	}
	
	/**
	 * Set offset
	 *
	 * @param	string	$offset	Offset
	 * @param	bool	$value	Value
	 * @return	void
	 */
	public function offsetSet( mixed $offset, mixed $value ): void
	{
		if ( $this->callback !== NULL )
		{
			$callback = $this->callback;
			$callback( $offset, $value );
		}
		
		$group = $this->lookup[ $offset ];
		if ( $value )
		{
			$this->values[ $group ] |= $this->keys[ $group ][ $offset ];
		}
		else
		{
			$this->values[ $group ] &= ~$this->keys[ $group ][ $offset ];
		}
	}
	
	/**
	 * Unset offset
	 *
	 * @param	string	$offset	Offset
	 * @return	void
	 */
	public function offsetUnset( mixed $offset ): void
	{
		$this->offsetSet( $offset, FALSE );
	}
	
	/** 
	 * Get array
	 *
	 * @return	array
	 */
	public function asArray(): array
	{	
		$return = array();
						
		foreach ( $this->keys as $group )
		{
			foreach ( $group as $k => $v )
			{
				$return[ $k ] = $this->offsetGet( $k );
			}
		}
		
		return $return;
	}
}