<?php
/**
 * @brief		5.0.0 Beta 5 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		12 Nov 2024
 */

namespace IPS\cms\setup\upg_5000031;

use IPS\Db;
use IPS\Image;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Beta 5 Upgrade Code
 */
class Upgrade
{
	/**
	 * ...
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		$media = iterator_to_array(
			Db::i()->select( '*', 'cms_media', [ 'media_is_image=?', 0 ] )
		);
		$extensions = Image::supportedExtensions();
		$toUpdate = [];
		foreach( $media as $row )
		{
			/* We added support for additional image extensions, so flip the flag here */
			$ext = mb_substr( $row['media_filename'], mb_strrpos( $row['media_filename'], '.' ) + 1 );
			if( in_array( mb_strtolower( $ext ), $extensions ) )
			{
				$toUpdate[] = $row['media_id'];
			}
		}

		if( count( $toUpdate ) )
		{
			Db::i()->update( 'cms_media', [ 'media_is_image' => 1 ], Db::i()->in( 'media_id', $toUpdate ) );
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}