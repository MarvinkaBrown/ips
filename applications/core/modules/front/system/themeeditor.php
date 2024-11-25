<?php
/**
 * @brief		Theme Editor
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 July 2023
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Helpers\Form\Color;
use IPS\Http\Url;
use IPS\Image;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\Settings;
use RuntimeException;
use function defined;
use function file_get_contents;
use function getimagesize;
use function in_array;
use function json_encode;
use function time;
use const IMAGETYPE_GIF;
use const IMAGETYPE_JPEG;
use const IMAGETYPE_PNG;
use const IMAGETYPE_WEBP;
use const IMAGETYPE_AVIF;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Theme editor
 */
class themeeditor extends Controller
{
	/**
	 * Shows the theme editor
	 *
	 * @return void
	 * @throws Exception
	 */
	public function manage(): void
	{
		if ( ! Member::loggedIn()->isAdmin() ) /* @todo add perm checks in Admin CP */
		{
			Output::i()->error( 'no_permission', '2S164/1', 403, '' );
		}
		
		/* CSS */
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'framework/framework.css', 'core', 'front' ) );
  		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'codemirror/codemirror.css', 'core', 'interface' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/theme_editor.css', 'core', 'front' ) );
		
		/* JS */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'library.js' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'js/jslang.php?langId=' . Member::loggedIn()->language()->id, 'core', 'interface' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'framework.js' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'app.js' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/diff_match_patch.js', 'core', 'interface' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/codemirror.js', 'core', 'interface' ) );
		Color::loadJS();
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'themeeditor/themeEditor.js', 'core', 'interface' ) );

		/* Flag our theme as being edited */
		Theme::i()->editingStart();

		Output::i()->output = Theme::i()->getTemplate( 'themeeditor', 'core', 'front' )->themeEditorTemplate();
		Output::i()->sendOutput( Output::i()->output );
	}

	/**
	 * Save the theme editor
	 *
	 * @return void
	 * @throws Exception
	 */
	public function save(): void
	{
		/* Has someone ended this session for us? */
		if ( ! Member::loggedIn()->isEditingTheme() )
		{
			Output::i()->error( 'theme_editor_session_expired', '2S164/A', 403, '' );
		}

		/* Useful for debugging */
		$changed = Theme::i()->getCssVariables( Theme::CUSTOM_ONLY );
		$data = [];
		$unchanged = [];

		$rootCssVariables = Theme::master()->getCssVariables( Theme::FORCE_DEFAULT );
		$cssVariables = [];

		foreach( $rootCssVariables as $key => $value )
		{
			$cssVariables[ $key ] = $value;
			if ( isset( Request::i()->$key ) )
			{
				$newValue = Request::i()->$key;

				/* We have a value from the theme editor, so override the root value */
				$cssVariables[ $key ] = $newValue;

				if ( $newValue !== $value )
				{
					$changed[ $key ] = $newValue;
				}
				else
				{
					/* If we previously changed the value and we changed it back, clear it */
					if( array_key_exists( $key, $changed ) )
					{
						unset( $changed[ $key ] );
					}

					$unchanged[ $key ] = $value;
				}
			}
		}

		/* Save the css */
		Theme::i()->setCssVariables( $changed );

		/* Work out the positioning based on a 3x3 grid */
		$positioning = [];
		foreach( [ 'logo', 'navigation', 'user', 'breadcrumb', 'search' ] as $type )
		{
			$key = 'set__i-position-' . $type;
			if ( isset( Request::i()->$key ) )
			{
				$positioning[ $type ] = Request::i()->$key;
			}
		}

		$data['set_theme_editor_data']['header'] = $positioning;

		/* Delete or save the logo(s) */
		foreach( [ 'logo-light', 'logo-dark', 'mobile-logo-light', 'mobile-logo-dark' ] as $type )
		{
			$logoKey = '';
			switch ( $type )
			{
				case 'logo-light':
					$logoKey = 'front';
					break;
				case 'logo-dark':
					$logoKey = 'front-dark';
					break;
				case 'mobile-logo-light':
					$logoKey = 'mobile';
					break;
				case 'mobile-logo-dark':
					$logoKey = 'mobile-dark';
					break;
			}

			if ( isset( Request::i()->{ 'delete__set__'. $type } ) and isset( Theme::i()->logo[ $logoKey ]['url'] ) )
			{
				File::get( 'core_Theme', Theme::i()->logo[ $logoKey ]['url'] )->delete();
				$data['logo'][$logoKey] = [];
			}
			else
			{
				if ( isset( $_FILES['set__' . $type] ) and $_FILES['set__' . $type]['tmp_name'] )
				{
					$file = $_FILES['set__' . $type];
					$fileExt = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
					$imageAttributes = getimagesize( $file['tmp_name'] );
					$logoHeightSetting = 'set__i-logo--he';
					if ( $fileExt === 'svg' or in_array( $imageAttributes[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_AVIF] ) )
					{
						/* So we can use the File::create() obfuscation, but it will not re-add the file extension back on for SVG, so... */
						$baseName = pathinfo( $file['name'], PATHINFO_FILENAME );
						$extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
						$randomKey = substr(bin2hex( random_bytes(3) ), 0, 6); // Produces a 6-character long key
						$uniqueFilename = "{$baseName}_{$randomKey}.{$extension}";

						try
						{
							if ( $fileExt !== 'svg' )
							{
								$image = Image::create( file_get_contents( $file['tmp_name'] ), false );
								$width = $image->width;
								$height = $image->height;
							}
							else
							{
								$image = file_get_contents( $file['tmp_name'] );
								$width = null;
								$height = null;

								try
								{
									/* I tried many fancy methods including XML parsing but they all failed, but regex will always work becaue it is the best thing in the world */
									preg_match( '#<image(?:.+?)width="(\d+)"\s+height="(\d+)"#i', $image, $matches );
									if ( isset( $matches[1] ) and isset( $matches[2] ) )
									{
										$width = $matches[1];
										$height = $matches[2];
									}
								}
								catch ( Exception $e ) { }
							}

							$file = File::create( 'core_Theme', $uniqueFilename, $image, null, true, null, false );

							$data['logo'][$logoKey] = [
								'url'            => (string)$file,
								'setting_height' => isset( Request::i()->$logoHeightSetting ) ? Request::i()->$logoHeightSetting : 100,
								'img_width'      => $width,
								'img_height'     => $height,
							];
						}
						catch ( RuntimeException $e )
						{
							$data['logo'][$logoKey] = [];
						}
					}
				}
			}
		}

		Theme::i()->saveSet( $data );

		/* And now layout options */
		$currentOptions = json_decode( Theme::i()->view_options, true );

		if ( ! is_array( $currentOptions ) )
		{
			$currentOptions = [];
		}

		foreach( Request::i() as $key => $value )
		{
			if( str_starts_with( $key, 'layout_' ) )
			{
				$option = substr( $key, strlen( 'layout_' ) );
				$currentOptions[ $option ] = $value;
			}
		}

		Theme::i()->view_options = json_encode( $currentOptions );

		/* Custom CSS */
		if ( isset( Request::i()->set__customCSS ) )
		{
			Theme::i()->custom_css = Request::i()->set__customCSS;
		}

		Theme::i()->save();

		/* Save the history - potential 'roll back' feature? */
		Db::i()->insert( 'core_theme_editor_history', [
			'member_id' => Member::loggedIn()->member_id,
			'time' => time(),
			'set_id' => Theme::i()->_id,
			'json' => json_encode( [
				'changed' => array_keys( $changed ),
				'unchanged' => $unchanged,
				'newCssVariables' => $cssVariables,
				'currentCssVariables' => Theme::i()->getCssVariables(),
				'logos' => $data,
				'request' => Request::i()->request
			] )
		] );

		Output::i()->redirect( Url::internal( "" ), ( \IPS\IN_DEV and ! Settings::i()->theme_designer_mode ) ? 'theme_editor_saved_indev' : 'theme_editor_saved' );
	}

	/**
	 * Close the theme editor
	 *
	 * @return void
	 * @throws Exception
	 */
	public function close(): void
	{
		/* Flag our theme as no longer being edited */
		Theme::i()->editingFinish();

		$redirect = Request::i()->cookie['themeEditorLocation'] ?? Url::internal( '' );

		Output::i()->redirect( $redirect );
	}
}