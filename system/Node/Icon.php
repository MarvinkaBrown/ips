<?php

/**
 * @brief        Icon
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        3/26/2024
 */

namespace IPS\Node;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ErrorException;
use Exception;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Icon as FormIcon;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Upload;
use IPS\Theme;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

trait Icon
{
	/**
	 * @brief	The column that holds the icon data, without the prefix
	 */
	public static string $iconColumn = 'icon';

	/**
	 * @return string
	 */
	public function get_icon_type() : string
	{
		$iconColumn = static::$iconColumn;
		if ( $this->$iconColumn and $iconData = json_decode( $this->$iconColumn, TRUE ) )
		{
			return 'icon';
		}

		return ( $this->$iconColumn ) ? 'upload' : 'none';
	}

	/**
	 * @return string
	 */
	public static function iconFormPrefix() : string
	{
		return static::$iconFormPrefix ?? '';
	}

	/**
	 * @return string
	 */
	public static function iconStorageExtension() : string
	{
		return static::$iconStorageExtension ?? '';
	}

	/**
	 * Add icon fields to the node form
	 *
	 * @param Form $form
	 * @return void
	 */
	public function iconFormFields( Form &$form ) : void
	{
		$prefix = static::iconFormPrefix();
		$form->add( new Radio( $prefix . 'icon_choose', $this->icon_type, null, [
			'options' => [
				'none' => $prefix . 'icon_choose_icon_none',
				'icon' => $prefix . 'icon_choose_icon',
				'upload' => $prefix . 'icon_choose_upload'
			],
			'toggles' => [
				'icon' => [ $prefix . 'icon_picker' ], 'upload' => [ $prefix . 'icon_upload' ] ]
		] ) );

		$form->add( new Upload( $prefix . 'icon_upload', ( $this->icon_type == 'upload' ) ? File::get( static::iconStorageExtension(), $this->icon ) : NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => static::iconStorageExtension() ), NULL, NULL, NULL, $prefix . 'icon_upload' ) );

		$form->add( new FormIcon( $prefix . 'icon_picker', ( $this->icon_type == 'icon' ? json_decode( $this->icon, true ) : null ), FALSE, array(), NULL, NULL, NULL, $prefix . 'icon_picker' ) );
	}

	/**
	 * Handle the above icon fields
	 * 
	 * @param array $values
	 * @return array
	 */
	public function formatIconFieldValues( array $values ) : array
	{
		/* Icon? Me? No. */
		$iconValue = null;
		$prefix = static::iconFormPrefix();
		if ( isset( $values[ $prefix . 'icon_choose' ] ) )
		{
			switch ( $values[ $prefix . 'icon_choose' ] )
			{
				case 'upload':
					if ( $values[ $prefix . 'icon_upload'] )
					{
						$iconValue = (string) $values[ $prefix . 'icon_upload'];
					}
					break;
				case 'icon':
					if ( is_array( $values[ $prefix . 'icon_picker'] ) )
					{
						$iconValue = json_encode( $values[ $prefix . 'icon_picker'] );
					}
					break;
			}
		}

		$values[ $prefix . static::$iconColumn ] = $iconValue;

		unset( $values[ $prefix . 'icon_choose' ], $values[ $prefix . 'icon_upload' ], $values[ $prefix . 'icon_picker' ] );

		return $values;
	}

	/**
	 * If the icon was uploaded, return the file
	 *
	 * @return File|null
	 */
	public function getIconFile() : ?File
	{
		$iconColumn = static::$iconColumn;
		if( $this->icon_type == 'upload' )
		{
			try
			{
				return File::get( static::iconStorageExtension(), $this->$iconColumn );
			}
			catch( Exception $e ){}
		}

		return null;
	}

	/**
	 * Return icon as a string
	 *
	 * @return string
	 * @throws ErrorException
	 */
	public function getIcon(): string
	{
		$iconColumn = static::$iconColumn;
		if ( $iconData = json_decode( $this->$iconColumn, true ) )
		{
			$iconData = $iconData[0];

			if ( isset( $iconData['html'] ) )
			{
				return $iconData['html'];
			}
		}
		else if ( ! empty( $this->$iconColumn ) and $iconFile = $this->getIconFile() )
		{
			/* Must be an upload */
			return Theme::i()->getTemplate( 'global', 'core', 'front' )->uploadedIcon( $iconFile );
		}

		return '';
	}
}