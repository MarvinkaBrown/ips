<?php
/**
 * @brief		Group Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Mar 2013
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ArrayIterator;
use DateInterval;
use InvalidArgumentException;
use IPS\Application;
use IPS\Application\Module;
use IPS\Content\Search\Index;
use IPS\core\StaffDirectory\User;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Lang;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Widget;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function str_replace;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group Model
 */
class Group extends ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_groups';
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'g_id';
	
	/**
	 * @brief	Bitwise keys
	 */
	protected static array $bitOptions = array(
		'g_bitoptions'	=> array(
			'g_bitoptions'	=> array(
				'gbw_mod_post_unit_type'	=> 1, 			// Lift moderation after x. 1 is days, 0 is posts. Corresponds to g_mod_post_unit
				'gbw_ppd_unit_type'			=> 2, 			// Lift post-per-day limit after x. 1 is days, 0 is posts. Corresponds to g_ppd_unit
				'gbw_displayname_unit_type'	=> 4, 			// Username change restrictions. 1 is days, 0 is posts. Corresponds to g_displayname_unit
				'gbw_sig_unit_type'			=> 8, 			// Signature edit restrictions. 1 is days, 0 is posts. Corresponds to g_sig_unit
				'gbw_promote_unit_type'		=> 16, 			// 16 is deprecated (previously gbw_promote_unit_type). Remove in 4.1.19.
				// 32 is deprecated (previously gbw_no_status_update)
				// 64 is deprecated (previously gbw_soft_delete)
				// 128 is deprecated (previously gbw_soft_delete_own)
				// 256 is deprecated (previously gbw_soft_delete_own_topic)
				// 512 is deprecated (previously gbw_un_soft_delete)
				// 1024 is deprecated (previously gbw_soft_delete_see)
				// 2048 is deprecated (previously gbw_soft_delete_topic)
				// 4096 is deprecated (previously gbw_un_soft_delete_topic)
				// 8192 is deprecated (previously gbw_soft_delete_topic_see)
				// 16384 is deprecated (previously gbw_soft_delete_reason)
				// 32768 is deprecated (previously gbw_soft_delete_see_post)
				// 65536 is deprecated (previously gbw_allow_customization)
				// 131072 is deprecated (previously gbw_allow_url_bgimage)
				'gbw_allow_upload_bgimage'	=> 262144, 		// Can upload a cover photo?
				'gbw_view_reps'				=> 524288, 		// Can view who gave reputation?
				'gbw_no_status_import'		=> 1048576, 	// Can NOT import status updates from Facebook/Twitter
				'gbw_disable_tagging'		=> 2097152, 	// Can NOT use tags
				'gbw_disable_prefixes'		=> 4194304, 	// Can NOT use prefixes
				// 8388608 is deprecated (previously gbw_view_last_info)
				// 16777216 is deprecated (previously gbw_view_online_lists)
				// 33554432 is deprecated (previously gbw_hide_leaders_page)
				'gbw_pm_unblockable'		=> 67108864,	// Deprecated in favour of global unblockable setting
				'gbw_pm_override_inbox_full'=> 134217728,	// 1 means this group can send other members PMs even when that member's inbox is full
				// 268435456 is deprecated (previously gbw_no_report)
				'gbw_cannot_be_ignored'		=> 536870912,	// 1 means they cannot be ignored. 0 means they can
				'gbw_delete_attachments'	=> 1073741824,	// 1 means they can delete attachments from the "My Attachments" screen
			),
			'g_bitoptions2'	=> array(
				'gbw_post_highlight'	=> 1,	// 1 means on, 0 means off
				'gbw_hide_group'		=> 2,	// Hide this group on the front end in search and similar areas: 1 is on, 0 is off
				// 4 is deprecated (previously gbw_lock_unlock_own)
				// 8 is deprecated (gbw_promote)
				'gbw_immune_auto_mod'   => 16,	// Users in this group can not be auto moderated
				'gbw_paid_clubs'		=> 32,	// Users in this group can create paid clubs
				'gbw_can_post_anonymously'	=> 64, // Users in this group can post anonymously (where allowed)
				'gbw_hide_inline_modevents'	=> 128, // Users in this group can see inline moderation events (where supported)
				'gbw_posted_in'				=> 256, // Items (topics, articles) will be marked as 'posted in by this group'
				'gbw_posted_in_secondary'	=> 512, // Items (topics, articles) will be marked as 'posted in by this group' when the group is set as a secondary group
				'gbw_club_manage_indexing'	=> 1024, // Users in this group can manage indexing of clubs
				'gbw_view_helpful'			=> 2048, // Users in this group can view who found content helpful
				'gbw_change_layouts'		=> 4096, // Users in this group can change the layouts of key areas set by themes
			)
		)
	);
		
	/**
	 * @brief	[ActiveRecord] Attempt to load from cache
	 * @note	If this is set to TRUE you MUST define a getStore() method to return the objects from cache
	 */
	protected static bool $loadFromCache = TRUE;

	/**
	 * Group datastore
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->groups ) )
		{
			Store::i()->groups = iterator_to_array( Db::i()->select( '*', 'core_groups' )->setKeyField( 'g_id' ) );
		}

		return Store::i()->groups;
	}
	
	/**
	 * @brief	Stored Groups
	 */
	protected static ?array $allGroups = NULL;

	/**
	 * Get groups
	 *
	 * @param bool $showAdminGroups Show admin groups. Used to restrict admin groups from being available when you cannot add/edit members in them.
	 * @param bool $showGuestGroups Show guest groups. Used to remove the guest group from the available groups returned.
	 * @param bool $hideForFiltering If the group is set not to not be available to filter by, should we hide them?
	 * @return array|null
	 */
	public static function groups( bool $showAdminGroups=TRUE, bool $showGuestGroups=TRUE, bool $hideForFiltering=FALSE ): ?array
	{
		if ( !static::$allGroups )
		{
			static::$allGroups = iterator_to_array( new ActiveRecordIterator( new ArrayIterator( static::getStore() ), 'IPS\Member\Group' ) );

			/* If we have a dispatcher instance, sort the groups alphabetically */
			if( Dispatcher::hasInstance() )
			{
				/* Get all of the group names in one query */
				$sortedGroupIds = array();

				foreach( Db::i()->select( '*', 'core_sys_lang_words', array( 'word_key LIKE ? AND lang_id=?', 'core_group_%', Member::loggedIn()->language()->id ), 'word_custom ASC' ) as $lang )
				{
					$sortedGroupIds[] = str_replace( 'core_group_', '', $lang['word_key'] );
				}

				$finalGroups = array();

				foreach( $sortedGroupIds as $groupId )
				{
					if( isset( static::$allGroups[ $groupId ] ) )
					{
						$finalGroups[ $groupId ] = static::$allGroups[ $groupId ];
					}
				}

				static::$allGroups = $finalGroups;
			}
		}

		$groups = static::$allGroups;

		if ( !$showGuestGroups )
		{
			unset( $groups[ Settings::i()->guest_group ] );
		}

		if( !$showAdminGroups )
		{
			$administrators = Member::administrators();
			foreach( $groups as $k => $_group )
			{
				if ( isset( $administrators['g'][ $_group->g_id ] ) )
				{
					unset( $groups[ $k ] );
				}
			}
		}

		if( $hideForFiltering )
		{
			foreach( $groups as $groupId => $group )
			{
				if( $group->g_bitoptions['gbw_hide_group'] )
				{
					unset( $groups[ $groupId ] );
				}
			}
		}

		return $groups;
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		$oldId = $this->g_id;
		$oldGroup = $this;
		parent::__clone();

		/* Rebuild permission indexes */
		$perms = array( 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7' );
		$where = array();
		foreach( $perms as $key )
		{
			$where[] = Db::i()->findInSet( $key, array($oldId) );
		}

		foreach( Db::i()->select( '*', 'core_permission_index', implode( ' OR ', $where ) ) as $index )
		{
			foreach( $perms as $key )
			{
				$groups = explode( ",", $index[$key] );
				if( in_array( $oldId, $groups ) and !in_array( $this->g_id, $groups ) )
				{
					$groups[] = $this->g_id;
				}

				$index[$key] = implode( ",", $groups );
			}
			Db::i()->update( 'core_permission_index', $index, array( 'perm_id = ?', $index['perm_id'] ) );
		}

		$extensions = Application::allExtensions( 'core', 'GroupForm', FALSE, 'core', 'GroupSettings', TRUE );
		/* Process each extension */
		foreach ( $extensions as $class )
		{
			if( method_exists( $class, 'cloneGroup' ) )
			{
				$class->cloneGroup( $oldGroup, $this );
			}
		}

		Lang::saveCustom( 'core', "core_group_{$this->g_id}", iterator_to_array( Db::i()->select( '*', 'core_sys_lang_words', array( 'word_key=?', "core_group_{$oldId}" ) )->setKeyField('lang_id')->setValueField('word_custom') ) );

		/* Clear application and module store so that permissions are correctly rebuilt */
		unset( Store::i()->applications );
		unset( Store::i()->modules );
		unset( Store::i()->groups );

		/* Rebuild search index */
		Index::i()->rebuild();
	}
	
	/**
	 * Get data
	 *
	 * @return	array
	 */
	public function data(): array
	{
		return $this->_data;
	}
	
	/**
	 * Magic Method: To String
	 * Returns group name
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->name;
	}
	
	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function get_name(): string
	{
		return Member::loggedIn()->language()->addToStack( "core_group_{$this->g_id}" );
	}
	
	/**
	 * Get formatted name
	 *
	 * @return	string
	 */
	public function get_formattedName(): string
	{
		return $this->formatName( $this->name );
	}

	/**
	 * Get number of members in this group
	 *
	 * @note	Includes count of members with this group as a secondary group
	 * @note	The count is cached for 10 minutes as the query can be resource intensive
	 * @return	int
	 */
	public function getCount(): int
	{
		$key = 'groupMembersCount_' . $this->g_id;

		try
		{
			$count = Store::i()->$key;

			if( $values = json_decode( $count, true ) )
			{
				if( $values['expire'] < time() )
				{
					throw new OutOfRangeException;
				}

				return $values['count'];
			}
			else
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			$count = Db::i()->select( 'COUNT(*)',  'core_members', array( 'member_group_id=? OR FIND_IN_SET( ?, mgroup_others )', $this->g_id, $this->g_id ) )->first();

			Store::i()->$key = json_encode( array( 'count' => $count, 'expire' => DateTime::create()->add( new DateInterval( 'PT10M' ) )->getTimestamp() ) );
		}

		return (int) $count;
	}
	
	/**
	 * Format Name
	 *
	 * @param	string	$name	The name to format
	 * @return	string
	 */
	public function formatName( string $name ): string
	{
		return ( $this->prefix . htmlspecialchars( $name, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) . $this->suffix );
	}

	/**
	 * Can use module
	 *
	 * @param	Module	$module	The module to test
	 * @return	bool
	 */
	public function canAccessModule( Module $module ): bool
	{
		return $module->can( 'view', $this );
	}

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'groups', 'moderators', 'administrators' );

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		if ( in_array( $this->g_id, array( Settings::i()->guest_group, Settings::i()->member_group, Settings::i()->admin_group ) ) )
		{
			throw new InvalidArgumentException;
		}

		/* remove group from mod & staff section */

		Db::i()->delete( 'core_leaders', array( 'leader_type=? AND leader_type_id = ?', 'g', $this->g_id ) );
		Db::i()->delete( 'core_moderators', array( 'type=? AND id = ?', 'g', $this->g_id ) );
		Db::i()->delete( 'core_admin_permission_rows', array( 'row_id=? and row_id_type=?', $this->g_id, 'group' ) );

		/* Make sure no other groups have this group ID set to promote to */
		foreach(static::groups() as $group )
		{
			$promote = explode( '&', $group->g_promotion );

			if( $promote[0] == $this->g_id )
			{
				$group->g_promotion = '-1&' . $promote[1];
				$group->save();
			}
		}

		$extensions = Application::allExtensions( 'core', 'GroupForm', FALSE, 'core', 'GroupSettings', TRUE );
		/* Process each extension */
		foreach ( $extensions as $class )
		{
			if( method_exists( $class, 'delete' ) )
			{
				$class->delete( $this );
			}
		}
		
		parent::delete();
		Lang::deleteCustom( 'core', 'core_group_' . $this->g_id );

		/* Update Core Leaders */
		User::updateEmptySetting();

		/* Deleted group, expire widget caches (online stats may try to load a group) */
		Widget::deleteCaches();
	}

	/**
	 * Can this group be deleted?
	 *
	 * @return bool
	 */
	public function canDelete() : bool
	{
		$extensions = Application::allExtensions( 'core', 'GroupForm', FALSE, 'core', 'GroupSettings', TRUE );

		foreach ( $extensions as $class )
		{
			if( method_exists( $class, 'canDelete' ) AND !$class->canDelete( $this ) )
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Return group that will show the posted in indicator
	 *
	 * @param array	$primary	Primary Group IDs
	 * @param array	$secondary	Array of Secondary Group IDs
	 * @return array|FALSE
	 */
	public static function postedIn( array $primary, array $secondary ): array|bool
	{
		if ( !static::$allGroups )
		{
			static::groups();
		}
		$return = array();

		foreach( array( 'primary', 'secondary' ) as $type )
		{
			if ( is_array( $$type ) and count( $$type ) )
			{
				foreach ( $$type as $id )
				{
					if ( isset( static::$allGroups[$id] ) and static::$allGroups[$id]->g_bitoptions[ ( $type == 'primary' ? 'gbw_posted_in' : 'gbw_posted_in_secondary') ] )
					{
						$return[ $id ] = static::$allGroups[$id];
					}
				}
			}
		}

		return ( count( $return ) ? $return : FALSE );
	}
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return		array
	 * @apiresponse	int			id				ID number
	 * @apiresponse	string		name			Name
	 * @apiresponse	string		formattedName	Name with formatting
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array(
			'id'				=> $this->g_id,
			'name'				=> $this->name,
			'formattedName'		=> $this->formattedName,
		);
	}

	/**
	 * Get the restriction level this group has. This will be an integer of 0, 1 or 2, which correspond to minimal, standard and advanced
	 *
	 * @param bool $comments
	 *
	 * @return int
	 */
	public function getEditorRestrictionLevel( bool $comments=false ) : int
	{
		$field = $comments ? "g_editor_comments_restriction" : "g_editor_restriction";
		if ( is_int( $this->$field ) )
		{
			return $this->$field;
		}

		$administrators = Member::administrators();
		if ( isset( $administrators['g'][$this->g_id] ) )
		{
			return 2; // the advanced editor
		}

		return $comments ? 0 : 1; // minimal for comments, standard otherwise
	}
}