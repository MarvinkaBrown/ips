<?php
/**
 * @brief		ACP Member Profile: Groups Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\core\MemberACPProfile\Block;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Enum;
use IPS\Helpers\Form\Select;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Groups Block
 */
class Groups extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$secondaryGroups = array();
		foreach ( array_filter( array_map( "intval", explode( ',', $this->member->mgroup_others ) ) ) as $secondaryGroupId )
		{
			try
			{
				$secondaryGroups[] = Group::load( $secondaryGroupId );
			}
			catch ( OutOfRangeException $e ) { }
		}
		
		return (string) Theme::i()->getTemplate('memberprofile')->groups( $this->member, $secondaryGroups );
	}
	
	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		/* Check permission */
		if ( $this->member->isAdmin() )
		{
			Dispatcher::i()->checkAcpPermission( 'member_move_admin1' );
		}
		
		/* If we are editing ourselves, we can only move ourselves into a group with the same restrictions as what we have now... */
		if ( $this->member->member_id == Member::loggedIn()->member_id )
		{
			/* Get the row... */
			try
			{
				$currentRestrictions = Db::i()->select( 'row_perm_cache', 'core_admin_permission_rows', array( 'row_id=? AND row_id_type=?', $this->member->member_group_id, 'group' ) )->first();
				$availableGroups = array();
				foreach( Db::i()->select( 'row_id', 'core_admin_permission_rows', array( 'row_perm_cache=? AND row_id_type=?', $currentRestrictions, 'group' ) ) AS $groupId )
				{
					$availableGroups[ $groupId ] = Group::load( $groupId );
				}
			}
			/* If we don't have a row in core_admin_permission_rows, we're an admin as a member rather than apart of our group, so we can be moved anywhere and it won't matter because member-level restrictions override group-level */
			catch ( UnderflowException $e )
			{
				$availableGroups = Group::groups( TRUE, FALSE );
			}
		}
		/* Not editing ourselves - do we have the Can move members into admin groups"" restriction? */
		else
		{
			$availableGroups = Group::groups( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin2' ), FALSE );
		}
		
		/* Build form */
		$form = new Form( 'group_form', 'save', NULL, array(
			'data-controller'   => 'core.admin.members.form',
			'data-adminGroups' => json_encode( iterator_to_array( Db::i()->select( 'row_id', 'core_admin_permission_rows', array( 'row_id_type=?', 'group' ) ) ) )
		)) ;
		$form->add( new Select( 'group', $this->member->member_group_id, TRUE, array( 'options' => $availableGroups, 'parse' => 'normal' ) ) );
		$form->add( new Enum( 'secondary_groups', array_filter( explode( ',', $this->member->mgroup_others ) ), FALSE, array( 'options' => Group::groups( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin2' ), FALSE ), 'parse' => 'normal' ), function( $val ) {
			/* There is a limit to the amount of groups that can be stored in mgroup_others. For now, limit how many can be selected and in the future change to a mapping table instead. */
			if ( count( $val ) > 50 )
			{
				throw new DomainException( 'too_many_groups' );
			}
		} ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$oldPrimary = $this->member->member_group_id;
			$oldSecondary = array_filter( explode( ',', $this->member->mgroup_others ) );
			
			$changes = array();
			if ( $this->member->member_group_id != $values['group'] )
			{
				$this->member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'manual', 'old' => $this->member->member_group_id, 'new' => $values['group'] ) );
			}
			$currentSecondary = array_filter( explode( ',', $this->member->mgroup_others ) );
			if ( array_diff( $currentSecondary, $values['secondary_groups'] ) or array_diff( $values['secondary_groups'], $currentSecondary ) )
			{
				$this->member->logHistory( 'core', 'group', array( 'type' => 'secondary', 'by' => 'manual', 'old' => $currentSecondary, 'new' => $values['secondary_groups'] ) );
			}
						
			$this->member->member_group_id = $values['group'];
			$this->member->mgroup_others = implode( ',', $values['secondary_groups'] );
			$this->member->save();
			
			Session::i()->log( 'acplog__members_edited_groups', array( $this->member->name => FALSE ) );
						
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view&id={$this->member->member_id}" ), 'saved' );
		}
		
		/* Display */
		return $form;
	}
}