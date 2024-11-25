<?php
/**
 * @brief		"Content" functions Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Apr 2013
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Output;
use IPS\Request;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * "Content" functions Controller
 */
class content extends Controller
{
	/**
	 * Find content
	 *
	 * @return	void
	 */
	protected function find() : void
	{
		if ( ! Request::i()->content_class AND ! Request::i()->content_id AND ! Request::i()->content_commentid )
		{
			Output::i()->error( 'node_error', '2S226/1', 404, '' );
		}
		
		$class = 'IPS\\' . implode( '\\', explode( '_', Request::i()->content_class ) );

		if ( ! class_exists( $class ) or ! in_array( 'IPS\Content', class_parents( $class ) ) )
		{
			Output::i()->error( 'node_error', '2S226/2', 404, '' );
		}
		
		try
		{
			$item = $class::load( Request::i()->content_id );

			if( isset( $item::$archiveClass ) AND method_exists( $item, 'isArchived' ) AND $item->isArchived() )
			{
				$commentClass = $class::$archiveClass;
			}
			else
			{
				$commentClass = $class::$commentClass;
			}

			$comment = $commentClass::load( Request::i()->content_commentid );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'node_error', '2S226/3', 404, '' );
		}
		
		/* Make sure we have permission to see this */
		if ( $item->canView() AND $comment->canView() )
		{
			Output::i()->redirect( $comment->url() );
		}
		else
		{
			Output::i()->error( 'node_error', '2S226/4', 404, '' );
		}
	}
}