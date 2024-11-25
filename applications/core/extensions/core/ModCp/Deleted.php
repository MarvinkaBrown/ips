<?php
/**
 * @brief		Moderator Control Panel Extension: Deleted
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		15 Feb 2017
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content;
use IPS\core\DeletionLog\Table;
use IPS\Db;
use IPS\Extensions\ModCpAbstract;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_SELECT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Moderator Control Panel Extension: Deleted
 */
class Deleted extends ModCpAbstract
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string|null
	 */
	public function getTab(): ?string
	{
		if ( ! Member::loggedIn()->modPermission( 'can_manage_deleted_content' ) )
		{
			return null;
		}
		
		return 'deleted';
	}

	/**
	 * What do I manage?
	 * Acceptable responses are: content, members, or other
	 *
	 * @return	string
	 */
	public function manageType() : string
	{
		return 'content';
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage(): void
	{
		if ( isset( Request::i()->modaction ) AND in_array( Request::i()->modaction, array( 'restore', 'restore_as_hidden', 'delete' ) ) )
		{
			$this->modaction();
		}
		
		/* Content Types to filter on */
		$contentOptions = array();
		$contentOptions['all'] = 'all';
		foreach( Content::routedClasses() AS $class )
		{
			if ( IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
			{
				$contentOptions[ $class ] = $class::$title;
			}
		}
		
		$table					= new Table( Url::internal( "app=core&module=modcp&controller=modcp&tab=deleted", 'front', 'modcp_deleted' ) );
		$table->sortOptions		= array( 'dellog_deleted_date', 'dellog_deleted_by' );
		$table->advancedSearch	= array(
			'dellog_content_class'	=> array( SEARCH_SELECT, array( 'options' => $contentOptions ) ),
			'dellog_deleted_by'		=> SEARCH_MEMBER
		);
		$table->tableTemplate	= array( Theme::i()->getTemplate( 'modcp', 'core', 'front' ), 'deletedTable' );
		$table->rowsTemplate	= array( Theme::i()->getTemplate( 'modcp', 'core', 'front' ), 'deletedRows' );
		
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'modcp_deleted' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'modcp_deleted' );
		Output::i()->output = Theme::i()->getTemplate( 'modcp' )->deletedContent( $table );
	}

	/**
	 * Mod Action
	 *
	 * @return    void
	 * @throws Exception
	 */
	public function modaction(): void
	{
		Session::i()->csrfCheck();

		$ids = array();
		foreach( Request::i()->moderate AS $id => $status )
		{
			$ids[] = $id;
		}
		
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_deletion_log', array( Db::i()->in( 'dellog_id', $ids ) ) ), 'IPS\core\DeletionLog' ) AS $log )
		{
			$class = $log->content_class;

			try
			{
				$content = $class::load( $log->content_id );
			}
			catch ( OutOfRangeException $e )
			{
				/* Content may have already been removed by a linked item. e.g. db records deleting topics */
				continue;
			}

			if ( $log->canView() )
			{
				switch( Request::i()->modaction )
				{
					case 'restore':
						Session::i()->modLog( 'modlog__action_restore', array(
							$content::$title				=> FALSE,
							$content->url()->__toString()	=> FALSE
						) );

						$content->modAction( 'restore' );
						break;

					case 'restore_as_hidden':
						Session::i()->modLog( 'modlog__action_restore_hidden', array(
							$content::$title				=> FALSE,
							$content->url()->__toString()	=> FALSE
						) );

						$content->modAction( 'restoreAsHidden' );
						break;

					case 'delete':
						Session::i()->modLog( 'modlog__action_delete_perm', array(
							$content::$title				=> FALSE,
							$content->url()->__toString()	=> FALSE
						) );

						$content->delete();
						$log->delete();
						break;
				}
			}
		}
		
		Output::i()->redirect( Url::internal( "app=core&module=modcp&controller=modcp&tab=deleted", 'front', 'modcp_deleted' ), 'saved' );
	}
}