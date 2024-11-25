<?php
/**
 * @brief		Warn Reasons API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 June 2018
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\core\Warnings\Reason;
use IPS\Lang;
use IPS\Node\Api\NodeController;
use IPS\Node\Model;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Warn Reasons API
 */
class warnreasons extends NodeController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\core\Warnings\Reason';

	/**
	 * GET /core/warnreasons
	 * Get list of warn reasons
	 *
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\core\Warnings\Reason>
	 * @throws		1C292/S	NO_PERMISSION	The current authorized user does not have permission to issue warnings and as such cannot view the list of warn reasons
	 * @return PaginatedResponse<Reason>
	 */
	public function GETindex() : PaginatedResponse
	{
		/* Check permissions */
		if( $this->member AND ( !$this->member->modPermission('mod_can_warn') OR !$this->member->modPermission('mod_see_warn') ) )
		{
			throw new Exception( 'NO_PERMISSION', '1C292/S', 403 );
		}

		/* Return */
		return $this->_list();
	}

	/**
	 * GET /core/warnreasons/{id}
	 * Get specific warn reason
	 *
	 * @apiclientonly
	 * @param		int		$id			ID Number
	 * @throws		1C385/1	INVALID_ID	The warn reason does not exist
	 * @apireturn		\IPS\core\Warnings\Reason
	 * @return Response
	 */
	public function GETitem( int $id ) : Response
	{
		try
		{
			return $this->_view( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C385/1', 404 );
		}
	}

	/**
	 * POST /core/warnreasons
	 * Create a warn reason
	 *
	 * @apiclientonly
	 * @reqapiparam	string		name				Name for the warn reason
	 * @apiparam	string		defaultNotes		Default notes to associate with the warn reason
	 * @apiparam	int			points				Default points to be issued with the warn reason
	 * @apiparam	bool		pointsOverride		Whether or not moderators can override the default points
	 * @apiparam	bool		pointsAutoRemove	Whether or not to automatically remove points (defaults to false). If true, you must supply removePoints.
	 * @apiparam	string|null		removePoints		Timeframe to remove points after as a date interval (e.g. P2D for 2 days or PT6H for 6 hours)
	 * @apiparam	bool		removeOverride		Whether or not moderators can override the default points removal configuration
	 * @apireturn		\IPS\core\Warnings\Reason
	 * @throws		1C385/2	INVALID_POINTS_EXPIRATION	Points were specified as automatically removing but the removal time period was not supplied or is invalid
	 * @throws		1C385/4	NO_NAME		A name for the warn reason must be supplied
	 * @return Response
	 */
	public function POSTindex() : Response
	{
		if( !Request::i()->name )
		{
			throw new Exception( 'NO_NAME', '1C385/4', 400 );
		}

		if( isset( Request::i()->pointsAutoRemove ) AND Request::i()->pointsAutoRemove AND ( !isset( Request::i()->removePoints ) OR !Request::i()->removePoints ) )
		{
			throw new Exception( 'INVALID_POINTS_EXPIRATION', '1C385/2', 400 );
		}

		return new Response( 201, $this->_create()->apiOutput( $this->member ) );
	}

	/**
	 * POST /core/warnreasons/{id}
	 * Edit a warn reason
	 *
	 * @apiclientonly
	 * @reqapiparam	string		name				Name for the warn reason
	 * @apiparam	string		defaultNotes		Default notes to associate with the warn reason
	 * @apiparam	int			points				Default points to be issued with the warn reason
	 * @apiparam	bool		pointsOverride		Whether or not moderators can override the default points
	 * @apiparam	bool		pointsAutoRemove	Whether or not to automatically remove points (defaults to false). If true, you must supply removePoints.
	 * @apiparam	string|null		removePoints		Timeframe to remove points after as a date interval (e.g. P2D for 2 days or PT6H for 6 hours)
	 * @apiparam	bool		removeOverride		Whether or not moderators can override the default points removal configuration
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\core\Warnings\Reason
	 * @throws		1C385/3	INVALID_POINTS_EXPIRATION	Points were specified as automatically removing but the removal time period was not supplied or is invalid
	 * @return Response
	 */
	public function POSTitem( int $id ) : Response
	{
		if( isset( Request::i()->pointsAutoRemove ) AND Request::i()->pointsAutoRemove AND ( !isset( Request::i()->removePoints ) OR !Request::i()->removePoints ) )
		{
			throw new Exception( 'INVALID_POINTS_EXPIRATION', '1C385/3', 400 );
		}

		/* @var Reason $class */
		$class	= $this->class;
		$reason	= $class::load( $id );

		return new Response( 200, $this->_createOrUpdate( $reason )->apiOutput( $this->member ) );
	}

	/**
	 * DELETE /core/warnreasons/{id}
	 * Delete a warn reason
	 *
	 * @apiclientonly
	 * @param		int		$id			ID Number
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ) : Response
	{
		return $this->_delete( $id );
	}

	/**
	 * Create or update node
	 *
	 * @param	Model	$reason				The node
	 * @return	Model
	 */
	protected function _createOrUpdate( Model $reason ): Model
	{
		if( Request::i()->name )
		{
			Lang::saveCustom( 'core', 'core_warn_reason_' . $reason->id, Request::i()->name );
		}

		$reason->points				= (int) Request::i()->points;
		$reason->points_override	= (bool) Request::i()->pointsOverride;
		$reason->remove_override	= (bool) Request::i()->removeOverride;
		$reason->notes				= Request::i()->notes;

		if( isset( Request::i()->pointsAutoRemove ) OR isset( Request::i()->removePoints ) )
		{
			if( !Request::i()->pointsAutoRemove )
			{
				$reason->remove	= -1;
				$reason->remove_unit = NULL;
			}
			else
			{
				$reason->remove	= (int) str_ireplace( array( 'P', 'T', 'H' ), '', Request::i()->removePoints );

				if( mb_strpos( Request::i()->removePoints, 'PT' ) === 0 )
				{
					$reason->remove_unit = 'h';
				}
				else
				{
					$reason->remove_unit = 'd';
				}
			}
		}

		return parent::_createOrUpdate( $reason );
	}
}