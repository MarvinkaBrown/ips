<?php
/**
 * @brief		Image Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Feb 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Image\Gd;
use IPS\Image\Imagemagick;
use LogicException;
use function defined;
use function file_put_contents;
use function function_exists;
use function in_array;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Image Class
 */
abstract class Image
{
	/**
	 * @brief	Image Extensions
	 */
	public static array $imageExtensions = array( 'gif', 'jpeg', 'jpe', 'jpg', 'png' );
	
	/**
	 * @brief	Image Mime Types
	 */
	public static array $imageMimes = array( 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/webp', 'image/avif' );
	
	/**
	 * @brief	Allow EXIF processing
	 */
	public static bool $exifEnabled = TRUE;
	
	/**
	 * Determine if EXIF extraction is supported
	 *
	 * @return	bool
	 */
	public static function exifSupported(): bool
	{
		return function_exists( 'exif_read_data' ) and static::$exifEnabled;
	}
	
	/**
	 * @brief	Has the image been automatically rotated?
	 */
	public bool $hasBeenRotated = FALSE;

	/**
	 * Create Object
	 *
	 * @param	string	$contents	    Contents
	 * @param   bool    $checkRotation  Check image to correct rotation
	 * @return    Imagemagick|Gd|Image
	 * @throws	InvalidArgumentException
	 * @link	https://en.wikipedia.org/wiki/List_of_file_signatures
	 */
	public static function create( string $contents, bool $checkRotation=TRUE ): Imagemagick|Image|Gd
	{
		/* Work out the type */
		$imageType = NULL;
		$signatures = array(
			'gif'	=> array(
				'47' . '49' . '46' . '38' . '37' . '61',
				'47' . '49' . '46' . '38' . '39' . '61'
			),
			'jpeg'	=> array(
				'ff' . 'd8' . 'ff'
			),
			'png'	=> array(
				'89' . '50' . '4e' . '47' . '0d' . '0a' . '1a' . '0a'
			),
		);

		$bytesNeeded = max( array_merge( array_map( 'strlen', array_map( 'hex2bin', array_merge( $signatures['gif'], $signatures['jpeg'], $signatures['png'] ) ) ), array( 12 ) ) );
		$fileHeader = substr( $contents, 0, $bytesNeeded );

		/* Try webp first since it's special: RIFF, 4 bytes for size, WEBP:  */
		if ( bin2hex( substr( $fileHeader, 0, 4 ) ) === '52' . '49' . '46' . '46' AND bin2hex( substr( $fileHeader, 8, 4 ) ) === '57' . '45' . '42' . '50' )
		{
			$imageType = 'webp';
		}

		/* AVIF has 3 bytes of 00, then another byte that varies, and then ftypavif */
		if( bin2hex( substr( $fileHeader, 0, 3 ) ) === '00' . '00' . '00' AND bin2hex( substr( $fileHeader, 4, 8 ) ) === '66' . '74' . '79' . '70' . '61' . '76' . '69' . '66' )
		{
			$imageType = 'avif';
		}

		/* If it's not webp, try the rest of the image types */
		if( !$imageType )
		{
			foreach ( $signatures as $type => $_signatures )
			{
				foreach ( $_signatures as $signature )
				{
					if ( bin2hex( substr( $fileHeader, 0, strlen( hex2bin( $signature ) ) ) ) === $signature )
					{
						$imageType = $type;
						break 2;
					}
				}
			}
		}

		if ( $imageType === NULL )
		{
			throw new InvalidArgumentException;
		}
				
		/* Create object */
		if( Settings::i()->image_suite == 'imagemagick' and class_exists( 'Imagick', FALSE ) )
		{
			$obj = new Imagemagick( $contents );
		}
		else
		{
			$obj = new Gd( $contents );
		}
		$obj->type = $imageType;
		
		/* Animated? @see https://www.php.net/manual/en/function.imagecreatefromgif.php#102915 */
		if ( $obj->type === 'gif' and preg_match( '#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $contents ) )
		{
			$obj->isAnimatedGif = TRUE;
			$obj->contents = $contents;
		}
				
		/* Set EXIF data immediately */
		if( static::exifSupported() AND $checkRotation )
		{
			$obj->setExifData( $contents );
		
			/* If the image is misoriented, attempt to automatically reorient */
			if ( $orientation = $obj->getImageOrientation() )
			{
				$mirrored = in_array( $orientation, [2,4,5,7] );
				$degrees = match ($orientation) {
					3,4 => 180,
					5,6 => 90,
					7,8 => -90,
					default => 0
				};

				if ( $degrees !== 0 OR $mirrored )
				{
					$obj->rotate( $degrees );
					if ( $mirrored )
					{
						$obj->flip();
					}

					$obj->hasBeenRotated = true;
				}

				/* ImageMagick requires orientation be reset when rotated */
				if ( $degrees !== 0 OR $mirrored OR $obj instanceof Imagemagick )
				{
					$obj->setImageOrientation( 1 );
				}
			}
		}
		
		/* Return */
		return $obj;
	}
	
	/**
	 * @brief	Type ('png', 'jpeg' or 'gif')
	 */
	public ?string $type = NULL;
	
	/**
	 * @brief	Width
	 */
	public ?int $width = null;
	
	/**
	 * @brief	Height
	 */
	public ?int $height = null;
		
	/**
	 * @brief	Is this an animated gif?
	 */
	public bool $isAnimatedGif	= FALSE;
	
	/**
	 * @brief	Contents of the image file when animated gif
	 */
	public ?string $contents			= NULL;

	/**
	 * @brief	EXIF data - has to be pulled and stored before GD manipulates image
	 */
	protected array $exif				= array();
	
	/**
	 * Resize to maximum
	 *
	 * @param int|null $maxWidth		Max Width (in pixels) or NULL
	 * @param int|null $maxHeight		Max Height (in pixels) or NULL
	 * @param bool $retainRatio	If TRUE, the image will keep it's current width/height ratio rather than being squashed
	 * @return	bool		Returns TRUE if it actually resized, or FALSE if it wasn't necessary
	 */
	public function resizeToMax(int $maxWidth=NULL, int $maxHeight=NULL, bool $retainRatio=TRUE ): bool
	{
		/* If the image is smaller than max we can skip */
		if( ( $maxWidth == NULL or $this->width < $maxWidth ) and ( $maxHeight == NULL or $this->height < $maxHeight ) )
		{
			return FALSE;
		}

		/* Work out the maximum width/height */
		$width = ( $maxWidth !== NULL and $this->width > $maxWidth ) ? $maxWidth : $this->width;
		$height = ( $maxHeight !== NULL and $this->height > $maxHeight ) ? $maxHeight : $this->height;
				
		if ( $width != $this->width or $height != $this->height )
		{
			/* Adjust the width/height as necessary if we want to keep the ratio */
			if ( $retainRatio === TRUE )
			{
				if ( ( $this->height - $height ) <= ( $this->width - $width ) )
				{
					$ratio = $this->height / $this->width;
					$height = $width * $ratio;
				}
				else
				{
					$ratio = $this->width / $this->height;
					$width = $height * $ratio;
				}
			}

			/* And resize */
			$this->resize( $width, $height );
			return TRUE;
		}
	
		return FALSE;
	}
	
	/**
	 * Add Watermark
	 *
	 * @param Image $watermark	The watermark
	 * @return	void
	 */
	public function watermark( Image $watermark ) : void
	{
		/* If it's too big, resize the watermark */
		$watermark->resizeToMax( $this->width, $this->height );

		/* Impose */		
		$this->impose( $watermark, $this->width - $watermark->width, $this->height - $watermark->height );
	}

	/**
	 * Parse file object to extract EXIF data
	 *
	 * @return	array
	 * @throws	LogicException
	 */
	public function parseExif(): array
	{
		if( !static::exifSupported() )
		{
			throw new LogicException( 'NO_EXIF' );
		}

		if( !in_array( $this->type, array( 'jpeg', 'jpg', 'jpe' ) ) )
		{
			return array();
		}

		$result	= array();

		/* Read the data and store in an array */
		if( $values = $this->getExifData() )
		{
			foreach( $values as $section => $data )
			{
				foreach( $data as $name => $value )
				{
					$result[ $section . '.' . $name ]	= $value;
				}
			}
		}

		/* Return the EXIF data */
		return $result;
	}
	
	/**
	 * Get EXIF data, if possible
	 *
	 * @return	array
	 */
	public function getExifData(): array
	{
		return $this->exif;
	}

	/**
	 * Get EXIF data, if possible
	 *
	 * @param string $contents	Image contents
	 * @return	void
	 */
	public function setExifData( string $contents ) : void
	{
		if( !in_array( $this->type, array( 'jpeg', 'jpg', 'jpe' ) ) )
		{
			return;
		}

		/* Exif requires a file on disk, so write it temporarily */
		$temporary	= tempnam( TEMP_DIRECTORY, 'exif' );
		file_put_contents( $temporary, $contents );

		$result	= @exif_read_data( $temporary, NULL, TRUE );

		/* Remove the temporary file */
		if( @is_file( $temporary ) )
		{
			@unlink( $temporary );
		}

		$this->exif	= $result;
	}

	/**
	 * Create a new blank canvas image
	 *
	 * @param int $width	Width
	 * @param int $height	Height
	 * @param array $rgb	Color to use for bg
	 * @return    Image
	 */
	public static function newImageCanvas( int $width, int $height, array $rgb ): Image
	{
		if( Settings::i()->image_suite == 'imagemagick' and class_exists( 'Imagick', FALSE ) )
		{
			return Imagemagick::newImageCanvas( $width, $height, $rgb );
		}
		else
		{
			return Gd::newImageCanvas( $width, $height, $rgb );
		}
	}

	/**
	 * Write text on our image
	 *
	 * @param string $text	Text
	 * @param string $font	Path to font to use
	 * @param int $size	Size of text
	 * @return	void
	 */
	abstract public function write( string $text, string $font, int $size ) : void;

	/**
	 * Get Contents
	 *
	 * @return	string
	 */
	abstract public function __toString();
	
	/**
	 * Resize
	 *
	 * @param int $width			Width (in pixels)
	 * @param int $height			Height (in pixels)
	 * @return	void
	 */
	abstract public function resize( int $width, int $height ) : void;

	/**
	 * Crop to a given width and height (will attempt to downsize first)
	 *
	 * @param int $width			Width (in pixels)
	 * @param int $height			Height (in pixels)
	 * @return	void
	 */
	abstract public function crop( int $width, int $height ) : void;
	
	/**
	 * Crop at specific points
	 *
	 * @param int $point1X		x-point for top-left corner
	 * @param int $point1Y		y-point for top-left corner
	 * @param int $point2X		x-point for bottom-right corner
	 * @param int $point2Y		y-point for bottom-right corner
	 * @return	void
	 */
	abstract public function cropToPoints( int $point1X, int $point1Y, int $point2X, int $point2Y ) : void;
	
	/**
	 * Impose image
	 *
	 * @param Image $image	Image to impose
	 * @param int $x		Location to impose to, x axis
	 * @param int $y		Location to impose to, y axis
	 * @return	void
	 */
	abstract public function impose( Image $image, int $x=0, int $y=0 ) : void;

	/**
	 * Rotate image
	 *
	 * @param int $angle	Angle of rotation
	 * @return	void
	 */
	abstract public function rotate( int $angle ) : void;

	/**
	 * @return void
	 */
	abstract public function flip():void;
	
	/**
	 * Get Image Orientation
	 *
	 * @return	int|NULL
	 */
	public function getImageOrientation(): ?int
	{
		if ( static::exifSupported() )
		{
			$exif = $this->parseExif();

			if ( isset( $exif['IFD0.Orientation'] ) )
			{
				return (int) $exif['IFD0.Orientation'];
			}
		}
		return null;
	}
	
	/**
	 * Set Image Orientation
	 *
	 * @param int $orientation The orientation
	 * @return	void
	 */
	abstract public function setImageOrientation( int $orientation ) : void;

	/**
	 * Can we write text reliably on an image?
	 *
	 * @return	bool
	 */
	public static function canWriteText(): bool
	{
		/* Create object */
		if( Settings::i()->image_suite == 'imagemagick' and class_exists( 'Imagick', FALSE ) )
		{
			return Image\Imagemagick::canWriteText();
		}
		else
		{
			return Image\Gd::canWriteText();
		}
	}
	
	/**
	 * Return an array of supported extensions
	 *
	 * @return	array
	 */
	public static function supportedExtensions(): array
	{
		if( Settings::i()->image_suite == 'imagemagick' and class_exists( 'Imagick', FALSE ) )
		{
			return Image\Imagemagick::supportedExtensions();
		}
		else
		{
			return Image\Gd::supportedExtensions();
		}
	}

	/**
	 * Is this a square image ( width == height )
	 *
	 * @return bool
	 */
	public function isSquare(): bool
	{
		return $this->width === $this->height;
	}
}