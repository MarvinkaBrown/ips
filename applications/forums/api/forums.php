<?php
/**
 * @brief		Forums API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		3 Apr 2017
 */

namespace IPS\forums\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Data\Store;
use IPS\forums\Forum;
use IPS\Http\Url\Friendly;
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
 * @brief	Forums API
 */
class forums extends NodeController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\forums\Forum';
	
	/**
	 * GET /forums/forums
	 * Get list of forums
	 *
	 * @apiparam	int		clubs		0|1 Include club forums, default: 1
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @note		For requests using an OAuth Access Token for a particular member, only forums the authorized user can view will be included
	 * @apireturn		PaginatedResponse<IPS\forums\Forum>
	 * @return PaginatedResponse<Forum>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Return */
		return $this->_list();
	}

	/**
	 * GET /forums/forums/{id}
	 * Get specific forum
	 *
	 * @param		int		$id			ID Number
	 * @throws		1F363/1	INVALID_ID	The forum does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\forums\Forum
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			return $this->_view( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1F363/1', 404 );
		}
	}

	/**
	 * POST /forums/forums
	 * Create a forum
	 *
	 * @apiclientonly
	 * @reqapiparam	string		title				The forum title
	 * @apiparam	string		description			The forum description
	 * @apiparam	string		type				normal|redirect|category
	 * @apiparam	int|null	parent				The ID number of the parent the forum should be created in. NULL for root.
	 * @apiparam	string		password			Forum password
	 * @apiparam	int			theme				Theme to use as an override
	 * @apiparam	int			sitemap_priority	1-9 1 highest priority
	 * @apiparam	int			min_content			The minimum amount of posts to be able to view
	 * @apiparam	int			can_see_others		0|1 Users can see topics posted by other users?
	 * @apiparam	object		permissions			An object with the keys as permission options (view, read, add, reply, attachments) and values as permissions to use (which may be * to grant access to all groups, or an array of group IDs to permit access to)
	 * @apiparam	string	redirect_url		If creating a redirect forum, the URL to redirect to.
	 * @apireturn		\IPS\forums\Forum
	 * @throws		1F363/2	NO_TITLE	A title for the forum must be supplied
	 * @throws		1F363/3	NO_REDIRECT_URL	No redirect URL supplied.
	 * @throws		1F363/4	INVALID_TYPE		Invalid type of forum.
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		if ( !Request::i()->title )
		{
			throw new Exception( 'NO_TITLE', '1F363/2', 400 );
		}

		if ( !Request::i()->parent )
		{
			Request::i()->parent = -1;
		}

		return new Response( 201, $this->_create()->apiOutput( $this->member ) );
	}
	
	/**
	 * POST /forums/forums/{id}
	 * Edit a forum
	 *
	 * @apiclientonly
	 * @reqapiparam	string		title				The forum title
	 * @apiparam	string		description			The forum description
	 * @apiparam	string		type				normal|redirect|category
	 * @apiparam	int|null	parent				The ID number of the parent the forum should be created in. NULL for root.
	 * @apiparam	string		password			Forum password
	 * @apiparam	int			theme				Theme to use as an override
	 * @apiparam	int			sitemap_priority	1-9 1 highest priority
	 * @apiparam	int			min_content			The minimum amount of posts to be able to view
	 * @apiparam	int			can_see_others		0|1 Users can see topics posted by other users?
	 * @apiparam	object		permissions			An object with the keys as permission options (view, read, add, reply, attachments) and values as permissions to use (which may be * to grant access to all groups, or an array of group IDs to permit access to)
	 * @apiparam	string	redirect_url		If editing a redirect forum, the URL to redirect too.
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\forums\Forum
	 * @throws		1F363/3	NO_REDIRECT_URL	No redirect URL supplied.
	 * @throws		1F363/4	INVALID_TYPE		Invalid type of forum.
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		/* @var Forum $class */
		$class = $this->class;
		$forum = $class::load( $id );

		return new Response( 200, $this->_createOrUpdate( $forum )->apiOutput( $this->member ) );
	}
	
	/**
	 * DELETE /forums/forums/{id}
	 * Delete a forum
	 *
	 * @apiclientonly
	 * @param		int			$id							ID Number
	 * @apiparam	int			deleteChildrenOrMove		The ID number of the new parent or -1 to delete all child nodes.
	 * @apireturn		void
	 * @throws	1S359/1	INVALID_ID		The node ID does not exist
	 * @throws	1S359/2	INVALID_TARGET	The target node cannot be deleted because the new parent node does not exist
	 * @throws	1S359/3	HAS_CHILDREN	The target node cannot be deleted because it has children (pass deleteChildrenOrMove in the request to specify how to handle the children)
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		/* Load forum and verify that it is not used for comments */
		/* @var Forum $nodeClass */
		$nodeClass = $this->class;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}

		try
		{
			$node = $nodeClass::load( $id );
		}
		catch (OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2F363/8', 404 );
		}

		if ( $dbCategory = $node->isUsedByADownloadsCategory() )
		{
			throw new Exception( 'FORUM_USED_BY_DOWNLOADS', '2F363/9', 403 );
		}

		if ( $db = $node->isUsedByCms() )
		{
			throw new Exception( 'FORUM_USED_BY_DATABASE', '2F363/6', 403 );
		}

		return $this->_delete( $id, Request::i()->deleteChildrenOrMove ?? NULL );
	}

	/**
	 * Create or update node
	 *
	 * @param	Model	$forum				The node
	 * @return	Model
	 */
	protected function _createOrUpdate( Model $forum ): Model
	{
		foreach ( array( 'title' => "forums_forum_{$forum->id}", 'description' => "forums_forum_{$forum->id}_desc" ) as $fieldKey => $langKey )
		{
			if ( isset( Request::i()->$fieldKey ) )
			{
				Lang::saveCustom( 'forums', $langKey, Request::i()->$fieldKey );

				if ( $fieldKey === 'title' )
				{
					$forum->name_seo = Friendly::seoTitle( Request::i()->$fieldKey );
				}
			}
		}

		if( isset( Request::i()->parent ) )
		{
			$forum->parent_id = (int) Request::i()->parent ?: -1;

			if ( !isset( Request::i()->type ) )
			{
				if ( $forum->parent_id === -1 )
				{
					Request::i()->type = 'category';
				}
				else
				{
					Request::i()->type = 'normal';
				}
			}

			switch ( Request::i()->type )
			{
				case 'category':
					$forum->sub_can_post = 0;
					break;

				case 'normal':
				case 'discussion':
					$forum->sub_can_post = 1;
					break;

				case 'redirect':
					if ( !isset( Request::i()->redirect_url ) )
					{
						throw new Exception( 'NO_REDIRECT_URL', '1F363/3', 400 );
					}

					$forum->redirect_on = 1;
					$forum->redirect_url = Request::i()->redirect_url;
					break;

				default:
					throw new Exception( 'INVALID_TYPE', '1F363/4', 400 );
			}
		}

		if ( isset( Request::i()->password ) )
		{
			$forum->password = Request::i()->password;
		}

		if ( isset( Request::i()->theme ) )
		{
			$forum->skin_id = Request::i()->theme;
		}

		if ( isset( Request::i()->sitemap_priority ) )
		{
			$forum->ipseo_priority = Request::i()->sitemap_priority;
		}

		if ( isset( Request::i()->min_content ) )
		{
			$forum->min_content = Request::i()->min_content;
		}

		if ( isset( Request::i()->can_see_others ) )
		{
			$forum->can_view_others = Request::i()->can_see_others;
		}

		$forum = parent::_createOrUpdate( $forum );

		unset( Store::i()->forumsCustomNodes );

		return $forum;
	}
}