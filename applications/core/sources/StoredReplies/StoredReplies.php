<?php
/**
 * @brief		Stored Replies Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		03 September 2021
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Node\Model;
use IPS\Node\Permissions;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stored Replies Node
 */
class StoredReplies extends Model implements Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_editor_stored_replies';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'editor_stored_replies';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databasePrefix = 'reply_';

	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'editor',
		'all'	=> 'stored_replies_manage'
	);

	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'core';

	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'editorStoredReplies';

	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
		'view' => 'view'
	);

	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'perm_editor_stored_reply_';

	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static ?string $databaseColumnEnabledDisabled = 'enabled';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'editor_stored_replies_';
		
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled(): ?bool
	{
		return $this->enabled;
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( bool|int $enabled ) : void
	{
		$this->enabled	= $enabled;
	}

	/**
	 * [Node] Get Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		return $this->title;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Text( 'editor_stored_replies_title', $this->id ? $this->title : NULL, TRUE ) );
		$form->add( new YesNo( 'editor_stored_replies_enabled', $this->id ? $this->enabled : TRUE, FALSE, array( 'togglesOn' => array( 'editor_stored_replies_content' ) ) ) );
		$form->add( new Editor( 'editor_stored_replies_content', $this->id ? $this->text : FALSE, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => ( $this->id ? "core-editor-replies-{$this->id}" : "core-editor-replies" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'editor_stored_replies' ) : NULL ), NULL, NULL, NULL, 'editor_stored_replies_content' ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		foreach( [
			'editor_stored_replies_title' => 'title',
			'editor_stored_replies_enabled' => 'enabled',
			'editor_stored_replies_content' => 'text'
			] as $input => $name )
		{
			$values[ $name ] = $values[ $input ];
			unset( $values[ $input ] );
		}

		$values['added_by'] = Member::loggedIn()->loggedIn()->member_id;

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		/* Looks a bit weird, but as this is postSave, $this->id is filled and $this->_new is false, _permissions is always null if it's a new entry */
		File::claimAttachments( ( $this->_permissions === null ) ? "core-editor-replies" : "core-editor-replies-{$this->id}", $this->id, NULL, 'editor_stored_replies' );
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		File::unclaimAttachments( 'core_Admin', $this->id, NULL, 'editor_stored_replies' );
		parent::delete();
	}

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'editorStoredReplies' );

	/**
	 * Get data store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->editorStoredReplies ) )
		{
			/* Don't get the reply text as this could make for a large store [but, dear future developer it might be fine to include it if you really have a need I didn't forsee]
			   Oh and we grab permissions here so we don't need to do a full query each time the editor is loaded */
			Store::i()->editorStoredReplies = iterator_to_array(
				Db::i()->select(
					'reply_id, reply_title, reply_added_by, reply_enabled, core_permission_index.perm_id, core_permission_index.perm_view',
					static::$databaseTable
				)->join(
					'core_permission_index',
					array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType )
				)->setKeyField('reply_id')
			);
		}
		
		return Store::i()->editorStoredReplies;
	}

	/**
	 * Set the permission index permissions
	 *
	 * @param	array	$insert	Permission data to insert
	 * @return  void
	 */
	public function setPermissions( array $insert ) : void
	{
		parent::setPermissions( $insert );

		/* Clear cache */
		unset( Store::i()->editorStoredReplies );
	}

	/**
	 * Check whether any replies exist that are enabled (for the logged in member)
	 *
	 * @return bool
	 */
	public static function enabledRepliesExist() : bool
	{
		foreach ( static::roots() as $root )
		{
			if ( $root->_enabled )
			{
				return true;
			}
		}

		return false;
	}
}