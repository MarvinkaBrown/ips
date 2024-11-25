<?php
/**
 * @brief		Report Types
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Dec 2017
 */

namespace IPS\core\Reports;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Translatable;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use function count;
use function defined;
use function get_class;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Report types node model
 */
class Types extends Model
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_automatic_moderation_types';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'type_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'automaticmod_types';
	
	/**
	 * @brief	[Node] Sortable
	 */
	public static bool $nodeSortable = TRUE;
	
	/**
	 * @brief	[Node] Positon Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'automaticmod_types_';


	/**
	 * Get the completed notification text (hits the DB to get the real value, not the hash key)
	 * @param Member|null $member
	 * @return string
	 */
	public function getCompletedNotificationText( \IPS\Member|null $member=null ): string
	{
		$member = $member ?: \IPS\Member::loggedIn();
		return $member->language()->get( 'automaticmod_types_notify_complete_' . $this->id, 'core' );
	}

	/**
	 * Get the rejected notification text (hits the DB to get the real value, not the hash key)
	 * @param Member|null $member
	 * @return string
	 */
	public function getRejectedNotificationText( \IPS\Member|null $member=null ): string
	{
		$member = $member ?: \IPS\Member::loggedIn();
		return $member->language()->get( 'automaticmod_types_notify_reject_' . $this->id, 'core' );
	}

	/**
	 * Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Translatable( 'automaticmod_types_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? 'automaticmod_types_' . $this->id : NULL ) ) ) );
		$form->add( new Translatable( 'automaticmod_types_notify_complete', NULL, FALSE, array( 'textArea' => true, 'app' => 'core', 'key' => ( $this->id ? 'automaticmod_types_notify_complete_' . $this->id : NULL ) ) ) );
		$form->add( new Translatable( 'automaticmod_types_notify_reject', NULL, FALSE, array( 'textArea' => true, 'app' => 'core', 'key' => ( $this->id ? 'automaticmod_types_notify_reject_' . $this->id : NULL ) ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( !$this->id )
		{
			$this->save();
		}
		
		/* Save the title */
		Lang::saveCustom( 'core', 'automaticmod_types_' . $this->id, $values['automaticmod_types_title'] );

		/* Save the notification text */
		Lang::saveCustom( 'core', 'automaticmod_types_notify_complete_' . $this->id, $values['automaticmod_types_notify_complete'] );
		Lang::saveCustom( 'core', 'automaticmod_types_notify_reject_' . $this->id, $values['automaticmod_types_notify_reject'] );
		unset( $values['automaticmod_types_title'], $values['automaticmod_types_notify_complete'], $values['automaticmod_types_notify_reject'] );
		
		return parent::formatFormValues( $values );
	}

	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @param	array|NULL			$limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	array
	 */
	public static function roots( ?string $permissionCheck='view', Member $member=NULL, mixed $where=array(), array $limit=NULL ): array
	{
		if ( !count( $where ) )
		{
			$return = array();
			foreach( static::getStore() AS $node )
			{
				$return[ $node['type_id'] ] = static::constructFromData( $node );
			}
			
			return $return;
		}
		else
		{
			return parent::roots( $permissionCheck, $member, $where, $limit );
		}
	}
	
	/**
	 * Get data store
	 *
	 * @return	array
	 * @note	Note that all records are returned, even disabled report type rules. Enable status needs to be checked in userland code when appropriate.
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->automatic_moderation_types ) )
		{
			Store::i()->automatic_moderation_types = iterator_to_array( Db::i()->select( '*', static::$databaseTable, NULL, "type_position ASC" )->setKeyField( 'type_id' ) );
		}
		
		return Store::i()->automatic_moderation_types;
	}

	/**
	 * @brief	[ActiveRecord] Attempt to load from cache
	 * @note	If this is set to TRUE you MUST define a getStore() method to return the objects from cache
	 */
	protected static bool $loadFromCache = TRUE;
	
	/**
	 * Get item count
	 * I'm sure you could have figured that out from the method name
	 * but I'll spoon feed you, it's ok.
	 *
	 * @return int
	 */
	public function getItemCount() : int
	{
		return intval( Db::i()->select( 'COUNT(*)', 'core_rc_reports', array( 'report_type=?', $this->id ) )->first() );
	}
	
	/**
	 * Form to delete or move content
	 *
	 * @param	bool	$showMoveToChildren	If TRUE, will show "move to children" even if there are no children
	 * @return	Form
	 */
	public function deleteOrMoveForm( bool $showMoveToChildren=FALSE ): Form
	{
		$form = new Form( 'delete_node_form', 'delete' );
		$form->addMessage( 'node_delete_blurb' );
	
		if ( $this->getItemCount() )
		{
			$form->add( new Node( 'node_move_content', 0, TRUE, array( 'class' => get_class( $this ), 'disabled' => array( $this->_id ), 'disabledLang' => 'node_move_delete', 'zeroVal' => 'node_delete_content', 'subnodes' => FALSE, 'permissionCheck' => function()
			{
				return true;
			} ) ) );
		}

		return $form;
	}
	
		/**
	 * Handle submissions of form to delete or move content
	 *
	 * @param	array	$values			Values from form
	 * @return	void
	 */
	public function deleteOrMoveFormSubmit( array $values ) : void
	{
		if ( isset( $values['node_move_content'] ) and $values['node_move_content'] )
		{
			/* We're moving first */
			Db::i()->update( 'core_rc_reports', array( 'report_type' => $values['node_move_content']->_id ), array( 'report_type=?', $this->id ) );
		}
		
		$this->delete();
	}

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'automatic_moderation_types' );
}