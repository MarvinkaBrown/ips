<?php
/**
 * @brief		moderators
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Apr 2013
 */

namespace IPS\core\modules\admin\staff;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content\ModeratorPermissions;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * moderators
 */
class moderators extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'moderators_manage' );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'members/restrictions.css', 'core', 'admin' ) );

		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_moderators', Url::internal( 'app=core&module=staff&controller=moderators' ) );

		/* Columns */
		$table->langPrefix	= 'moderators_';
		$table->selects		= array( 'perms', 'id', 'type', 'updated' );
		$table->joins = array(
			array( 'select' => "IF(core_moderators.type= 'g', w.word_custom, m.name) as name", 'from' => array( 'core_members', 'm' ), 'where' => "m.member_id=core_moderators.id AND core_moderators.type='m'" ),
			array( 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'core_group_', core_moderators.id ) AND core_moderators.type='g' AND w.lang_id=" . Member::loggedIn()->language()->id )
		);
		$table->include = array( 'name', 'updated', 'perms' );
		$table->parsers = array(
			'name'		=> function( $val, $row )
			{
				$return	= Theme::i()->getTemplate( 'global' )->shortMessage( $row['type'] === 'g' ? 'group' : 'member', array( 'ipsBadge', 'ipsBadge--neutral', 'ipsBadge--label' ) );
				try
				{
					$name = empty( $row['name'] ) ? Member::loggedIn()->language()->addToStack('deleted_member') : htmlentities( $row['name'], ENT_DISALLOWED, 'UTF-8', FALSE );
					$return	.= ( $row['type'] === 'g' ) ? Group::load( $row['id'] )->formattedName : $name;
				}
				catch( OutOfRangeException $e )
				{
					$return .= Member::loggedIn()->language()->addToStack('deleted_group');
				}
				return $return;
			},
			'updated'	=> function( $val )
			{
				return ( $val ) ? DateTime::ts( $val )->localeDate() : Member::loggedIn()->language()->addToStack('never');
			},
			'perms' => function( $val )
			{
				return Theme::i()->getTemplate( 'members' )->restrictionsLabel( $val );
			}
		);
		$table->mainColumn = 'name';
		$table->quickSearch = array( array( 'name', 'word_custom' ), 'name' );
		$table->noSort = array( 'perms' );
		
		/* Sorting */
		$table->sortBy = $table->sortBy ?: 'updated';
		$table->sortDirection = $table->sortDirection ?: 'desc';

		/* Buttons */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) or Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_group' ) )
		{
			Output::i()->sidebar['actions'] = array(
				'add'	=> array(
					'primary' => TRUE,
					'icon'	=> 'plus',
					'link'	=> Url::internal( 'app=core&module=staff&controller=moderators&do=add' ),
					'title'	=> 'add_moderator',
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add_moderator') )
				),
			);
		}
		$table->rowButtons = function( $row )
		{
			$buttons = array(
				'edit'	=> array(
					'icon'	=> 'pencil',
					'link'	=> Url::internal( "app=core&module=staff&controller=moderators&do=edit&id={$row['id']}&type={$row['type']}" ),
					'title'	=> 'edit',
					'class'	=> '',
				),
				'delete'	=> array(
					'icon'	=> 'times-circle',
					'link'	=> Url::internal( "app=core&module=staff&controller=moderators&do=delete&id={$row['id']}&type={$row['type']}" ),
					'title'	=> 'delete',
					'data'	=> array( 'delete' => '' ),
				)
			);
			
			if ( $row['type'] === 'm' )
			{
				if ( !Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_edit_member' ) )
				{
					unset( $buttons['edit'] );
				}
				if ( !Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_delete_member' ) )
				{
					unset( $buttons['delete'] );
				}
			}
			else
			{
				if ( !Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_edit_group' ) )
				{
					unset( $buttons['edit'] );
				}
				if ( !Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_delete_group' ) )
				{
					unset( $buttons['delete'] );
				}
			}

			return $buttons;
		};
		
		/* Buttons for logs */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'restrictions_moderatorlogs' ) )
		{
			Output::i()->sidebar['actions']['actionLogs'] = array(
					'title'		=> 'modlogs',
					'icon'		=> 'search',
					'link'		=> Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogs' ),
			);
		}

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('moderators');
		Output::i()->output	= (string) $table;
	}
	
	/**
	 * Add
	 *
	 * @return	void
	 */
	protected function add() : void
	{
		$form = new Form();
				
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) and Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_group' ) )
		{
			$form->add( new Radio( 'moderators_type', NULL, TRUE, array( 'options' => array( 'g' => 'group', 'm' => 'member' ), 'toggles' => array( 'g' => array( 'moderators_group' ), 'm' => array( 'moderators_member' ) ) ) ) );
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_group' ) )
		{
			$form->add( new Select( 'moderators_group', NULL, FALSE, array( 'options' => Group::groups( TRUE, FALSE ), 'parse' => 'normal' ), NULL, NULL, NULL, 'moderators_group' ) );
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) )
		{
			$form->add( new FormMember( 'moderators_member', NULL, ( Request::i()->moderators_type === 'member' ), array(), NULL, NULL, NULL, 'moderators_member' ) );
		}
		
		if ( $values = $form->values() )
		{		
			$rowId = NULL;

			if( !isset( $values['moderators_type'] ) )
			{
				$values['moderators_type'] = isset( $values['moderators_group'] ) ? 'g' : 'm';
			}
			
			if ( $values['moderators_type'] === 'g' or !Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) )
			{
				Dispatcher::i()->checkAcpPermission( 'moderators_add_group' );
				$rowId = $values['moderators_group'];
			}
			elseif ( $values['moderators_member'] )
			{
				Dispatcher::i()->checkAcpPermission( 'moderators_add_member' );
				$rowId = $values['moderators_member']->member_id;
			}

			if ( $rowId !== NULL )
			{
				try
				{
					$current = Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", $rowId, $values['moderators_type'] ) )->first();
				}
				catch( UnderflowException $e )
				{
					$current	= array();
				}

				if ( !count( $current ) )
				{
					$current = array(
						'id'		=> $rowId,
						'type'		=> $values['moderators_type'],
						'perms'		=> '*',
						'updated'	=> time()
					);
					
					Db::i()->insert( 'core_moderators', $current );
					
					foreach ( Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
					{
						$ext->onChange( $current, $values );
					}

					if( $values['moderators_type'] == 'g' )
					{
						$logValue = array( 'core_group_' . $values['moderators_group'] => TRUE );
					}
					else
					{
						$logValue = array( $values['moderators_member']->name => FALSE );
					}

					Session::i()->log( 'acplog__moderator_created', $logValue );

					unset (Store::i()->moderators);
				}

				Output::i()->redirect( Url::internal( "app=core&module=staff&controller=moderators" ) );
			}
		}

		Output::i()->title	 = Member::loggedIn()->language()->addToStack('add_moderator');
		Output::i()->output = Theme::i()->getTemplate('global')->block( 'add_moderator', $form, FALSE );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		try
		{
			$current = Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", intval( Request::i()->id ), Request::i()->type ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2C118/2', 404, '' );
		}

		/* Check acp restrictions */
		if ( $current['type'] === 'm' )
		{
			Dispatcher::i()->checkAcpPermission( 'moderators_edit_member' );
		}
		else
		{
			Dispatcher::i()->checkAcpPermission( 'moderators_edit_group' );
		}

		/* Load */
		try
		{
			$_name = ( $current['type'] === 'm' ) ? Member::load( $current['id'] )->name : Group::load( $current['id'] )->name;
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C118/2', 404, '' );
		}

		$currentPermissions = ( $current['perms'] === '*' ) ? '*' : ( $current['perms'] ? json_decode( $current['perms'], TRUE ) : array() );
				
		/* Define content field toggles */
		$toggles = array( 'view_future' => array(), 'future_publish' => array(), 'pin' => array(), 'unpin' => array(), 'feature' => array(), 'unfeature' => array(), 'edit' => array(), 'hide' => array(), 'unhide' => array(), 'view_hidden' => array(), 'move' => array(), 'lock' => array(), 'unlock' => array(), 'reply_to_locked' => array(), 'assign' => array(), 'delete' => array(), 'split_merge' => array(), 'feature_comments' => array(), 'unfeature_comments' => array(), 'add_item_message' => array(), 'edit_item_message' => array(), 'delete_item_message' => array(), 'toggle_item_moderation' => array(), 'view_reports' => array() );
		foreach ( Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
		{
			if ( $ext instanceof ModeratorPermissions )
			{
				foreach ( $ext->actions as $s )
				{
					$class = $ext::$class;
					$toggles[ $s ][] = "can_{$s}_{$class::$title}";
				}
				
				if ( isset( $class::$commentClass ) )
				{
					foreach ( $ext->commentActions as $s )
					{
						$commentClass = $class::$commentClass;
						$toggles[ $s ][] = "can_{$s}_{$commentClass::$title}";
					}
				}
				
				if ( isset( $class::$reviewClass ) )
				{
					foreach ( $ext->reviewActions as $s )
					{
						$reviewClass = $class::$reviewClass;
						$toggles[ $s ][] = "can_{$s}_{$reviewClass::$title}";
					}
				}
			}
		}

		/* We need to remember which keys are 'nodes' so we can adjust values upon submit */
		$nodeFields = array();
		
		/* Build */
		$form = new Form;

		/* Add the restricted/unrestricted option first */
		$form->add(
			new Radio( 'mod_use_restrictions', ( $currentPermissions === '*' ) ? 'no' : 'yes', TRUE, array( 'options' => array( 'no' => 'mod_all_permissions', 'yes' => 'mod_restricted' ), 'toggles' => array( 'yes' => array( 'permission_form_wrapper' ) ) ), NULL, NULL, NULL, 'use_restrictions_id' )
		);
		
		$form->add( new YesNo( 'mod_show_badge', ( $current ) ? $current['show_badge'] : TRUE, TRUE ) );
		
		$extensions = array();
		foreach ( Application::allExtensions( 'core', 'ModeratorPermissions', FALSE, 'core' ) as $k => $ext )
		{
			$extensions[ $k ] = $ext;
		}
		
		if ( isset( $extensions['core_General'] ) )
		{
			$meFirst = array( 'core_General' => $extensions['core_General'] );
			unset( $extensions['core_General'] );
			$extensions = $meFirst + $extensions;
		}
		
		foreach( $extensions as $k => $ext )
		{
			$form->addTab( 'modperms__' . $k );
						
			foreach ( $ext->getPermissions( $toggles ) as $name => $data )
			{
				/* Class */
				$type = is_array( $data ) ? $data[0] : $data;
				$class = '\IPS\Helpers\Form\\' . ( $type );

				/* Remember 'nodes' */
				if( $type == 'Node' )
				{
					$nodeFields[ $name ]	= $name;
				}
				
				/* Current Value */
				if ( $currentPermissions === '*' )
				{
					switch ( $type )
					{
						case 'YesNo':
							$currentValue = TRUE;
							break;
							
						case 'Number':
							$currentValue = -1;
							break;
						
						case 'Node':
							$currentValue = 0;
							break;
					}
				}
				else
				{
					$currentValue = ( $currentPermissions[$name] ?? NULL );

					/* We translate nodes to -1 so the moderator permissions merging works as expected allowing "all" to override individual node selections */
					if( $type == 'Node' AND $currentValue == -1 )
					{
						$currentValue = 0;
					}
				}
				
				/* Options */
				$options = is_array( $data ) ? $data[1] : array();
				if ( $type === 'Number' )
				{
					$options['unlimited'] = -1;
				}
				
				/* Prefix/Suffix */
				$prefix = NULL;
				$suffix = NULL;
				if ( is_array( $data ) )
				{
					if ( isset( $data[2] ) )
					{
						$prefix = $data[2];
					}
					if ( isset( $data[3] ) )
					{
						$suffix = $data[3];
					}
				}
				
				/* Add */
				$form->add( new $class( $name, $currentValue, FALSE, $options, NULL, $prefix, $suffix, $name ) );
			}
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Allow extensions an opportunity to inspect the values and make adjustments */
			foreach ( Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
			{
				if( method_exists( $ext, 'preSave' ) )
				{
					$ext->preSave( $values );
				}
			}

			if( $values['mod_use_restrictions'] == 'no' )
			{
				$permissions = '*';

				$changed = '*';
			}
			else
			{
				unset( $values['mod_use_restrictions'] );

				foreach ( $values as $k => $v )
				{
					/* For node fields, if the value is 0 translate it to -1 so mod permissions can merge properly */
					if( in_array( $k, $nodeFields ) )
					{
						/* If nothing is checked we have '', but if 'all' is checked then the value is 0 */
						if( $v === 0 )
						{
							$v = -1;
							$values[ $k ] = $v;
						}
					}

					if ( is_array( $v ) )
					{
						foreach ( $v as $l => $w )
						{
							if ( $w instanceof Model )
							{
								$values[ $k ][ $l ] = $w->_id;
							}
						}
					}
				}
				
				if ( $currentPermissions == '*' )
				{
					$changed = $values;
				}
				else
				{
					$changed = array();
					foreach ( $values as $k => $v )
					{
						if ( !isset( $currentPermissions[ $k ] ) or $currentPermissions[ $k ] != $v )
						{
							$changed[ $k ] = $v;
						}
					}
				}

				$permissions = json_encode( $values );
			}
			
			Db::i()->update( 'core_moderators', array( 'perms' => $permissions, 'updated' => time(), 'show_badge' => $values['mod_show_badge'] ), array( array( "id=? AND type=?", $current['id'], $current['type'] ) ) );

			if( !( $currentPermissions == '*' AND $changed == '*' ) )
			{
				foreach ( Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
				{
					$ext->onChange( $current, $changed );
				}
			}

			if( $current['type'] == 'g' )
			{
				$logValue = array( 'core_group_' . $current['id'] => TRUE );
			}
			else
			{
				$logValue = array( Member::load( $current['id'] )->name => FALSE );
			}

			Session::i()->log( 'acplog__moderator_edited', $logValue );

			$currentPermissions = $values;

			unset( Store::i()->moderators );

			Output::i()->redirect( Url::internal( 'app=core&module=staff&controller=moderators' ), 'saved' );
		}

		/* Display */
		Output::i()->title		= $_name;
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_members.js', 'core', 'admin' ) );
		Output::i()->output 	.= $form->customTemplate( array( Theme::i()->getTemplate( 'members' ), 'moderatorPermissions' ) );
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		/* Load */
		try
		{
			$current = Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", intval( Request::i()->id ), Request::i()->type ) )->first();
		}
		catch( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2C118/4', 404, '' );
		}

		/* Check acp restrictions */
		if ( $current['type'] === 'm' )
		{
			Dispatcher::i()->checkAcpPermission( 'moderators_delete_member' );
		}
		else
		{
			Dispatcher::i()->checkAcpPermission( 'moderators_delete_group' );
		}

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		/* Delete */
		Db::i()->delete( 'core_moderators', array( array( "id=? AND type=?", $current['id'], $current['type'] ) ) );
		foreach ( Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
		{
			$ext->onDelete( $current );
		}

		unset (Store::i()->moderators);
		
		/* Log and redirect */
		if( $current['type'] == 'g' )
		{
			try
			{
				$name = 'core_group_' . $current['id'];
			}
			catch( OutOfRangeException $e )
			{
				$name = 'deleted_group';
			}

			$logValue = array( $name => TRUE );
		}
		else
		{
			$member = Member::load( $current['id'] );

			if( $member->member_id )
			{
				$logValue = array( $member->name => FALSE );
			}
			else
			{
				$logValue = array( 'deleted_member' => TRUE );
			}
		}

		Session::i()->log( 'acplog__moderator_deleted', $logValue );

		Output::i()->redirect( Url::internal( 'app=core&module=staff&controller=moderators' ) );
	}
	
	/**
	 * Action Logs
	 *
	 * @return	void
	 */
	protected function actionLogs() : void
	{
		Dispatcher::i()->checkAcpPermission( 'restrictions_moderatorlogs' );
	
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_moderator_logs', Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogs' ) );
		$table->langPrefix = 'modlogs_';
		$table->include = array( 'member_id', 'action', 'ip_address', 'ctime' );
		$table->mainColumn = 'action';
		$table->parsers = array(
				'member_id'	=> function( $val, $row )
				{
					$member = Member::load( $val );
					if ( $member->member_id )
					{
						return htmlentities( Member::load( $val )->name, ENT_DISALLOWED, 'UTF-8', FALSE );
					}
					else if ( $row['member_name'] != '' )
					{
						return htmlentities( $row['member_name'], ENT_DISALLOWED, 'UTF-8', FALSE );
					}
					else
					{
						// Member doesn't exist anymore, but we also haven't stored the name
						return '';
					}
				},
				'action'	=> function( $val, $row )
				{
					if ( $row['lang_key'] )
					{
						$langKey = $row['lang_key'];
						$params = array();
                        $note = json_decode( $row['note'], TRUE );
                        if ( !empty( $note ) )
                        {
                            foreach ($note as $k => $v)
                            {
                                $params[] = $v ? Member::loggedIn()->language()->addToStack($k) : $k;
                            }
                        }
						return Member::loggedIn()->language()->addToStack( $langKey, FALSE, array( 'sprintf' => $params ) );
					}
					else
					{
						return $row['note'];
					}
				},
				'ip_address'=> function( $val )
				{
					if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) )
					{
						return "<a href='" . Url::internal( "app=core&module=members&controller=ip&ip={$val}" ) . "'>{$val}</a>";
					}
					return $val;
				},
				'ctime'		=> function( $val )
				{
					return (string) DateTime::ts( $val );
				}
		);
		$table->sortBy = $table->sortBy ?: 'ctime';
		$table->sortDirection = $table->sortDirection ?: 'desc';
	
		/* Search */
		$table->advancedSearch	= array(
				'member_id'			=> SEARCH_MEMBER,
				'ip_address'		=> SEARCH_CONTAINS_TEXT,
				'ctime'				=> SEARCH_DATE_RANGE
		);

		/* Custom quick search function to search unicode entities in JSON encoded data */
		$table->quickSearch = function( $val )
		{
			$searchTerm = mb_strtolower( trim( Request::i()->quicksearch ) );
			$jsonSearchTerm = str_replace( '\\', '\\\\\\', trim( json_encode( trim( Request::i()->quicksearch ) ), '"' ) );

			return array(
				"(`note` LIKE CONCAT( '%', ?, '%' ) OR LOWER(`word_custom`) LIKE CONCAT( '%', ?, '%' ) OR LOWER(`word_default`) LIKE CONCAT( '%', ?, '%' ))",
				$jsonSearchTerm,
				$searchTerm,
				$searchTerm
			);
		};

		$table->joins = array(
			array( 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=lang_key AND w.lang_id=" . Member::loggedIn()->language()->id )
		);

		/* Add a button for settings */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'restrictions_moderatorlogs_prune' ) )
		{
			Output::i()->sidebar['actions'] = array(
					'settings'	=> array(
							'title'		=> 'prunesettings',
							'icon'		=> 'cog',
							'link'		=> Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogSettings' ),
							'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('prunesettings') )
					),
			);
		}
	
		/* Display */
		Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogs' ), Member::loggedIn()->language()->addToStack( 'modlogs' ) );
		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'modlogs' );
		Output::i()->output	= (string) $table;
	}
	
	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function actionLogSettings() : void
	{
		Dispatcher::i()->checkAcpPermission( 'restrictions_moderatorlogs_prune' );
	
		$form = new Form;
		$form->add( new Interval( 'prune_log_moderator', Settings::i()->prune_log_moderator, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_log_moderator' ) );
	
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__moderatorlog_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogs' ), 'saved' );
		}
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('moderatorlogssettings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'moderatorlogssettings', $form, FALSE );
	}
}
