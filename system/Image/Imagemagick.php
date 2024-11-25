<?php
/**
 * @brief		Image Class - ImageMagick
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 Mar 2014
 */

namespace IPS\Image;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Imagick;
use ImagickDraw;
use ImagickException;
use ImagickPixel;
use InvalidArgumentException;
use IPS\Image;
use IPS\Settings;
use function count;
use function defined;
use function file_put_contents;
use function in_array;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Image Class - ImageMagick
 */
class Imagemagick extends Image
{
	/**
	 * @brief	Temporary filename
	 */
	protected string|null|false $tempFile = NULL;
	
	/**
	 * @brief	Imagick object
	 */
	protected Imagick $imagick;
	
	/**
	 * Constructor
	 *
	 * @param string|null $contents	Contents
	 * @param bool $noImage	We are creating a new instance of the object internally and are not passing an image string
	 * @return	void
	 * @throws	InvalidArgumentException
	 */
	public function __construct( ?string $contents, bool $noImage=FALSE )
	{
		/* If we are just creating an instance of the object without passing image contents as a string, return now */
		if( $noImage === TRUE )
		{
			return;
		}

		$this->tempFile = tempnam( TEMP_DIRECTORY, 'imagick' );
		file_put_contents( $this->tempFile, $contents );
		
		try
		{
			$this->imagick = new Imagick( $this->tempFile );

			/* Set quality (if image format is JPEG) */
			if ( in_array( mb_strtolower( $this->imagick->getImageFormat() ), array( 'jpg', 'jpeg', 'webp', 'avif' ) ) )
			{
				$this->imagick->setImageCompressionQuality( (int) Settings::i()->image_jpg_quality ?: 85 );
			}
		}
		catch ( ImagickException $e )
		{
			throw new InvalidArgumentException( $e->getMessage(), $e->getCode() );
		}

		/* Set width/height */
		$this->setDimensions();
	}
	
	/**
	 * Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if( $this->tempFile !== NULL )
		{
			unlink( $this->tempFile );
		}
	}
	
	/**
	 * Get Contents
	 *
	 * @return	string
	 */
	public function __toString()
	{
		/* If possible, retain the color profiles when stripping EXIF data */
		if( Settings::i()->imagick_strip_exif )
		{
			$imageColorProfiles	= array();

			try
			{
				$imageColorProfiles = $this->imagick->getImageProfiles( 'icc' );
			}
			catch( ImagickException $e ){}

			$this->imagick->stripImage();

			if( count( $imageColorProfiles ) )
			{
				foreach( $imageColorProfiles as $type => $profile )
				{
					try
					{
						$this->imagick->profileImage( $type, $profile );
					}
					catch( ImagickException $e ){}
				}
			}
		}

		return $this->imagick->getImagesBlob();
	}
	
	/**
	 * Resize
	 *
	 * @param int $width			Width (in pixels)
	 * @param int $height			Height (in pixels)
	 * @return	void
	 */
	public function resize( int $width, int $height ) : void
	{
		$format = $this->imagick->getImageFormat();

		if( mb_strtolower( $format ) == 'gif' )
		{
			$this->imagick	= $this->imagick->coalesceImages();

			foreach( $this->imagick as $frame )
			{
				$frame->thumbnailImage( $width, $height );
			}

			/* Needs ImageMagick 6.2.9 or higher for optimizeImageLayers */
			try
			{
				$this->imagick->optimizeImageLayers();
			}
			catch( ImagickException $e )
			{
				$this->imagick->deconstructImages();
			}
		}
		else
		{
			$this->imagick->thumbnailImage( $width, $height );
		}

		/* Set width/height */
		$this->setDimensions();
	}

	/**
	 * Crop to a given width and height (will attempt to downsize first)
	 *
	 * @param int $width			Width (in pixels)
	 * @param int $height			Height (in pixels)
	 * @return	void
	 */
	public function crop( int $width, int $height ) : void
	{
		$this->imagick->cropThumbnailImage( $width, $height );

		/* Set width/height */
		$this->setDimensions();
	}
	
	/**
	 * Crop at specific points
	 *
	 * @param int $point1X		x-point for top-left corner
	 * @param int $point1Y		y-point for top-left corner
	 * @param int $point2X		x-point for bottom-right corner
	 * @param int $point2Y		y-point for bottom-right corner
	 * @return	void
	 */
	public function cropToPoints( int $point1X, int $point1Y, int $point2X, int $point2Y ) : void
	{
		if( mb_strtolower( $this->imagick->getImageFormat() ) === 'gif' )
		{
			$this->imagick	= $this->imagick->coalesceImages();
			
			foreach( $this->imagick as $frame )
			{
				$frame->cropImage( $point2X - $point1X, $point2Y - $point1Y, $point1X, $point1Y );
				$frame->setImagePage($point2X - $point1X, $point2Y - $point1Y, 0, 0);
			}
			
			/* Needs ImageMagick 6.2.9 or higher for optimizeImageLayers */
			try
			{
				$this->imagick->optimizeImageLayers();
			}
			catch( ImagickException $e )
			{
				$this->imagick->deconstructImages();
			}
		}
		else
		{
			$this->imagick->cropImage( $point2X - $point1X, $point2Y - $point1Y, $point1X, $point1Y );
		}

		/* Set width/height */
		$this->setDimensions();
	}
	
