<?php
/**
 * @brief		FTP RecursiveDirectoryIterator Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Oct 2013
 */

namespace IPS\Ftp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Ftp;
use RecursiveIterator;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Recursive directory FTP iterator
 */
class RecursiveDirectoryFtpIterator extends FtpSplFileInfo implements RecursiveIterator
{
	/**
	 * @brief	Holds an array of iterable objects
	 */
	public ?array $contents		= NULL;

	/**
	 * @brief	The FTP class holding our connection
	 */
	protected ?Ftp $ftpObject	= NULL;

	/**
	 * @brief	Flag to store whether the iterator has been rewound
	 */
	protected bool $rewound		= FALSE;

	/**
	 * Constructor: Create a new recursive FTP directory iterator
	 *
	 * @param Ftp $ftp	The FTP object handle
	 * @param string $file	The filename or directory
	 * @param	int			$type	The type of item passed
	 * @return	void
	 * @throws InvalidArgumentException
	 */
	public function __construct( Ftp $ftp, string $file, int $type = self::TYPE_DIRECTORY )
	{
		$this->ftpObject	= $ftp;

		parent::__construct( $file, $type );
	}

	/**
	 * Return children of the current object
	 *
	 * @return static
	 */
	public function getChildren(): RecursiveDirectoryFtpIterator|static
	{
		return new RecursiveDirectoryFtpIterator( $this->ftpObject, $this->current()->getPath() );
	}

	/**
	 * Do we have any children?
	 *
	 * @return bool
	 */
	public function hasChildren(): bool
	{
		return $this->current()->isDir();
	}

	/**
	 * Return the current item
	 *
	 * @return mixed
	 */
	public function current(): mixed
	{
		return current( $this->contents );
	}

	/**
	 * Return the current key
	 *
	 * @return string
	 */
	public function key(): string
	{
		return $this->current()->getPathname();
	}

	/**
	 * Advance to the next item
	 *
	 * @return void
	 */
	public function next(): void
	{
		next( $this->contents );
	}

	/**
	 * Rewind and fetch all items
	 *
	 * @return void
	 */
	public function rewind() : void
	{
		/* Change to the appropriate directory */
		$this->ftpObject->chdir( $this->getPath() );

		/* Fetch our names and the raw listing */
		$names	= $this->ftpObject->ls( $this->getFilename() );
		$types	= $this->ftpObject->rawList($this->getFilename());

		/* Reset our contents array */
		$this->contents	= array();

		/* Now loop over the results we got */
		foreach( $names as $k => $name )
		{
			if( $name == '.' OR $name == '..' )
			{
				continue;
			}

			$this->contents[]	= new RecursiveDirectoryFtpIterator( $this->ftpObject, $this->getItemname( $name ), static::getTypeFromRaw( $types[ $k ] ) );
		}
	}

	/**
	 * Is this a valid item?
	 *
	 * @return bool
	 */
	public function valid(): bool
	{
		if( !$this->rewound )
		{
			$this->rewind();
			$this->rewound	= TRUE;
		}

		return ( $this->current() instanceof FtpSplFileInfo);
	}

	/**
	 * Return sub-path
	 *
	 * @return string
	 */
	public function getSubPath(): string
	{
		return $this->getPath();
	}

	/**
	 * Return sub-path + filename
	 *
	 * @return string
	 */
	public function getSubPathname(): string
	{
		return $this->getPathname();
	}
}