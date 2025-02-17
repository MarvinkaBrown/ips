<?php
/**
 * @brief		Image Class - GD
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Feb 2013
 */

namespace IPS\Image;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Image;
use IPS\IPS;
use IPS\Settings;
use function defined;
use function function_exists;
use function intval;
use function is_resource;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Image Class - GD
 */
class Gd extends Image
{	
	/**
	 * @brief	Image Resource
	 */
	public $image;

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

		/* Create the resource */
		$this->image = @imagecreatefromstring( $contents );
		if ( $this->image === FALSE )
		{
			if ( $error = IPS::$lastError )
			{
				throw new InvalidArgumentException( $error->getMessage(), $error->getCode() );
			}

			throw new InvalidArgumentException;
		}

		/* Set width/height */
		$this->width = imagesx( $this->image );
		$this->height = imagesy( $this->image );
	    
	    /* Try to maintain any transparency */
    	$this->setAlpha();
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
		$obj = new static(NULL, TRUE);
		$obj->type		= 'png';
		$obj->width		= $width;
		$obj->height	= $height;
		$obj->image		= imagecreatetruecolor( $width, $height );
		$bgColor		= imagecolorallocatealpha( $obj->image, $rgb[0], $rgb[1], $rgb[2], 1 );
		imagefill( $obj->image, 0, 0, $bgColor );

