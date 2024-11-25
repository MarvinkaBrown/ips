<?php
/**
 * @brief		FTP Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 May 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use FTP\Connection;
use IPS\Ftp\Exception;
use function defined;
use function function_exists;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * FTP Class
 */
class Ftp
{
	/**
	 * @brief	Connection resource
	 */
	protected ?Connection $ftp = null;

	/**
	 * Constructor
	 *
	 * @param string $host		Hostname
	 * @param string $username	Username
	 * @param string $password	Password
	 * @param int $port		Port
	 * @param bool $secure		Use secure SSL-FTP connection?
	 * @param int $timeout	Timeout (in seconds)
	 * @return	void
	 * @throws	Exception
	 */
	public function __construct( string $host, string $username, string $password, int $port=21, bool $secure = false, int $timeout=10 )
	{
		if ( $secure )
		{
			if( !function_exists('ftp_ssl_connect') )
			{
				throw new Exception( 'SSL_NOT_AVAILABLE' );
			}

			$this->ftp = @ftp_ssl_connect( $host, $port, $timeout );
		}
		else
		{
			$this->ftp = @ftp_connect( $host, $port, $timeout );
		}
		
		if ( $this->ftp === FALSE )
		{
			throw new Exception( 'COULD_NOT_CONNECT' );
		}
		if ( !@ftp_login( $this->ftp, $username, $password ) )
		{
			throw new Exception( 'COULD_NOT_LOGIN' );
		}

		/* Typically if passive mode is required, ftp_nlist will return FALSE instead of an array */
		if( $this->ls() === FALSE )
		{
			@ftp_pasv( $this->ftp, true );
		}
	}
	
	/**
	 * Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if( $this->ftp !== NULL )
		{
			@ftp_close( $this->ftp );
		}
	}
	
	/**
	 * chdir
	 *
	 * @param string $dir	Directory
	 * @return	void
	 * @throws	Exception
	 */
	public function chdir( string $dir ) : void
	{
		if ( !@ftp_chdir( $this->ftp, $dir ) )
		{
			throw new Exception( 'COULD_NOT_CHDIR' );
		}
	}
	
	/**
	 * cdup
	 *
	 * @return	void
	 * @throws	Exception
	 */
	public function cdup() : void
	{
		if ( !@ftp_cdup( $this->ftp ) )
		{
			throw new Exception( 'COULD_NOT_CDUP' );
		}
	}
	
	/**
	 * mkdir
	 *
	 * @param string $dir	Directory
	 * @return	void
	 * @throws	Exception
	 */
	public function mkdir( string $dir ) : void
	{
		if ( !@ftp_mkdir( $this->ftp, $dir ) )
		{
			throw new Exception( 'COULD_NOT_MKDIR' );
		}
	}
	
	/**
	 * ls
	 *
	 * @param string $path	Argument to pass to ftp_nlist
	 * @return	array|bool
	 */
	public function ls( string $path = '.' ): bool|array
	{
		return ftp_nlist( $this->ftp, $path );
	}

	/**
	 * Raw list
	 *
	 * @param string $path		Argument to pass to ftp_nlist
	 * @param bool $recursive	Whether or not to list recursively
	 * @return	array
	 */
	public function rawList( string $path = '.', bool $recursive = false ): array
	{
		return ftp_rawlist( $this->ftp, $path, $recursive );
	}
	
	/**
	 * Upload File
	 *
	 * @param string $filename	Filename to use
	 * @param string $file		Path to local file
	 * @return	void
	 * @throws	Exception
	 */
	public function upload( string $filename, string $file ) : void
	{
		if ( !@ftp_put( $this->ftp, $filename, $file, FTP_BINARY ) )
		{
			throw new Exception( 'COULD_NOT_UPLOAD' );
		}
	}
	
	/**
	 * Download File
	 *
	 * @param string $filename	The file to download
	 * @param string|null $target		Location to save downloaded file or NULL to return contents
	 * @param bool $returnPath	Return the path to the downloaded file instead of the contents
	 * @return	string|null		File contents
	 * @throws	Exception
	 */
	public function download( string $filename, ?string $target=NULL, bool $returnPath = false ): ?string
	{
		$temp = FALSE;
		if ( $target === NULL )
		{
			$temp = TRUE;
			$target = tempnam( TEMP_DIRECTORY, 'IPS' );
		}
		
		if ( !@ftp_get( $this->ftp, $target, $filename, FTP_BINARY ) )
		{
			throw new Exception( 'COULD_NOT_DOWNLOAD' );
		}

		/* We use this to avoid out of memory errors - just return path */
		if( $returnPath === TRUE )
		{
			return $target;
		}

		$result = file_get_contents( $target );
		
		if ( $temp )
		{
			@unlink( $target );
		}
		
		return $result;		
	}
	
	/**
	 * CHMOD
	 * 
	 * @param string $filename	The file to CHMOD
	 * @param int $mode		Mode (in octal form)
	 * @return	void
	 * @throws	Exception	CHMOD_ERROR
	 */
	public function chmod( string $filename, int $mode ) : void
	{
		if( !@ftp_chmod( $this->ftp, $mode, $filename ) )
		{
			throw new Exception( 'CHMOD_ERROR' );
		}
	}
	
	/**
	 * Delete file
	 *
	 * @param string $file		Path to file
	 * @return	void
	 * @throws	Exception
	 */
	public function delete( string $file ) : void
	{
		if ( !@ftp_delete( $this->ftp, $file ) )
		{
			throw new Exception( 'COULD_NOT_DELETE' );
		}
	}

	/**
	 * Get file size (if possible)
	 *
	 * @param string $file		Path to file
	 * @return	float|int
	 */
	public function size( string $file ): float|int
	{
		$size = @ftp_size( $this->ftp, $file );

		if ( !$size OR $size == -1 )
		{
			$size = 0;

			$rawValue = @ftp_raw( $this->ftp, "SIZE " . $file );

			if( mb_substr( $rawValue[0], 0, 3 ) == 213 )
			{
				$size = str_replace( '213 ', '', $rawValue[0] );
			}
		}

		return sprintf( "%u", $size );
	}
	
	/**
	 * Delete directory
	 *
	 * @param string $dir		Path to directory
	 * @param bool $recursive	Recursive? (If FALSE and directory is not empty, operation will fail)
	 * @return	void
	 * @throws	Exception
	 */
	public function rmdir( string $dir, bool $recursive=FALSE ) : void
	{	
		if ( $recursive )
		{
			$this->chdir( $dir );
			foreach ( ftp_rawlist( $this->ftp, '.' ) as $data )
			{
				preg_match( '/^(.).*\s(.*)$/', $data, $matches );
				if ( $matches[2] !== '.' and $matches[2] !== '..' )
				{
					if ( $matches[1] === 'd' )
					{
						$this->rmdir($matches[2], TRUE);
					}
					else
					{
						$this->delete( $matches[2] );
					}
				}
			}
			$this->cdup();
		}
				
		if ( !@ftp_rmdir( $this->ftp, $dir ) )
		{
			throw new Exception( 'COULD_NOT_DELETE' );
		}
	}
}