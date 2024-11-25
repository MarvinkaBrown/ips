<?php
/**
 * @brief		Application builder custom filter iterator
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Aug 2013

 */

namespace IPS\Application;

/* To prevent PHP errors (extending class does not exist) revealing path */

use FilesystemIterator;
use IPS\Application;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Custom filter iterator for application building
 */
class BuilderIterator extends RecursiveIteratorIterator
{
	/**
	 * @brief	The application
	 */
	protected Application $application;

	/**
	 * Constructor
	 *
	 * @param Application $application
	 */
	public function __construct( Application $application )
	{
		$this->application = $application;
		parent::__construct( new BuilderFilter( new RecursiveDirectoryIterator( \IPS\ROOT_PATH . "/applications/" . $application->directory, FilesystemIterator::SKIP_DOTS ) ) );
	}
	
	/**
	 * Current key
	 *
	 * @return	string
	 */
	public function key() : string
	{
		return mb_substr( parent::current(), mb_strlen( \IPS\ROOT_PATH . "/applications/" . $this->application->directory ) + 1 );
	}
}