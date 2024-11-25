<?php
/**
 * @brief		Archive Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Jul 2015
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Archive\Exception;
use OutOfRangeException;
use function defined;
use function dirname;
use function fclose;
use function file_put_contents;
use function fopen;
use function function_exists;
use function fwrite;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Archive Class
 */
abstract class Archive
{	
	/**
	 * Create object from local file
	 *
	 * @param	string	$path			Path to archive file
	 * @param	string	$containerName	The root folder name which should be ignored (with trailing slash)
	 * @return    Archive
	 */
	public static function fromLocalFile( string $path, string $containerName = '' ) : static
	{
		return new static;
	}

	/**
	 * Number of files
	 *
	 * @return	int
	 */
	abstract public function numberOfFiles(): int;
	
	/**
	 * Get file name
	 *
	 * @param	int	$i	File number
	 * @return	string
	 * @throws	OutOfRangeException
	 */
	abstract public function getFileName( int $i ): string;
	
	/**
	 * Get file contents
	 *
	 * @param	int	$i	File number
	 * @return	string
	 * @throws	OutOfRangeException
	 */
	abstract public function getFileContents( int $i ): string;
	
	/**
	 * @brief	The root folder name which should be ignored (with trailing slash)
	 */
	protected string $containerName = '';
	
	/**
	 * @brief	Ignore hidden files?
	 */
	public bool $ignoreHiddenFiles = TRUE;
	
	/**
	 * Extract
	 *
	 * @param string $destination	Destination directory
	 * @param int|null $limit			Number of files to extract
	 * @param int $offset			Offset
	 * @param Ftp|NULL	$ftp			If provided, the files will be extracted using FTP, otherwise will attempt to write manually
	 * @return	bool			If true, all files were extracted, if false, there is more to extract
	 */
	public function extract( string $destination, int $limit=NULL, int $offset=0, Ftp $ftp = NULL ): bool
	{
		$done = 0;
		while ( $limit === NULL or $limit > $done ) // OutOfRangeException will break if $limit is NULL
		{
			try
			{
				$path = $this->getFileName( $offset + $done );
				if ( $path and mb_substr( $path, -1 ) !== '/' and ( !$this->ignoreHiddenFiles or ( mb_substr( $path, 0, 1 ) !== '.' and mb_substr( $path, 0, 9 ) !== '__MACOSX/' ) ) )
				{
					/* Create a directory if needed */
					$dir = dirname( $path );
					$directories = array( $dir );
					while ( $dir != '.' )
					{
						$dir = dirname( $dir );
						if ( $dir != '.' )
						{
							$directories[] = $dir;
						}
					}
					foreach ( array_reverse( $directories ) as $dir )
					{
						if ( !is_dir( $destination . '/' . $dir ) )
						{
							if ( $ftp )
							{
								$ftp->mkdir( $dir );
							}
							else
							{
								@mkdir( $destination . '/' . $dir, FOLDER_PERMISSION_NO_WRITE);
							}
						}
					}
					
					/* Write contents */
					$contents = $this->getFileContents( $offset + $done );
					if ( $ftp )
					{
						$tmpFile = tempnam( TEMP_DIRECTORY, 'IPS' );
						file_put_contents( $tmpFile, $contents );
						
						$ftp->upload( $path, $tmpFile );

						@unlink( $tmpFile );
					}
					else
					{
						/* Determine if the file exists (we'll want to know this later) */
						$fileExists	= file_exists( $destination . '/' . $path );

						$fh = @fopen( $destination . '/' . $path, 'w+' );
						if ( $fh === FALSE )
						{
							throw new Exception( 'Error: ' . ( IPS::$lastError?->getMessage() ) . ', Path:' . $destination . '/' . $path, Exception::COULD_NOT_WRITE );
						}
						if ( @fwrite( $fh, $contents ) === FALSE )
						{
							throw new Exception( 'Error: ' . ( IPS::$lastError?->getMessage() ) . ', Path:' . $destination . '/' . $path, Exception::COULD_NOT_WRITE );
						}
						else
						{
							/* If the file existed before we started, we should clear it from opcache if opcache is enabled */
							if( $fileExists )
							{
								if ( function_exists( 'opcache_invalidate' ) )
								{
									@opcache_invalidate( $destination . '/' . $path );
								}
							}
							/* Otherwise, we should set file permissions on the file if it's brand new in case server defaults to something odd */
							else
							{
								@chmod( $destination . '/' . $path, FILE_PERMISSION_NO_WRITE);
							}
						}
						@fclose( $fh );
					}
				}
				
				$done++;
			}
			catch ( OutOfRangeException $e )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
}