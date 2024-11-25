<?php
/**
 * @brief		File IteratorIterator
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Oct 2013
 */

namespace IPS\File;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Countable;
use Exception;
use IPS\File;
use IteratorIterator;
use Traversable;
use function defined;
use function is_callable;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File IteratorIterator
 */
class Iterator extends IteratorIterator implements Countable
{
	/**
	 * @brief	Stroage Extension
	 */
	protected string|null $storageExtension = null;
	
	/**
	 * @brief	URL Field
	 */
	protected $urlField = null;
	
	/**
	 * @brief	URLs Only
	 */
	protected bool $fileUrlsOnly = false;
	
	/**
	 * @brief	Used to restore 'real' names when filenames cleaned (eg adh1029_file.php back to fi^le.php)
	 */
	protected ?string $replaceNameField = null;

	/**
	 * @brief	Used to pre-cache the filesize value if available
	 */
	protected ?string $fileSizeField = null;

	
	/**
	 * Constructor
	 *
	 * @param	Traversable $iterator			The iterator
	 * @param	string		$storageExtension	The storage extension
	 * @param	string|callable|NULL	$urlField			If passed a string, will look for an element with that key in the data returned from the iterator
	 * @param	bool		$fileUrlsOnly		Only return the file URL instead of the file object
	 * @param	string|NULL	$replaceNameField	If passed a string, it will replace the originalFilename with the data in the array. Used to restore 'real' names when filenames cleaned (eg adh1029_file.php back to fi^le.php)
	 * @param 	string|null		$fileSizeField		Field to use to pre-cache the filesize
	 * @return	void
	 */
	public function __construct( Traversable $iterator, string $storageExtension, string|callable|null $urlField=NULL, bool $fileUrlsOnly=FALSE, ?string $replaceNameField=NULL, ?string $fileSizeField=NULL )
	{
		$this->storageExtension = $storageExtension;
		$this->urlField = $urlField;
		$this->fileUrlsOnly = $fileUrlsOnly;
		$this->replaceNameField = $replaceNameField;
		$this->fileSizeField = $fileSizeField;
		parent::__construct( $iterator );
	}
	
	/**
	 * Get current
	 *
	 * @return	File|string
	 */
	public function current() : File|string
	{
		try
		{
			$data = $this->data();
			$urlField = NULL;
			
			if ( $this->urlField )
			{
				if ( !is_string( $this->urlField ) and is_callable( $this->urlField ) )
				{
					$urlFieldCallback = $this->urlField;
					$urlField = $urlFieldCallback( $data );
				}
				else
				{
					$urlField = $this->urlField;
				}
			}

			$fileSize = ( $this->fileSizeField AND isset( $data[ $this->fileSizeField ] ) ) ? $data[ $this->fileSizeField ] : ( $this->fileSizeField === FALSE ? FALSE : NULL );
			$obj = File::get( $this->storageExtension, $urlField ? $data[ $urlField ] : $data, $fileSize );
			
			if ( $this->replaceNameField and ! empty( $data[ $this->replaceNameField ] ) )
			{
				$obj->originalFilename = $data[ $this->replaceNameField ];
			}
			
			return ( $this->fileUrlsOnly ) ? (string) $obj->url : $obj;
		}
		catch ( Exception $e )
		{
			$this->next();
			return $this->current();
		}
	}
	
	/**
	 * Get data
	 *
	 * @return	mixed
	 */
	public function data() : mixed
	{
		return parent::current();
	}
	
	/**
	 * Get count
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return $this->getInnerIterator()->count();
	}
}