<?php
/**
 * @brief		Cover Photo Helper
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 May 2014
 */

namespace IPS\Helpers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\File;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Cover Photo Helper
 */
class CoverPhoto
{
	/**
	 * File
	 */
	public ?File $file = NULL;
	
	/**
	 * Offset
	 */
	public int $offset = 0;
	
	/**
	 * Editable
	 */
	public bool $editable = FALSE;

	/**
	 * Maximum file size
	 */
	public ?int $maxSize = NULL;
	
	/**
	 * Overlay
	 */
	public ?string $overlay = null;
	
	/**
	 * Object
	 */
	public ?object $object;
	
	/**
	 * Constructor
	 *
	 * @param	File|NULL	$file		The file
	 * @param int $offset		The offset
	 * @param bool $editable	User can edit?
	 */
	public function __construct( File $file = NULL, int $offset = 0, bool $editable=FALSE )
	{
		$this->file = $file;
		$this->offset = $offset;
	}
	
	/**
	 * Render
	 *
	 * @return	string
	 */
	public function __toString()
	{
		if( !Request::i()->isAjax() )
		{
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_core.js', 'core' ) );
		}

		return Theme::i()->getTemplate( 'global', 'core' )->coverPhoto( $this->object->url(), $this );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		if ( $this->file )
		{
			$this->file->delete();
		}
	}
}