<?php
/**
 * @brief		Abstract class that Controllers should extend
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		16 April 2014
 */

namespace IPS\cms\Databases;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller as DispatcherController;
use IPS\Http\Url;
use function defined;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract class that Controllers should extend
 */
abstract class Controller extends DispatcherController
{
	/** 
	 * @brief	Base URL
	 */
	public mixed $url;
	
	/**
	 * Constructor
	 *
	 * @param mixed|null $url		The base URL for this controller or NULL to calculate automatically
	 * @return	void
	 */
	public function __construct( mixed $url=NULL )
	{
		if ( $url === NULL )
		{
			$class		= get_called_class();
			$exploded	= explode( '\\', $class );
			$this->url = Url::internal( "app=cms&module=database", 'front' ); /* @todo fix URL */
		}
		else
		{
			$this->url = $url;
		}
	}

}