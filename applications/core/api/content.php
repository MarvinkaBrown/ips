<?php
/**
 * @brief		Content Item API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Aug 2023
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Exception;
use IPS\Api\Response;
use IPS\Application;
use IPS\Node\Api\NodeController;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Item API
 */
class content extends NodeController
{

	/**
	 * Class
	 */
	protected string $class = '';

	/**
	 * GET /core/content
	 * Get content item by class and ID
	 *
	 * @apiclientonly
	 * @param int $id			ID Number
	 * @apiparam	string	class	Class of content item to fetch e.g. IPS\forums\Topic
	 * @apiparam	int		id		ID of content item to fetch
	 * @throws		1C436/1	INVALID_CLASS	The class does not exist
	 * @throws		1C436/2	INVALID_ID	The item does not exist
	 * @apireturn	\IPS\Content\Item
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		foreach ( Application::allExtensions( 'core', 'ContentRouter' ) as $contentRouter )
		{

			foreach ( $contentRouter->classes as $class )
			{
				if( $class == Request::i()->class )
				{
					$this->class = $class;
				}
			}
		}

		if( !$this->class )
		{
			throw new Exception( 'INVALID_CLASS', '1C436/1', 404 );
		}

		try
		{
			return $this->_view( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C436/2', 404 );
		}
	}
}