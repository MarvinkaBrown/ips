<?php
/**
 * @brief		ACP Live Search
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Sep 2013
 */

namespace IPS\core\modules\admin\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Output;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Live Search
 */
class livesearch extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'livesearch_manage', 'core' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$results = array();
		
		try
		{
			$exploded = explode( '_', Request::i()->search_key );
			$app = Application::load( $exploded[0] );
			foreach ( $app->extensions( 'core', 'LiveSearch' ) as $k => $extension )
			{
				if ( $k === $exploded[1] )
				{
					$results = $extension->getResults( urldecode( Request::i()->search_term ) );
				}
			}
		}
		catch ( OutOfRangeException $e ) { }
						
		Output::i()->json( array_values( $results ) );
	}
}