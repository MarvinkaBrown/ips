<?php
/**
 * @brief		SFTP Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 May 2013
 */

namespace IPS\Ftp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Ftp;
use function defined;
use function extension_loaded;
use function in_array;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * SFTP Class
 */
class Sftp extends Ftp
{
	/**
	 * @brief	SSH2 Connection resource
	 */
	protected $ssh;
	
	/**
	 * @brief	FTP Connection resource
	 */
	protected $sftp;
	
	/**
	 * @brief	Current Directory
	 */
	protected string $dir;
	
	/**
	 * Constructor
	 *
	 * @param string $host		Hostname
	 * @param string $username	Username
	 * @param string $password	Password
	 * @param int $port		Port
	 * @return	void
	 * @throws    Exception
	 * @throws	BadMethodCallException	If server does not have ssh
	 */
	public function __construct( string $host, string $username, string $password, int $port=22 )
	{
		if ( !extension_loaded( 'ssh2' ) )
		{
			throw new BadMethodCallException;
		}
				
		$this->ssh = ssh2_connect( $host, $port );		
		if ( $this->ssh === FALSE )
		{
			throw new Exception( 'COULD_NOT_CONNECT' );
		}
		
		if ( !@ssh2_auth_password( $this->ssh, $username, $password ) )
		{
			throw new Exception( 'COULD_NOT_LOGIN' );
		}
		
		$this->sftp = @ssh2_sftp( $this->ssh );
		if ( $this->sftp === FALSE )
		{
			throw new Exception( 'COULD_NOT_CONNECT' );
		}
	}

	/**
	 * chdir
	 *
	 * @param string $dir	Directory
	 * @return	void
	 * @throws    Exception
	 */
	public function chdir( string $dir ) : void
	{
		$dir = mb_substr( $dir, 0, 1 ) === '/' ? $dir : ( $this->dir . $dir );
		
		if ( $dir and !@ssh2_sftp_stat( $this->sftp, $dir ) )
		{
			throw new Exception( 'COULD_NOT_CHDIR' );
		}
		
		$this->dir = ssh2_sftp_realpath( $this->sftp, $dir ) . '/';
	}
	
	/**
	 * cdup
	 *
	 * @return	void
	 * @throws    Exception
	 */
	public function cdup() : void
	{
		$this->chdir( mb_substr( $this->dir, 0, mb_strrpos( mb_substr( $this->dir, 0, -1 ), '/' ) ) );
	}
	
	/**
	 * mkdir
	 *
	 * @param string $dir	Directory
	 * @return	void
	 * @throws    Exception
	 */
	public function mkdir( string $dir ) : void
	{
		if ( !@ssh2_sftp_mkdir( $this->sftp, $this->dir . $dir ) )
		{
			throw new Exception( 'COULD_NOT_MKDIR' );
		}
	}
	
	/**
	 * ls
	 *
	 * @param string $path	Path
	 * @return	array|bool
	 */
	public function ls( string $path = '' ): bool|array
	{
		$return = array();
		$handle = opendir("ssh2.sftp://{$this->sftp}/{$this->dir}");
		while( $entry = readdir( $handle ) )
		{
			if ( !in_array( $entry, array( '.', '..' ) ) )
			{
				$return[] = $entry;
			}
		}
		return $return;
	}

