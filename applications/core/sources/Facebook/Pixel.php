<?php
/**
 * @brief		Facebook Pixel Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		16 May 2017
 */

namespace IPS\core\Facebook;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Patterns\Singleton;
use IPS\Settings;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Facebook Pixel class
 */
class Pixel extends Singleton
{
	/**
	 * @brief	Singleton Instances
	 */
	protected static ?Singleton $instance = NULL;
	
	/**
	 * @brief	Data Store
	 */
	protected ?array $data = NULL;
	
	/**
	 * @brief	Events
	 */
	protected static ?array $events = NULL;
	
	/**
	 * Output for JS inclusion
	 *
	 * @return string|null
	 */
	public function output() : ?string
	{
		if ( $this->data === NULL )
		{
			return "fbq('track', 'PageView');";
		}
		
		$return = array();
		
		if ( ! isset( $this->data['PageView'] ) )
		{
			$this->data['PageView'] = true;
		}
		
		foreach( $this->data as $name => $params )
		{
			$inlineParams = '';
		
			if ( is_array( $params ) and count( $params ) )
			{
				$inlineParams = json_encode( $params );
			}
			
			if ( $inlineParams )
			{
				$return[] = "fbq('track', '{$name}', " . $inlineParams . " );";
			}
			else
			{
				$return[] = "fbq('track', '{$name}');";
			}
		}
		
		return count( $return ) ? implode( "\n", $return ) : null;
	}
	
	/**
	 * Output for noscript inclusion
	 *
	 * @return string|null
	 */
	public function noscript() : ?string
	{
		if ( $this->data === NULL )
		{
			return '<img alt="" height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . Settings::i()->fb_pixel_id . '&ev=PageView&noscript=1"/>';
		}
		
		$return = array();
		
		if ( ! isset( $this->data['PageView'] ) )
		{
			$this->data['PageView'] = true;
		}
		
		foreach( $this->data as $name => $params )
		{
			$url = 'https://www.facebook.com/tr?id=' . Settings::i()->fb_pixel_id . '&ev=' . $name;
			
			if ( is_array( $params ) and count( $params ) )
			{
				$url .= '&' . http_build_query( array( 'cd' => $params ), '', '&' );
			}
			
			$return[] = '<img alt="" height="1" width="1" style="display:none" src="' . $url . '"/>';
			
		}
			
		return count( $return ) ? implode( "\n", $return ) : null;
	}
}