	/**
	 * Impose image
	 *
	 * @param Image $image	Image to impose
	 * @param int $x		Location to impose to, x axis
	 * @param int $y		Location to impose to, y axis
	 * @return	void
	 */
	public function impose( Image $image, int $x=0, int $y=0 ) : void
	{
		$this->imagick->compositeImage( $image->imagick, Imagick::COMPOSITE_DEFAULT, $x, $y );
	}

	/**
	 * Rotate image
	 *
	 * @param int $angle	Angle of rotation
	 * @return	void
	 */
	public function rotate( int $angle ) : void
	{
		$this->imagick->rotateImage( new ImagickPixel('#00000000'), $angle );

		/* Set width/height */
		$this->setDimensions();
	}

	/**
	 * @return void
	 */
	public function flip(): void
	{
		$this->imagick->flopImage();
	}

	/**
	 * Set the image width and height
	 *
	 * @return	void
	 */
	protected function setDimensions() : void
	{
		/* If this is a gif, we need to coalesce the image in order to get the proper dimensions */
		if ( mb_strtolower( $this->imagick->getImageFormat() ) === 'gif' )
		{
			$this->imagick = $this->imagick->coalesceImages();
		}
		
		/* Set width/height */
		$this->width = $this->imagick->getImageWidth();
		$this->height = $this->imagick->getImageHeight();
	}
	
	/**
	 * Get Image Orientation
	 *
	 * @return	int|NULL
	 */
	public function getImageOrientation(): int|NULL
	{
		try
		{
			if ( $orientation = parent::getImageOrientation() )
			{
				return $orientation;
			}
			/* This method does not exist in ImageMagick < 6.6.4 */
			return ( method_exists( $this->imagick, 'getImageOrientation' ) ) ? $this->imagick->getImageOrientation() : NULL;
		}
		catch( ImagickException $e )
		{
			return NULL;
		}
	}

	/**
	 * Set image orientation
	 *
	 * @param int $orientation The orientation
	 * @return	void
	 */
	public function setImageOrientation( int $orientation ) : void
	{
		if( method_exists( $this->imagick, 'getImageOrientation' ) )
		{
			$this->imagick->setImageOrientation($orientation);
		}
	}

	/**
	 * Can we write text reliably on an image?
	 *
	 * @return	bool
	 */
	public static function canWriteText(): bool
	{
		return TRUE;
	}

	/**
	 * Create a new blank canvas image
	 *
	 * @param int $width	Width
	 * @param int $height	Height
	 * @param array $rgb	Color to use for bg
	 * @return	Image
	 */
	public static function newImageCanvas( int $width, int $height, array $rgb ): Image
	{
		$obj			= new static(NULL, TRUE);
		$obj->imagick	= new Imagick();
		$obj->width		= $width;
		$obj->height	= $height;
		$obj->type		= 'png';
		$pixel			= new ImagickPixel( "rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, 1)" );

		$obj->imagick->newImage( $width, $height, $pixel );
		$obj->imagick->setImageFormat( "png" );

		return $obj;
	}

	/**
	 * Write text on our image
	 *
	 * @param string $text	Text
	 * @param string $font	Path to font to use
	 * @param int $size	Size of text
	 * @return	void
	 */
	public function write( string $text, string $font, int $size ) : void
	{
		$draw			= new ImagickDraw();
		$draw->setTextAntialias( true );
		$draw->setGravity( Imagick::GRAVITY_CENTER );
		$draw->setFont( $font );
		$draw->setFontSize( $size );

		$draw->setFillColor( new ImagickPixel( "rgba(255,255,255,1)" ) );

		$this->imagick->annotateImage( $draw, 0, 0, 0, $text );
	}
	
	/**
	 * Return an array of supported extensions
	 *
	 * @return	array
	 */
	public static function supportedExtensions(): array
	{
		$extensions = static::$imageExtensions;
		
		if( in_array( 'WEBP', Imagick::queryFormats() ) )
		{
			$extensions[] = 'webp';
		}

		if( in_array( 'AVIF', Imagick::queryFormats() ) )
		{
			$extensions[] = 'avif';
		}

		return $extensions;
	}
}