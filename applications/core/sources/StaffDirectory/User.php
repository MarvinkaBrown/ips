<?php
/**
 * @brief		Staff Directory User Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Sep 2013
 */

namespace IPS\core\StaffDirectory;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Translatable;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Request;
use IPS\Settings;
use LogicException;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Staff Directory User Node
 */
class User extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_leaders';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'leader_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'leader_type_id' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static string $parentNodeColumnId = 'group_id';
	
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static string $parentNodeClass = 'IPS\core\StaffDirectory\Group';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'staff_directory';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'staff',
		'prefix'	=> 'leaders_',
	);
	
	/**
	 * Get staff users in a group
	 *
	 * @param	int|null		$group	[Optional] Group to return users from
	 * @return	array
	 */
	public static function staff( ?int $group=NULL ) : array
	{
		if( $group === NULL )
		{
			return static::roots();
		}

		$users	= array();

		foreach ( static::roots() as $user )
		{
			if( $user->group_id === $group )
			{
				$users[]	= $user;
			}
		}
				
		return $users;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Radio( 'leader_type', $this->type ?: 'm', TRUE, array(
			'options' 	=> array( 'm' => 'leader_type_member', 'g' => 'leader_type_group' ),
			'toggles'	=> array(
				'm'			=> array( 'leader_id_member', 'leader_name_toggle', 'leader_title_toggle', 'leader_custom_bio_id' ),
				'g'			=> array( 'leader_id_group' )
			),
		) ) );

		$form->add( new Select( 'leader_id_group', $this->type === 'g' ? $this->type_id : NULL, FALSE, array( 'options' => Group::groups( TRUE, FALSE ), 'parse' => 'normal' ), NULL, NULL, NULL, 'leader_id_group' ) );
		$form->add( new FormMember( 'leader_id_member', ( $this->type === 'm' AND !$this->_new ) ? Member::load( $this->type_id )->name : NULL, FALSE, array(), function( $val )
		{
			if ( !$val and Request::i()->leader_type === 'm' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'leader_id_member' ) );
		$form->add( new Radio( 'leader_use_custom_name', ( Member::loggedIn()->language()->checkKeyExists("core_staff_directory_name_{$this->id}") ) ? 1 : 0, FALSE, array( 'options' => array( 0 => 'leader_custom_name_default', 1 => 'leader_custom_name_custom' ), 'toggles' => array( 1 => array( 'leader_custom_name' ) ) ), NULL, NULL, NULL, 'leader_name_toggle' ) );
		$form->add( new Translatable( 'leader_custom_name', NULL, FALSE, array( 'app' => 'core', 'key' => ( $this->id ? "core_staff_directory_name_{$this->id}" : NULL ) ), NULL, NULL, NULL, 'leader_custom_name' ) );
		$form->add( new Translatable( 'leader_custom_title', NULL, FALSE, array( 'app' => 'core', 'key' => ( $this->id ? "core_staff_directory_title_{$this->id}" : NULL ) ), NULL, NULL, NULL, 'leader_custom_title' ) );
		$form->add( new Translatable( 'leader_custom_bio', NULL, FALSE, array(
			'app'			=> 'core',
			'key'			=> ( $this->id ) ? "core_staff_directory_bio_{$this->id}" : NULL,
			'editor'		=> array(
				'app'			=> 'core',
				'key'			=> 'Staffdirectory',
				'autoSaveKey'	=> ( $this->id ) ? "leader-{$this->id}" : 'leader-new',
				'attachIds'		=> ( $this->id ) ? array( $this->id, NULL, NULL ) : NULL
			),
		), NULL, NULL, NULL, 'leader_custom_bio_id' ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( isset( $values['leader_type'] ) )
		{
			try
			{
				if( Db::i()->select( '*', 'core_leaders', array( 'leader_type=? AND leader_type_id=?', $values['leader_type'], $values['leader_type'] === 'm' ? $values['leader_id_member']->member_id : $values['leader_id_group'] ) )->first() )
				{
					if( !$this->id OR ( $this->id AND $this->type != $values['leader_type'] OR $this->type_id != ( $values['leader_type'] === 'm' ? $values['leader_id_member']->member_id : $values['leader_id_group'] ) ) )
					{
						throw new LogicException( Member::loggedIn()->language()->addToStack("only_one_leader") );
					}
				}
			}
			catch( UnderflowException $e ){}
		}

		if( isset( $values['leader_type'] ) AND ( isset( $values['leader_id_member'] ) OR $values['leader_id_group'] ) )
		{
			$values['type_id']			= $values['leader_type'] === 'm' ? $values['leader_id_member']->member_id : $values['leader_id_group'];
			unset( $values['leader_id_group'], $values['leader_id_member'] );
			$this->type		= $values['leader_type'];
			$this->type_id	= $values['type_id'];
		}
		
		/* Is this a new entry? */
		if ( !$this->id )
		{
			$this->save();
			File::claimAttachments( 'leader-new', $this->id, NULL, NULL, TRUE );

			$values['position']			= ( Db::i()->select( 'MAX(leader_position)', 'core_leaders' )->first() + 1 );
		}
		else
		{
			File::claimAttachments( "leader-{$this->id}", $this->id, NULL, NULL, TRUE );
		}

		$toUnset	= array();

		if ( isset( $values['leader_use_custom_name'] ) )
		{
			$toUnset[]	= 'leader_use_custom_name';
			$toUnset[]	= 'leader_custom_name';

			Lang::deleteCustom( 'core', "core_staff_directory_name_{$this->id}" );
		}

		if( isset( $values['leader_custom_name'] ) AND isset( $values['leader_use_custom_name'] ) AND $values['leader_use_custom_name'] )
		{
			Lang::saveCustom( 'core', "core_staff_directory_name_{$this->id}", $values['leader_custom_name'] );
		}

		if( isset( $values['leader_custom_title'] ) )
		{
			Lang::saveCustom( 'core', "core_staff_directory_title_{$this->id}", $values['leader_custom_title'] );
			unset( $values['leader_custom_title'] );
		}

		if( array_key_exists( 'leader_custom_bio', $values ) )
		{
			$toUnset[]	= 'leader_custom_bio';
			if ( isset ( $values['leader_custom_bio'] ) )
			{
				Lang::saveCustom( 'core', "core_staff_directory_bio_{$this->id}", $values['leader_custom_bio'] );
			}
		}

		if( count( $toUnset ) )
		{
			foreach( $toUnset as $_key )
			{
				unset( $values[ $_key ] );
			}
		}

		return $values;
	}
	
	/**
	 * [Node] Get Node Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		if ( !$this->id )
		{
			return '';
		}
		
		if ( $this->type === 'm' )
		{
			if( Member::loggedIn()->language()->checkKeyExists("core_staff_directory_name_{$this->id}") and Member::loggedIn()->language()->get("core_staff_directory_name_{$this->id}") )
			{
				return Member::loggedIn()->language()->addToStack( "core_staff_directory_name_{$this->id}", FALSE, array( 'escape' => TRUE ) );
			}

			$member = Member::load( $this->type_id );

			if( $member->member_id )
			{
				return $member->name;
			}

			return Member::loggedIn()->language()->addToStack('deleted_member');
		}
		else
		{
			try
			{
				return Group::load( $this->type_id )->name;
			}
			catch( OutOfRangeException $e )
			{
				return Member::loggedIn()->language()->addToStack('deleted_group');
			}
		}
	}
	
	/**
	 * [Node] Get Node Icon
	 *
	 * @return	string
	 */
	protected function get__icon(): mixed
	{
		return $this->type === 'm' ? 'fa-user' : 'fa-group';
	}
	
	/**
	 * @brief	Member
	 */
	public ?Member $member = null;

	/**
	 * Get member data for user
	 *
	 * @return	Member
	 */
	public function member() : Member
	{
		if ( $this->member === NULL )
		{
			$this->member =  Member::load( $this->type_id );
		}
		
		return $this->member;
	}

	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canAdd(): bool
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy(): bool
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions(): bool
	{
		return FALSE;
	}
	
	/**
	 * Delete
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		File::unclaimAttachments( 'core_Staffdirectory', $this->id );
		parent::delete();
		static::updateEmptySetting();
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();
		static::updateEmptySetting( FALSE );
	}
	
	/**
	 * Check if there are any records and update setting so we can hide the link if there is nothing
	 *
	 * @param	bool|NULL	$value	If we already know the value (because we've just set it), will save a query
	 * @return	void
	 */
	public static function updateEmptySetting( ?bool $value = NULL ) : void
	{
		if ( $value == NULL )
		{
			$value = !( Db::i()->select( 'COUNT(*)', 'core_leaders', NULL, NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first() );
		}
		
		Settings::i()->changeValues( array( 'staff_directory_empty' => $value ) );
	}
}