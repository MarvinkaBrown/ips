<?php
/**
 * @brief		Template Plugin - Filesize
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Nov 2013
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use function defined;
use function floatval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Filesize
 */
class Filesize
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static bool $canBeUsedInCss = FALSE;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		array( 'pre' => Code to eval before 'return', 'return' => Code to eval to return desired value )
	 */
	public static function runPlugin( string $data, array $options ): string
	{
		return '\IPS\Output\Plugin\Filesize::humanReadableFilesize( ' . $data . ( ( isset( $options['decimal'] ) and $options['decimal'] ) ? ', TRUE' : '' ) . ' )';
	}
	
	/**
	 * Get human readable filesize (to 2SF)
	 *
	 * @param	int|null		$sizeInBytes	Size in bytes
	 * @param	bool	$decimal		If TRUE, will calculate based on decimal figures rather than binary
	 * @param	bool	$json			If TRUE, will format for json
	 * @param	bool	$get			If TRUE, will get the language string rather than add to stack
	 * @return	string
	 */
	public static function humanReadableFilesize( ?int $sizeInBytes, bool $decimal=FALSE, bool $json=FALSE, bool $get=FALSE ) : string
	{
		$sizeInBytes = floatval( $sizeInBytes );
		
		foreach ( array( 'Y' => 80, 'Z' => 70, 'E' => 60, 'P' => 50, 'T' => 40, 'G' => 30, 'M' => 20, 'k' => 10 ) as $sig => $pow )
		{
			$raised = $decimal ? pow( 1000, $pow / 10 ) : pow( 2, $pow );
			if ( $sizeInBytes >= $raised )
			{
				if ( $get )
				{
					return sprintf( Member::loggedIn()->language()->get( 'filesize_' . $sig ), round( ( $sizeInBytes / $raised ), 2 ) );
				}
				else
				{
					$format = array( 'sprintf' => round( ( $sizeInBytes / $raised ), 2 ) );
	
					if( $json === TRUE )
					{
						$format['json'] = TRUE;
					}
	
					return Member::loggedIn()->language()->addToStack( 'filesize_' . $sig, FALSE, $format );
				}
			}
		}
		
		if ( $get )
		{
			return sprintf( Member::loggedIn()->language()->get( 'filesize_b' ), $sizeInBytes );
		}
		else
		{
			$format = array( 'sprintf' => $sizeInBytes );
	
			if( $json === TRUE )
			{
				$format['json'] = TRUE;
			}
			
			return Member::loggedIn()->language()->addToStack( 'filesize_b', FALSE, $format );
		}
	}
}