	/**
	 * Raw list
	 *
	 * @param string $path		The path
	 * @param bool $recursive	Whether or not to recursively list
	 * @return	array
	 */
	public function rawList( string $path = '.', bool $recursive=FALSE ): array
	{
		$return = array();
		$handle = opendir("ssh2.sftp://{$this->sftp}/{$this->dir}");
		while( $entry = readdir( $handle ) )
		{
			if ( !in_array( $entry, array( '.', '..' ) ) )
			{
				$bits = array();
				$stats =  @ssh2_sftp_stat( $this->sftp, $this->dir . $entry );
								
				$perms = '---------';
				if ( isset( $stats['mode'] ) )
				{
					$blocks = array();
					$oct = mb_substr( decoct( $stats['mode'] ), -3 );
					for ( $i = 0; $i < 3; $i++ )
					{
						switch ( $oct[$i] )
						{
							case 1:
								$blocks[] = '--x';
								break;
							case 2:
								$blocks[] = '-w-';
								break;
							case 3:
								$blocks[] = '-wx';
								break;
							case 4:
								$blocks[] = 'r--';
								break;
							case 5:
								$blocks[] = 'r-x';
								break;
							case 6:
								$blocks[] = 'rw-';
								break;
							case 7:
								$blocks[] = 'rwx';
								break;
						}
					}
					$perms = implode( '', $blocks );
				}
				
				$bits[] = ( is_dir("ssh2.sftp://{$this->sftp}/{$this->dir}/{$entry}") ? 'd' : '-' ) . $perms;
				$bits[] = $stats['nlink'] ?? 0;
				$bits[] = $stats['uid'] ?? 0;
				$bits[] = $stats['gid'] ?? 0;
				$bits[] = $stats['size'] ?? 0;
				$bits[] = isset( $stats['mtime'] ) ? date( 'd M H:i', $stats['mtime'] ) : 0;
				$bits[] = $entry;
								
				$return[] = implode( ' ', $bits );
			}
		}
		return $return;
	}
	
	/**
	 * Upload File
	 *
	 * @param string $filename	Filename to use
	 * @param string $file		Path to local file
	 * @return	void
	 * @throws    Exception
	 */
	public function upload( string $filename, string $file ) : void
	{
		if ( !@ssh2_scp_send( $this->ssh, $file, $this->dir . $filename ) )
		{
			throw new Exception( 'COULD_NOT_UPLOAD' );
		}
	}

	/**
	 * Download File
	 *
	 * @param string $filename The file to download
	 * @param string|null $target Location to save downloaded file or NULL to return contents
	 * @param bool $returnPath Return the path to the downloaded file instead of the contents
	 * @return string|null File contents
	 */
	public function download( string $filename, string $target=NULL, bool $returnPath=FALSE ): ?string
	{
		$temp = FALSE;
		if ( $target === NULL )
		{
			$temp = TRUE;
			$target = tempnam( TEMP_DIRECTORY, 'IPS' );
		}
		
		if ( !@ssh2_scp_recv( $this->ssh, $this->dir . $filename, $target ) )
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
	 * Delete file
	 *
	 * @param string $file		Path to file
	 * @return	void
	 * @throws    Exception
	 */
	public function delete( string $file ) : void
	{
		if ( !@ssh2_sftp_unlink( $this->sftp, $this->dir . $file ) )
		{
			throw new Exception( 'COULD_NOT_DELETE' );
		}
	}

	/**
	 * Get file size (if possible)
	 *
	 * @param string $file Path to file
	 * @return float|int
	 */
	public function size( string $file ): float|int
	{
		if( $stats = @ssh2_sftp_stat( $this->sftp, $this->dir . $file ) )
		{
			if( isset( $stats['size'] ) )
			{
				return sprintf( "%u", $stats['size'] );
			}
		}

		return 0;
	}
	
	/**
	 * Delete directory
	 *
	 * @param string $dir		Path to directory
	 * @param bool $recursive	Recursive? (If FALSE and directory is not empty, operation will fail)
	 * @return	void
	 * @throws    Exception
	 */
	public function rmdir( string $dir, bool $recursive=FALSE ) : void
	{	
		if ( $recursive )
		{			
			$this->chdir( $dir );
			foreach ($this->rawList() as $data )
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
				
		if ( !@ssh2_sftp_rmdir( $this->sftp, $this->dir . $dir ) )
		{
			throw new Exception( 'COULD_NOT_DELETE' );
		}
	}
}