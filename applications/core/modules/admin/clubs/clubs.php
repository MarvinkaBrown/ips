<?php
/**
 * @brief		Clubs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Feb 2017
 */

namespace IPS\core\modules\admin\clubs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_NUMERIC;
use const IPS\Helpers\Table\SEARCH_QUERY_TEXT;
use const IPS\Helpers\Table\SEARCH_SELECT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Clubs
 */
class clubs extends Controller
{	
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @var string|null
	 */
	protected ?string $nodeClass = null;

	/**
	 * @var Club|null
	 */
	protected ?Club $club = null;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'clubs_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{			
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_clubs_clubs');
		
		if ( Settings::i()->clubs )
		{			
			/* Create the table */
			$table = new TableDb( 'core_clubs', Url::internal( 'app=core&module=clubs&controller=clubs' ) );
			$table->include = array( 'name', 'type', 'members', 'owner', 'created' );
			$table->langPrefix = 'club_';
			$table->parsers = array(
				'name'	=> function( $value, $row )
				{
					return Theme::i()->getTemplate('clubs')->name( $value, $row );
				},
				'type'	=> function( $value ) {
					return Theme::i()->getTemplate('clubs')->privacy( $value );
				},
				'created'	=> function( $value ) {
					return DateTime::ts( $value );
				},
				'members'	=> function( $value, $row ) {
					if ( $row['type'] !== Club::TYPE_PUBLIC )
					{
						$link = Url::internal( "app=core&module=clubs&controller=view&id={$row['id']}&do=members", 'front', 'clubs_view', array( Friendly::seoTitle( $row['name'] ) ) );
						return Theme::i()->getTemplate('clubs')->members( $value, $link );
					}
					return '';
				},
				'owner'	=> function( $value ) {
					return Theme::i()->getTemplate('clubs')->owner( Member::load( $value ) );
				},
				'_highlight' => function( $row ) {
					if ( !$row['approved'] )
					{
						return 'ipsModerated';
					}
					return NULL;
				}
			);
			$table->noSort = array( 'privacy', 'owner' );
			$table->sortBy = $table->sortBy ?: 'members';
			$table->quickSearch = 'name';
			$table->advancedSearch = array(
				'name'	=> SEARCH_QUERY_TEXT,
				'members' => SEARCH_NUMERIC,
				'owner'	=> SEARCH_MEMBER,
				'created'	=> SEARCH_DATE_RANGE,
			);
			if ( Settings::i()->clubs_require_approval )
			{
				$table->filters = array(
					'pending_approval' => 'approved=0'
				);
				$table->advancedSearch['type'] = array( SEARCH_SELECT, array( 'options' => array(
					Club::TYPE_PUBLIC	=> 'club_type_' . Club::TYPE_PUBLIC,
					Club::TYPE_OPEN	=> 'club_type_' . Club::TYPE_OPEN,
					Club::TYPE_CLOSED	=> 'club_type_' . Club::TYPE_CLOSED,
					Club::TYPE_PRIVATE	=> 'club_type_' . Club::TYPE_PRIVATE,
					Club::TYPE_READONLY	=> 'club_type_' . Club::TYPE_READONLY,
				), 'multiple' => TRUE ) );
			}
			else
			{
				$table->filters = array(
					'club_type_' . Club::TYPE_PUBLIC	=> array( 'type=?', Club::TYPE_PUBLIC ),
					'club_type_' . Club::TYPE_OPEN		=> array( 'type=?', Club::TYPE_OPEN ),
					'club_type_' . Club::TYPE_CLOSED	=> array( 'type=?', Club::TYPE_CLOSED ),
					'club_type_' . Club::TYPE_PRIVATE	=> array( 'type=?', Club::TYPE_PRIVATE ),
					'club_type_' . Club::TYPE_READONLY	=> array( 'type=?', Club::TYPE_READONLY )
				);
			}
			$table->rowButtons = function( $row ) {
				$return = array();
				if ( !$row['approved'] )
				{
					if ( Member::loggedIn()->hasAcpRestriction( 'core', 'clubs', 'clubs_edit' ) )
					{
						$return['approve']	= array(
							'title'	=> 'approve',
							'icon'	=> 'check',
							'link'	=> Url::internal("app=core&module=clubs&controller=clubs&do=approve&id={$row['id']}")->csrf()
						);
					}
					if ( Member::loggedIn()->hasAcpRestriction( 'core', 'clubs', 'clubs_delete' ) )
					{
						$return['delete'] = array(
							'title'	=> 'delete',
							'icon'	=> 'times',
							'link'	=> Url::internal("app=core&module=clubs&controller=clubs&do=delete&id={$row['id']}"),
							'data'	=> Db::i()->select( 'COUNT(*)', 'core_clubs_node_map', array( 'club_id=?', $row['id'] ) )->first() ? array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('delete') ) : array( 'delete' => '' )
						);
					}
				}
				$return['open']	= array(
					'title'	=> 'view',
					'icon'	=> 'search',
					'link'	=> Url::internal( "app=core&module=clubs&controller=view&id={$row['id']}", 'front', 'clubs_view', array( Friendly::seoTitle( $row['name'] ) ) ),
					'target'=> '_blank'
				);
				if ( Member::loggedIn()->hasAcpRestriction( 'core', 'clubs', 'clubs_edit' ) )
				{
					$return['edit']	= array(
						'title'	=> 'edit',
						'icon'	=> 'pencil',
						'link'	=> Url::internal("app=core&module=clubs&controller=clubs&do=edit&id={$row['id']}")
					);
				}
				if ( !isset( $return['delete'] ) and Member::loggedIn()->hasAcpRestriction( 'core', 'clubs', 'clubs_delete' ) )
				{
					$return['delete'] = array(
						'title'	=> 'delete',
						'icon'	=> 'times-circle',
						'link'	=> Url::internal("app=core&module=clubs&controller=clubs&do=delete&id={$row['id']}"),
						'data'	=> Db::i()->select( 'COUNT(*)', 'core_clubs_node_map', array( 'club_id=?', $row['id'] ) )->first() ? array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('delete') ) : array( 'delete' => '' )
					);
				}
				return $return;
			};
				
			/* Display */
			Output::i()->output = (string) $table;
		}
		else
		{
			$availableTypes = array();
			foreach ( Club::availableNodeTypes( NULL ) as $class )
			{
				$availableTypes[] = Member::loggedIn()->language()->addToStack( $class::clubAcpTitle() );
			}
			
			$availableTypes = Member::loggedIn()->language()->formatList( $availableTypes );
			
			Output::i()->output = Theme::i()->getTemplate( 'clubs' )->disabled( $availableTypes );
		}
	}
	
	/**
	 * Enable
	 *
	 * @return	void
	 */
	protected function enable() : void
	{	
		Dispatcher::i()->checkAcpPermission( 'clubs_settings_manage' );
		Session::i()->csrfCheck();
		
		Settings::i()->changeValues( array( 'clubs' => true ) );

		Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'clubrebuild' ) );
		
		Session::i()->log( 'acplog__club_settings' );
		
		Output::i()->redirect( Url::internal('app=core&module=clubs&controller=clubs') );
	}
	
	/**
	 * Edit
	 *
	 * @csrfChecked	Uses node form() 7 Oct 2019
	 * @return	void
	 */
	protected function edit() : void
	{
		Dispatcher::i()->checkAcpPermission( 'clubs_edit' );
		
		/* Load Club */
		try
		{
			$club = Club::load( Request::i()->id );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2C352/1', 404, '' );
		}
		$editUrl = Url::internal("app=core&module=clubs&controller=clubs&do=edit&id={$club->id}");
		
		/* Tabs */
		$tabs = array( 'settings' => 'settings' );
		foreach ( Club::availableNodeTypes( Member::loggedIn() ) as $class )
		{
			$tabs[ str_replace( '\\', '-', preg_replace( '/^IPS\\\/', '', $class ) ) ] = $class::clubAcpTitle();
		}
		$activeTab = ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'settings';
		
		/* Settings */
		if ( $activeTab === 'settings' )
		{
			$form = $club->form( TRUE );

			if( $values = $form->values() )
			{
				$club->skipCloneDuplication = TRUE;
				$old = clone $club;

				$club->processForm( $values, TRUE, FALSE, NULL );

				$changes = $club::renewalChanges( $old, $club );

				if ( !empty( $changes ) )
				{
					Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'global' )->decision( 'product_change_blurb', array(
						'product_change_blurb_existing'	=> Url::internal( "app=core&module=clubs&controller=clubs&do=updateExisting&id={$club->id}" )->setQueryString( 'changes', json_encode( $changes ) )->csrf(),
						'product_change_blurb_new'		=> Url::internal( "app=core&module=clubs&controller=clubs" ),
					) );

					return;
				}
				else
				{
					Output::i()->redirect( Url::internal("app=core&module=clubs&controller=clubs"), "saved" );
				}
			}
			else
			{
				$activeTabContents = (string) $form;
			}
		}
		
		/* Node List */
		else
		{
			$nodeClass = 'IPS\\' . str_replace( '-', '\\', Request::i()->tab );
			$this->nodeClass = $nodeClass;
			$this->club = $club;

			/* @var Model $nodeClass */
			$tree = new Tree( $editUrl->setQueryString( 'tab', Request::i()->tab ), 'x', array( $this, '_getNodeRows' ), array( $this, '_getNodeRow' ), function() { return NULL; }, function() { return array(); }, function() use( $nodeClass ) {
				if ( Member::loggedIn()->hasAcpRestriction( 'core', 'clubs', 'clubs_add_nodes' ) )
				{
					return array(
						'create'	=> array(
							'title'		=> 'add',
							'icon'		=> 'plus',
							'link'		=> Url::internal("app=core&module=clubs&controller=clubs&do=nodeForm&club={$this->club->id}&nodeClass={$nodeClass}" ),
							'data'	=> array(
								'ipsDialog'			=> '',
								'ipsDialog-title'	=> Member::loggedIn()->language()->addToStack( $nodeClass::clubAcpTitle() )
							)
						)
					);
				}
				return array();
			} );
			
			$activeTabContents = $tree;
		}
		
		/* Output */
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
		}
		else
		{
			Output::i()->title = $club->name;
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $activeTabContents, $editUrl );
		}
	}
	
	/**
	 * Approve
	 *
	 * @return	void
	 */
	protected function approve() : void
	{
		Dispatcher::i()->checkAcpPermission( 'clubs_edit' );
		Session::i()->csrfCheck();
		
		/* Load Club */
		try
		{
			$club = Club::load( Request::i()->id );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2C352/8', 404, '' );
		}
		
		/* Approve */
		$club->approved = TRUE;
		$club->save();
		
		$club->onApprove();
			
		/* Redirect */
		$target = Url::internal("app=core&module=clubs&controller=clubs");
		if ( Db::i()->select( 'COUNT(*)', 'core_clubs', array( 'approved=0 AND id!=?', $club->id ) )->first() )
		{
			$target = $target->setQueryString( 'filter', 'pending_approval' );
		}
		Session::i()->modLog( 'acplog__club_approved', array( $club->name => FALSE ) );
		Output::i()->redirect( $target, "approved" );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'clubs_delete' );
		
		/* Load Club */
		try
		{
			$club = Club::load( Request::i()->id );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2C352/7', 404, '' );
		}
		
		/* Ask what to do with each node */
		$nodes = $club->nodes();
		if ( count( $nodes ) )
		{
			$form = new Form( 'form', 'delete' );
			$form->addMessage( 'club_delete_blurb' );
			foreach ( $nodes as $data )
			{
				try
				{
					$field = new Node( 'node_' . str_replace( '\\', '-', preg_replace( '/^IPS\\\/', '', $data['node_class'] ) ) . '_' . $data['node_id'], 0, TRUE, array( 'class' => $data['node_class'], 'disabled' => array( $data['node_id'] ), 'disabledLang' => 'node_move_delete', 'zeroVal' => 'node_delete_content', 'subnodes' => FALSE, 'permissionCheck' => function( $node )
					{
						return array_key_exists( 'add', $node->permissionTypes() );
					}, 'clubs' => TRUE ) );
					$field->label = htmlspecialchars( $data['name'] );
					$form->add( $field );
				}
				catch ( Exception $e ) {}
			}
			if ( $values = $form->values() )
			{
				foreach ( $values as $k => $v )
				{
					$exploded = explode( '_', $k );
					$nodeClass = 'IPS\\' . str_replace( '-', '\\', $exploded[1] );
					
					try
					{
						/* @var Model $nodeClass */
						$node = $nodeClass::load( $exploded[2] );
						
						$nodesToQueue = array( $node );
						$nodeToCheck = $node;
						while( $nodeToCheck->hasChildren( NULL ) )
						{
							foreach ( $nodeToCheck->children( NULL ) as $nodeToCheck )
							{
								$nodesToQueue[] = $nodeToCheck;
							}
						}
						
						foreach ( $nodesToQueue as $_node )
						{
							$_values = array();

							if ( $v )
							{
								$_values['node_move_children'] = $v;
								$_values['node_move_content'] = $v;
							}

							$_node->deleteOrMoveFormSubmit( $_values );
						}
					}
					catch ( Exception $e ) {}
				}
			}
			else
			{
				Output::i()->output = $form;
				return;
			}
		}
		else
		{
			Request::i()->confirmedDelete();
		}
		
		/* Delete it */
		Session::i()->log( 'acplog__club_deleted', array( $club->name => FALSE ) );
		$club->delete();
		Db::i()->delete( 'core_clubs_memberships', array( 'club_id=?', $club->id ) );
		Db::i()->delete( 'core_clubs_node_map', array( 'club_id=?', $club->id ) );
		Db::i()->delete( 'core_clubs_fieldvalues', array( 'club_id=?', $club->id ) );

		/* Boink */
		if( Request::i()->isAjax() )
		{
			Output::i()->json( "OK" );
		}
		else
		{
			Output::i()->redirect( Url::internal('app=core&module=clubs&controller=clubs'), 'deleted' );
		}
	}
	
	/**
	 * Edit Node
	 *
	 * @return	void
	 */
	protected function nodeForm() : void
	{
		/* Load Club */
		try
		{
			$club = Club::load( Request::i()->club );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2C352/2', 404, '' );
		}
		
		/* Load Node */
		$nodeClass = Request::i()->nodeClass;
		if ( isset( Request::i()->nodeId ) )
		{
			Dispatcher::i()->checkAcpPermission( 'clubs_edit_nodes' );
			
			try
			{
				$node = $nodeClass::load( Request::i()->nodeId );
				$nodeClub = $node->club();
				if ( !$nodeClub or $nodeClub->id !== $club->id )
				{
					throw new Exception;
				}
			}
			catch ( Exception $e )
			{
				Output::i()->error( 'node_error', '2C352/3', 404, '' );
			}
		}
		else
		{
			Dispatcher::i()->checkAcpPermission( 'clubs_add_nodes' );
			$node = new $nodeClass;
		}
		
		/* Build Form */
		$form = new Form;
		$node->clubForm( $form, $club );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$node->saveClubForm( $club, $values );
			Session::i()->log( 'acplog__node_edited_club', array( $nodeClass::$nodeTitle => TRUE, $node->titleForLog() => FALSE, $club->name => FALSE ) );
			Output::i()->redirect( Url::internal( "app=core&module=clubs&controller=clubs&do=edit&id={$club->id}&tab=" . str_replace( '\\', '-', preg_replace( '/^IPS\\\/', '', $nodeClass ) ) ) );
		}
		
		/* Display */
		Output::i()->title = $node->_title;
		Output::i()->output = $form;
	}
	
	/**
	 * Delete Node
	 *
	 * @return	void
	 */
	protected function deleteNode() : void
	{
		Dispatcher::i()->checkAcpPermission( 'clubs_delete_nodes' );
		
		/* Load Club */
		try
		{
			$club = Club::load( Request::i()->club );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2C352/4', 404, '' );
		}
		
		/* Load Node */
		$nodeClass = Request::i()->nodeClass;
		try
		{
			$node = $nodeClass::load( Request::i()->nodeId );
			$nodeClub = $node->club();
			if ( !$nodeClub or $nodeClub->id !== $club->id )
			{
				throw new Exception;
			}
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2C352/5', 404, '' );
		}
		$targetUrl = Url::internal( "app=core&module=clubs&controller=clubs&do=edit&id={$club->id}&tab=" . str_replace( '\\', '-', preg_replace( '/^IPS\\\/', '', $nodeClass ) ) );
		
		/* Do we have any children or content? */
		if ( $node->hasChildren( NULL, NULL, TRUE ) or $node->showDeleteOrMoveForm() )
		{			
			$form = $node->deleteOrMoveForm( FALSE );
			if ( $values = $form->values() )
			{
				Db::i()->delete( 'core_clubs_node_map', array( 'club_id=? AND node_class=? AND node_id=?', $club->id, $nodeClass, $node->_id ) );
				$node->deleteOrMoveFormSubmit( $values );				
				Output::i()->redirect( $targetUrl );
			}
			else
			{
				/* Show form */
				Output::i()->output = $form;
				return;
			}
		}
		else
		{
			/* Make sure the user confirmed the deletion */
			Request::i()->confirmedDelete();
		}
		
		/* Delete it */
		Db::i()->delete( 'core_clubs_node_map', array( 'club_id=? AND node_class=? AND node_id=?', $club->id, $nodeClass, $node->_id ) );
		Session::i()->log( 'acplog__node_deleted_club', array( $nodeClass::$nodeTitle => TRUE, $node->titleForLog() => FALSE, $club->name => FALSE ) );
		$node->delete();

		/* Boink */
		if( Request::i()->isAjax() )
		{
			Output::i()->json( "OK" );
		}
		else
		{
			Output::i()->redirect( $targetUrl );
		}
	}
	
	/**
	 * Get Node Rows
	 *
	 * @return	array
	 */
	public function _getNodeRows() : array
	{
		/* @var Model $nodeClass */
		$nodeClass = $this->nodeClass;
		$rows = array();
		foreach( $nodeClass::roots( NULL, NULL, array( $nodeClass::$databasePrefix . $nodeClass::clubIdColumn() . '=?', $this->club->id ) ) as $node )
		{
			$rows[ $node->_id ] = $this->_getNodeRow( $node );
		}
		
		return $rows;
	}
	
	/**
	 * Get Node Row
	 *
	 * @param	mixed	$id		May be ID number (or key) or an \IPS\Node\Model object
	 * @param	bool	$root	Format this as the root node?
	 * @param	bool	$noSort	If TRUE, sort options will be disabled (used for search results)
	 * @return	string
	 */
	public function _getNodeRow( mixed $id, bool $root=FALSE, bool $noSort=FALSE ) : string
	{
		$nodeClass = $this->nodeClass;
		
		if ( $id instanceof Model )
		{
			$node = $id;
		}
		else
		{
			try
			{
				/* @var Model $nodeClass */
				$node = $nodeClass::load( $id );
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2C352/6', 404, '' );
			}
		}
		
		
		$buttons = array(
			'open'	=> array(
				'title'	=> 'view',
				'icon'	=> 'search',
				'link'	=> $node->url(),
				'target'=> '_blank',
			)
		);
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'clubs', 'clubs_edit_nodes' ) )
		{
			$buttons['edit'] = array(
				'title'	=> 'edit',
				'icon'	=> 'pencil',
				'link'	=> Url::internal("app=core&module=clubs&controller=clubs&do=nodeForm&club={$this->club->id}&nodeClass={$nodeClass}&nodeId={$node->_id}"),
				'data'	=> array(
					'ipsDialog'			=> '',
					'ipsDialog-title'	=> $node->_title
				)
			);
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'clubs', 'clubs_delete_nodes' ) )
		{
			$buttons['delete'] = array(
				'title'	=> 'delete',
				'icon'	=> 'times-circle',
				'link'	=> Url::internal("app=core&module=clubs&controller=clubs&do=deleteNode&club={$this->club->id}&nodeClass={$nodeClass}&nodeId={$node->_id}"),
				'data' 	=> ( $node->hasChildren( NULL, NULL, TRUE ) or $node->showDeleteOrMoveForm() ) ? array( 'ipsDialog' => '', 'ipsDialog-title' => $node->_title ) : array( 'delete' => '' ),
			);
		}
		
		return Theme::i()->getTemplate( 'trees', 'core' )->row(
			NULL,
			$node->_id,
			$node->_title,
			FALSE,
			$buttons,
			$node->description
		);
	}

	/**
	 * Update Existing Purchases
	 *
	 * @return	void
	 */
	public function updateExisting() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$club = Club::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_permission', '3C352/8', 403, '' );
		}

		$changes = json_decode( Request::i()->changes, TRUE );

		Task::queue( 'core', 'UpdateClubRenewals', array( 'changes' => $changes, 'club' => $club->id ), 5 );

		Output::i()->redirect( Url::internal( "app=core&module=clubs&controller=clubs" ), 'saved' );
	}
}