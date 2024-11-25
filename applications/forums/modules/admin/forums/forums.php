<?php
/**
 * @brief		forums
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		07 Jan 2014
 */

namespace IPS\forums\modules\admin\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\forums\Forum;
use IPS\Member;
use IPS\Node\Controller;
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
 * forums
 */
class forums extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\forums\Forum';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'forums_manage' );
		parent::execute();
	}
	
	/**
	 * Permissions
	 *
	 * @return	void
	 */
	protected function permissions() : void
	{
		try
		{
			$forum = Forum::load( Request::i()->id );

			if ( $forum->password and !$forum->can_view_others )
			{
				Member::loggedIn()->language()->words['perm_forum_perm__read'] = Member::loggedIn()->language()->addToStack( 'perm_forum_perm__read2_pass', FALSE );
			}
			elseif ( !$forum->can_view_others )
			{
				Member::loggedIn()->language()->words['perm_forum_perm__read'] = Member::loggedIn()->language()->addToStack( 'perm_forum_perm__read2', FALSE );
			}
			elseif ( $forum->password )
			{
				Member::loggedIn()->language()->words['perm_forum_perm__read'] = Member::loggedIn()->language()->addToStack( 'perm_forum_perm__read_pass', FALSE );
			}
			
		}
		catch ( OutOfRangeException $e ) {}
		
		parent::permissions();
	}

	/**
	 * Form to add/edit a forum
	 *
	 * @return void
	 */
	protected function form() : void
	{
		parent::form();

		if ( Request::i()->id )
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('edit_forum') . ': ' . Output::i()->title;
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('add_forum');
		}
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete(): void
	{
		/* Load forum and verify that it is not used for comments */
		/** @var Forum $nodeClass */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}

		try
		{
			$node = $nodeClass::load( Request::i()->id );
		}
		catch (OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S101/J', 404, '' );
		}

		/* Is any downloads category synced with this forum? */
		if ( $dbCategory = $node->isUsedByADownloadsCategory() )
		{
			Member::loggedIn()->language()->words['downloads_forum_used'] = sprintf( Member::loggedIn()->language()->get('downloads_forum_used'), $dbCategory->_title );
			Output::i()->error( 'downloads_forum_used', '1D372/1', 403, '' );
		}

		parent::delete();
	}
}