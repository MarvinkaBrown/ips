<?php
/**
 * @brief		Template Plugin - Content: Database
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		12 March 2014
 */

namespace IPS\cms\extensions\core\OutputPlugins;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases;
use IPS\Extensions\OutputPluginsAbstract;
use IPS\Member;
use LogicException;
use OutofRangeException;
use function defined;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Content: Database
 */
class Database extends OutputPluginsAbstract
{
	/**
	 * @brief	Record how many database tags there are per page
	 */
	public static int $count = 0;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string|array		Code to eval
	 */
	public static function runPlugin( string $data, array $options ): string|array
	{
		if ( isset( $options['category'] ) )
		{
			return '\IPS\cms\Databases\Dispatcher::i()->setDatabase( "' . $data . '" )->setCategory( "' . $options['category'] . '" )->run()';
		}
		
		return '\IPS\cms\Databases\Dispatcher::i()->setDatabase( "' . $data . '" )->run()';
	}
	
	/**
	 * Do any processing before a page is added/saved
	 *
	 * @param string $data	  The initial data from the tag
	 * @param array $options  Array of options
	 * @param object $page	  Page being edited/saved
	 * @return	void
	 */
	public static function preSaveProcess( string $data, array $options, object $page ) : void
	{
		/* Keep a count of databases used so far */
		static::$count++;
		
		if ( static::$count > 1 )
		{
			throw new LogicException( Member::loggedIn()->language()->addToStack('cms_err_db_already_on_page') );
		}
	}
	
	/**
	 * Do any processing after a page is added/saved
	 *
	 * @param string $data	  The initial data from the tag
	 * @param array $options  Array of options
	 * @param object $page	  Page being edited/saved
	 * @return	void
	 */
	public static function postSaveProcess( string $data, array $options, object $page ) : void
	{
		try
		{
			if ( is_numeric( $data ) )
			{
				$database = Databases::load( $data );
			}
			else
			{
				$database = Databases::load( $data, 'database_key' );
			}
			
			if ( $database->id AND $page->id )
			{
				try
				{
					$page->mapToDatabase( $database->id );
				}
				catch( LogicException $ex )
				{
					throw new LogicException( $ex->getMessage() );
				}
			}
		}
		catch( OutofRangeException $ex ) { }
	}

}