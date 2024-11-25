<?php
/**
 * @brief		Moderator Control Panel Extension: Content Pending Approva
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Dec 2013
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\core\Approval;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\ModCpAbstract;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Club;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use IPS\IPS;
use Exception;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Pending Approval
 */
class Unapproved extends ModCpAbstract
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string
	 */
	public function getTab() : string
	{
		return 'approval';
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
	 * Execute
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		if ( isset( Request::i()->modaction ) AND in_array( Request::i()->modaction, array( 'hide', 'approve', 'delete' ) ) )
		{
			$this->modaction();
		}
		
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('modcp_approval') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('modcp_approval');
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/clubs.css', 'core', 'front' ) );

		$types = $this->_getContentTypes();
		$table = new \IPS\Helpers\Table\Db( 'core_approval_queue', Url::internal( "app=core&module=modcp&controller=modcp&tab=approval", "front", "modcp_approval" ), $this->_buildWhere( $types ) );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'modcp' ), 'approvalQueueTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'modcp' ), 'approvalQueueRows' );
		$table->title = 'modcp_approval';
		$table->limit = 5;
		$table->sortBy = 'approval_id';
		$table->sortDirection = 'asc';

		foreach( $types as $key => $class )
		{
			$classesToCheck = [ $class ];
			if( isset( $class::$commentClass ) )
			{
				$classesToCheck[] = $class::$commentClass;
			}
			if( isset( $class::$reviewClass ) )
			{
				$classesToCheck[] = $class::$reviewClass;
			}
			$table->filters[ $class::$title . '_pl' ] = array( Db::i()->in( 'approval_content_class', $classesToCheck ) );
		}

		$resortKey = $table->resortKey;
		if( Request::i()->isAjax() AND isset( Request::i()->$resortKey ) )
		{
			Output::i()->sendOutput( (string) $table );
		}
		else
		{
			Output::i()->output = (string) $table;
		}
	}

	/**
	 * @return void
	 */
	public function getCount() : void
	{
		Output::i()->json( array( 'count' => $this->getApprovalQueueCount( true ) ) );
	}

	/**
	 * Get the approval queue count
	 *
	 * @param	bool	$bypassCache	Ignore cache and refetch the count
	 * @return	int
	 */
	public function getApprovalQueueCount( bool $bypassCache = FALSE ) : int
	{
		try
		{
			if( $bypassCache === TRUE )
			{
				throw new OutOfRangeException;
			}

			$approvalQueueCount = Cache::i()->getWithExpire( 'modCpApprovalQueueCount_' . Member::loggedIn()->member_id, TRUE );
		}
		catch ( OutOfRangeException )
		{
			$approvalQueueCount = (int) Db::i()->select( 'count(approval_id)', 'core_approval_queue', $this->_buildWhere( $this->_getContentTypes() ) )->first();

			Cache::i()->storeWithExpire( 'modCpApprovalQueueCount_' . Member::loggedIn()->member_id, $approvalQueueCount, DateTime::create()->add( new DateInterval( 'PT5M' ) ), TRUE );
		}

		return $approvalQueueCount;
	}

	/**
	 * Get hidden content types
	 *
	 * @return	array
	 */
	protected function _getContentTypes(): array
	{
		$types = array();
		foreach ( \IPS\Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			if ( IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
			{
				if ( Member::loggedIn()->modPermission( 'can_view_hidden_content' ) or Member::loggedIn()->modPermission( 'can_view_hidden_' . $class::$title ) )
				{
					$types[ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ] = $class;
				}
			}
		}

		/* Remove pending file versions */
		if( isset( $types['downloads_file_pendingversion'] ) )
		{
			unset( $types['downloads_file_pendingversion'] );
		}

		/* Add clubs */
		if ( Settings::i()->clubs and Settings::i()->clubs_require_approval and Member::loggedIn()->modPermission('can_access_all_clubs') )
		{
			$types[ 'core_clubs' ] = Club::class;
		}

		return $types;
	}

	/**
	 * Build the where clause to filter by content
	 *
	 * @param array	$types
	 * @return array
	 */
	protected function _buildWhere( array $types ) : array
	{
		$clause = [];
		$binds = [];
		foreach( $types as $class )
		{
			$allowedContainers = null;
			$classesToUse = [ str_replace( '\\', '\\\\', $class ) ];
			if ( isset( $class::$containerNodeClass ) )
			{
				$containerClass = $class::$containerNodeClass;
				if ( isset( $containerClass::$modPerm ) )
				{
					$allowedContainers = Member::loggedIn()->modPermission( $containerClass::$modPerm );
				}
			}

			if( isset( $class::$commentClass ) AND IPS::classUsesTrait( $class::$commentClass, 'IPS\Content\Hideable' ) )
			{
				$classesToUse[] = str_replace( '\\', '\\\\', $class::$commentClass );
			}
			if( isset( $class::$reviewClass ) AND IPS::classUsesTrait( $class::$reviewClass, 'IPS\Content\Hideable' ) )
			{
				$classesToUse[] = str_replace( '\\', '\\\\', $class::$reviewClass );
			}

			if( $allowedContainers !== null AND $allowedContainers !== -1 AND $allowedContainers !== true )
			{
				/* It could be an array or empty. If it's empty we have no permission to this class at all */
				if( is_array( $allowedContainers ) )
				{
					$clause[] = "(approval_content_class in ('" . implode( "','", $classesToUse ) . "') AND approval_container_id IN(?))";
					$binds[] = implode( ",", $allowedContainers );
				}
			}
			else
			{
				$clause[] = "approval_content_class in ('" . implode( "','", $classesToUse ) . "')";
			}
		}

		return array(
			array_merge( array( "(" . implode( " OR ", $clause ) . ")" ), $binds )
		);
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

		$ids = array_keys( Request::i()->moderate );
		$rows = iterator_to_array(
			new ActiveRecordIterator(
				Db::i()->select( '*', 'core_approval_queue', Db::i()->in( 'approval_id', $ids ) ),
				Approval::class
			)
		);

		foreach( $rows as $row )
		{
			if( $item = $row->item() )
			{
				if( $item instanceof Club )
				{
					switch( Request::i()->modaction )
					{
						case 'approve':
							$item->approved = true;
							$item->save();
							$item->onApprove();
							break;

						case 'delete':
							$item->delete();
							break;
					}
				}
				elseif( $item instanceof \IPS\Content )
				{
					$item->modAction( Request::i()->modaction == 'approve' ? 'unhide' : Request::i()->modaction );
				}
			}
		}

		Output::i()->redirect( Url::internal( "app=core&module=modcp&controller=modcp&tab=approval", 'front', 'modcp_approval' ), 'saved' );
	}
}