		return $obj;
	}

	/**
	 * Write text on our image
	 *
	 * @param string $text			Text
	 * @param string $font			Path to font to use
	 * @param int $size			Size of text
	 * @return	void
	 * @note	Some latin characters have inherent left padding, so when we want to center a letter visually we need to account for this
	 */
	public function write( string $text, string $font, int $size ) : void
	{
		$fontColor = imagecolorallocate( $this->image, 255, 255, 255 );

		$box = imagettfbbox( $size, 0, $font, $text );
		$x   = intval( ( imagesx( $this->image ) - abs( max( $box[2], $box[4] ) ) ) / 2 ) - ( ( $box[0] > 0 ) ? ( $box[0] / 2.5 ) : 0 );
		$y	 = intval( ( imagesy( $this->image ) + ( abs( $box[5] ) - abs( $box[1] ) ) ) / 2 );

		imagettftext( $this->image, $size, 0, $x, $y, $fontColor, $font, $text );
	}
	
	/**
	 * Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if( is_resource( $this->image ) )
		{
			imagedestroy( $this->image );
		}
	}
	
	/**
	 * Get Contents
	 *
	 * @return	string
	 */
	public function __toString()
	{
		$this->setAlpha();

		ob_start();
		switch( $this->type )
		{
			case 'gif':
				if ( $this->isAnimatedGif )
				{
					return (string) $this->contents;
				}
				imagegif( $this->image );
			break;
			
			case 'jpeg':
				$quality	= Settings::i()->image_jpg_quality ?: 85;

				imagejpeg( $this->image, NULL, $quality );
			break;
			
			case 'png':
				$quality	= Settings::i()->image_png_quality_gd ?: NULL;

				imagepng( $this->image, NULL, $quality );
			break;

			case 'webp':
				$quality	= Settings::i()->image_jpg_quality ?: 85;

				imagewebp( $this->image, NULL, $quality );
			break;

			case 'avif':
				$quality = Settings::i()->image_jpg_quality ?: 85;
				imageavif( $this->image, null, $quality );
				break;

		}
		return ob_get_clean();
	}

	/**
	 * Resets alpha transparency preservation
	 *
	 * @return void
	 */
	protected function setAlpha() : void
	{
		/* Turn off alpha blending and turn on saving of alpha channel info (this requires turning off alpha blending) */
		imagealphablending( $this->image, false );
		imagesavealpha( $this->image, true );
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
		$this->_manipulate($width, $height, FALSE);
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
		$this->_manipulate($width, $height, TRUE);
	}

	/**
	 * Resize and/or crop image
	 *
	 * @param int $width			Width (in pixels)
	 * @param int $height			Height (in pixels)
	 * @param bool $crop			Crop image to provided dimensions
	 * @return	void
	 */
	public function _manipulate( int $width, int $height, bool $crop=FALSE ) : void
	{
		if ( $this->isAnimatedGif )
		{
			return;
		}
			
		/* Create a new canvas */
		$width = ceil( $width );
		$height = ceil( $height );
		$newImage = imagecreatetruecolor( $width, $height );
		switch( $this->type )
		{			
			case 'gif':
				imagealphablending( $newImage, FALSE );
				// PHP8 fix, see https://stackoverflow.com/questions/3874533/what-could-cause-a-color-index-out-of-range-error-for-imagecolorsforindex
				$transindex = imagecolortransparent( $this->image );
				$palletsize = imagecolorstotal($this->image);
				if( $transindex >= 0 AND $transindex < $palletsize )
				{
					$transcol	= @imagecolorsforindex( $this->image, $transindex );
					$transindex	= imagecolorallocatealpha( $newImage, $transcol['red'], $transcol['green'], $transcol['blue'], 127 );
					imagefill( $newImage, 0, 0, $transindex );
				}
			break;

			case 'png':
			case 'jpg':
				/* We need to fill the background as transparent. If we copy a watermark image here (resizing it down for instance) and it has
					transparency, then the background color will show through, so it needs to be transparent too. */
				$transparent = imagecolorallocatealpha( $newImage, 0, 0, 0, 127 ); 
				imagefill( $newImage, 0, 0, $transparent ); 
			break;
		}
		
		/* Crop the image? */
		if( $crop === TRUE )
		{
			/* First, downsize the image */
			$ratio	= ( $this->width / $this->height );

			if ( $width / $height > $ratio ) 
			{
				$nheight	= $width / $ratio;
				$nwidth		= $width;
			}
			else
			{
				$nwidth		= $height * $ratio;
				$nheight	= $height;
			}

			$this->resizeToMax( $nwidth, $nheight );

			/* Then we use imagecopy which will crop */
			imagecopy( $newImage, $this->image, 0, 0, 0, 0, $this->width, $this->height );
		}
		else
		{
			/* Copy the image resampled */
			imagecopyresampled( $newImage, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height );
		}

		/* Replace */
		imagedestroy( $this->image );
		$this->image = $newImage;
		
		/* Set width/height */
		$this->width = imagesx( $this->image );
		$this->height = imagesy( $this->image );

		$this->setAlpha();
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
		/* Create a new canvas */
		$newImage = imagecreatetruecolor( ( $point2X - $point1X > 0 ) ? $point2X - $point1X : 0, ( $point2Y - $point1Y > 0 ) ? $point2Y - $point1Y : 0 );
		switch( $this->type )
		{			
			case 'gif':
				imagealphablending( $newImage, FALSE );

				// PHP8 fix, see https://stackoverflow.com/questions/3874533/what-could-cause-a-color-index-out-of-range-error-for-imagecolorsforindex
				$transindex = imagecolortransparent( $this->image );
				$palletsize = imagecolorstotal($this->image);
				if( $transindex >= 0 AND $transindex < $palletsize )
				{
					$transcol	= @imagecolorsforindex( $this->image, $transindex );
					$transindex	= imagecolorallocatealpha( $newImage, $transcol['red'], $transcol['green'], $transcol['blue'], 127 );
					imagefill( $newImage, 0, 0, $transindex );
				}
			break;

			case 'png':
			case 'jpg':
				/* We need to fill the background as transparent. If we copy a watermark image here (resizing it down for instance) and it has
					transparency, then the background color will show through, so it needs to be transparent too. */
				$transparent = imagecolorallocatealpha( $newImage, 0, 0, 0, 127 ); 
				imagefill( $newImage, 0, 0, $transparent ); 
			break;
		}
		
		/* Then we use imagecopy which will crop */
		imagecopy( $newImage, $this->image, 0, 0, $point1X, $point1Y, $point2X - $point1X, $point2Y - $point1Y );
		
		/* Replace */
		imagedestroy( $this->image );
		$this->image = $newImage;
		
		/* Set width/height */
		$this->width = imagesx( $this->image );
		$this->height = imagesy( $this->image );

		$this->setAlpha();
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
		/* Turn on alpha blending for both images */
		imagealphablending( $this->image, true );
		imagealphablending( $image->image, true );

		/* Copy the image over - typically a watermark */
		imagecopy( $this->image, $image->image, $x, $y, 0, 0, $image->width, $image->height );
	}

	/**
	 * Rotate image
	 *
	 * @param int $angle	Angle of rotation
	 * @return	void
	 */
	public function rotate( int $angle ) : void
	{
		$this->image	= imagerotate( $this->image, $angle, 0 );

		/* Set width/height */
		$this->width = imagesx( $this->image );
		$this->height = imagesy( $this->image );
	}

	/**
	 * Flip this image vertically
	 *
	 * @return void
	 */
	public function flip(): void
	{
		imageflip( $this->image, IMG_FLIP_HORIZONTAL );
	}
	
	/**
	 * Set Image Orientation
	 *
	 * @param int $orientation	The orientation
	 * @return	void
	 */
	public function setImageOrientation( int $orientation ) : void
	{
		/* Note, GD does not require orientation to be set after rotation */
	}

	/**
	 * Can we write text reliably on an image?
	 *
	 * @return	bool
	 */
	public static function canWriteText(): bool
	{
		return function_exists( 'imagettfbbox' );
	}
	
	/**
	 * Return an array of supported extensions
	 *
	 * @return	array
	 */
	public static function supportedExtensions(): array
	{
		$extensions = static::$imageExtensions;

		if( isset( gd_info()['WebP Support'] ) and gd_info()['WebP Support'] )
		{
			$extensions[] = 'webp';
		}

		if( isset( gd_info()['AVIF Support'] ) and gd_info()['AVIF Support'] )
		{
			$extensions[] = 'avif';
		}

		return $extensions;
	}
}