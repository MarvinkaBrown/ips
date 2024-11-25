<?php
/**
 * @brief		Share Link Services
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Jun 2013
 */

namespace IPS\core\modules\admin\promotion;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\ShareServices;
use IPS\core\ShareLinks\Service;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Node\Controller;
use IPS\Node\Model;
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
 * Share Link Services
 */
class sharelinks extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\core\ShareLinks\Service';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'sharelinks_manage' );

		$reloadRoots	= FALSE;

		/* First, see if any are missing */
		$nodeClass = $this->nodeClass;

		$shareServices = ShareServices::services();

		/* @var Model $nodeClass */
		foreach( $nodeClass::roots() as $node )
		{
			if( !isset( $shareServices[ ucwords( $node->key ) ] ) )
			{
				$node->delete();
				$reloadRoots	= TRUE;
			}
		}

		/* Now see if there are any new classes */
		foreach( ShareServices::services() as $id => $className )
		{
			try
			{
				$nodeClass::load( mb_strtolower( $id ), 'share_key' );
			}
			catch( OutOfRangeException $e )
			{
				/* Class does not exist - let's add it */
				$newService	= new Service;
				$newService->key		= mb_strtolower( $id );
				$newService->groups		= '*';
				$newService->title		= $id;
				$newService->enabled	= 0;
				$newService->save();

				$reloadRoots	= TRUE;
			}
		}

		if( $reloadRoots === TRUE )
		{
			$nodeClass::resetRootResult();
		}

		parent::execute();
	}

	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		return array();
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		Db::i()->delete( 'core_share_links', array( 'share_id=?', (int) Request::i()->id ) );

		Output::i()->redirect( Url::internal( 'app=core&module=promotion&controller=sharelinks' ), 'saved' );
	}
}