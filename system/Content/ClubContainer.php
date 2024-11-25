<?php
/**
 * @brief		Trait for Content Containers which can be used in Clubs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Feb 2017
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_called_class;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Trait for Content Containers which can be used in Clubs
 */
trait ClubContainer
{
	/**
	 * Get the database column which stores the club ID
	 *
	 * @return	string
	 */
	public static function clubIdColumn(): string
	{
		return 'club_id';
	}
	
	/**
	 * Get front-end language string
	 *
	 * @return	string
	 */
	public static function clubFrontTitle(): string
	{
		$itemClass = static::$contentItemClass;
		return $itemClass::$title . '_pl';
	}
	
	/**
	 * Get acp language string
	 *
	 * @return	string
	 */
	public static function clubAcpTitle(): string
	{
		return static::$nodeTitle;
	}

	/**
	 * Check if the node can be added as a club feature
	 * This was mainly implemented to handle the club categories,
	 * which are based on database settings.
	 *
	 * @return bool
	 */
	public static function canBeAddedToClub() : bool
	{
		return true;
	}
	
	/**
	 * Check if we need to re-acknowledge rules
	 *
	 * @return void
	 */
	public function clubCheckRules(): void
	{
		if ( $club = $this->club() AND !$club->rulesAcknowledged() AND !Member::loggedIn()->modPermission( 'can_access_all_clubs' ) )
		{
			Output::i()->redirect( $club->url()->setQueryString( 'do', 'rules' )->addRef( Request::i()->url() ) );
		}
	}
	
	/**
	 * Get the associated club
	 *
	 * @return	Club|NULL
	 */
	public function club(): Club|NULL
	{
		if ( Settings::i()->clubs )
		{
			$clubIdColumn = $this->clubIdColumn();
			if ( $this->$clubIdColumn )
			{
				try
				{
					return Club::load( $this->$clubIdColumn );
				}
				catch ( OutOfRangeException $e ) { }
			}
		}
		return NULL;
	}

	/**
	 * Set form for creating a node of this type in a club
	 *
	 * @param Form $form Form object
	 * @param Club $club
	 * @return    void
	 */
	public function clubForm( Form $form, Club $club ): void
	{
		$itemClass = static::$contentItemClass;
		$form->add( new Text( 'club_node_name', $this->_id ? $this->_title : Member::loggedIn()->language()->addToStack( static::clubFrontTitle() ), TRUE, array( 'maxLength' => 255 ) ) );
	}
	
	/**
	 * Save club form
	 *
	 * @param	Club	$club	The club
	 * @param	array				$values	Values
	 * @return	void
	 */
	public function saveClubForm( Club $club, array $values ): void
	{
		/* @var Model $nodeClass */
		$nodeClass = get_called_class();
		$itemClass = $nodeClass::$contentItemClass;

		$clubIdColumn = $this->clubIdColumn();
		$haveId = (bool) $this->_id;

		/* We want to make sure the club ID is set before we
		run the _saveClubForm method */
		if( !$haveId )
		{
			$this->$clubIdColumn = $club->id;
		}

		$this->_saveClubForm( $club, $values );

		$needToUpdatePermissions = FALSE;
		
		if ( !$haveId )
		{
			$this->save();

			Db::i()->insert( 'core_clubs_node_map', array(
				'club_id'		=> $club->id,
				'node_class'	=> $nodeClass,
				'node_id'		=> $this->_id,
				'name'			=> $values['club_node_name'],
				'public'		=> $values['club_node_public'] ?? 0
			) );
			
			$needToUpdatePermissions = TRUE;
		}
		else
		{
			if( isset( $values['club_node_public'] ) AND $values['club_node_public'] != $this->isPublic() )
			{
				$needToUpdatePermissions = TRUE;
			}
			
			$this->save();
			Db::i()->update( 'core_clubs_node_map', array( 'name' => $values['club_node_name'], 'public' => isset( $values['club_node_public'] ) ? (int) $values['club_node_public'] : 0), array( 'club_id=? AND node_class=? AND node_id=?', $club->id, $nodeClass, $this->_id ) );
		}
		
		Lang::saveCustom( $itemClass::$application, static::$titleLangPrefix . $this->_id, $values['club_node_name'] );
		Lang::saveCustom( $itemClass::$application, static::$titleLangPrefix . $this->_id . '_desc', $values['club_node_description'] ?? '' );
		
		if ( $needToUpdatePermissions )
		{
			$this->setPermissionsToClub( $club );
		}

		if ( !$haveId )
		{
			/* @var string $nodeClass */
			$followApp = $itemClass::$application;
			$followArea = mb_strtolower( mb_substr( $nodeClass, mb_strrpos( $nodeClass, '\\' ) + 1 ) );
			$time = time();
			$follows = array();
			foreach( Db::i()->select( "MD5( CONCAT( '{$followApp};{$followArea};{$this->_id};', follow_member_id ) ) AS follow_id, '{$followApp}' AS follow_app, '{$followArea}' AS follow_area, '{$this->_id}' AS follow_rel_id, follow_member_id, follow_is_anon, '{$time}' AS follow_added, follow_notify_do, follow_notify_meta, follow_notify_freq, 0 AS follow_notify_sent, follow_visible", 'core_follow', array(	'follow_app=? AND follow_area=? AND follow_rel_id=?', 'core', 'club', $club->id ) ) AS $follow )
			{
				$follows[] = $follow;
			}

			if ( count( $follows ) )
			{
				Db::i()->insert( 'core_follow', $follows );
			}
		}
	}
	
	/**
	 * Class-specific routine when saving club form
	 *
	 * @param	Club	$club	The club
	 * @param	array				$values	Values
	 * @return	void
	 */
	public function _saveClubForm( Club $club, array $values ): void
	{
		// Deliberately does nothing so classes can override
	}
	
	/**
	 * Set the permission index permissions to a specific club
	 *
	 * @param	Club	$club	The club
	 * @return  void
	 */
	public function setPermissionsToClub( Club $club ): void
	{
		/* Delete current rows */
		Db::i()->delete( 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id=?', static::$permApp, static::$permType, $this->_id ) );

		/* Build new rows */
		$insert = array( 'app' => static::$permApp, 'perm_type' => static::$permType, 'perm_type_id' => $this->_id );
		foreach ( static::$permissionMap as $k => $v )
		{
			if ( in_array( $k, array( 'view', 'read' ) ) )
			{
				switch ( $club->type )
				{
					case $club::TYPE_PUBLIC:
					case $club::TYPE_OPEN:
					case $club::TYPE_READONLY:
						$insert[ 'perm_' . $v ] = '*';
						break;					
					case $club::TYPE_CLOSED:
						$insert[ 'perm_' . $v ] = ( $this->isPublic() ) ? "*" : "cm,c{$club->id}";						
						break;
					case $club::TYPE_PRIVATE:
						$insert[ 'perm_' . $v ] = "cm,c{$club->id}";
						break;
				}
			}
			elseif ( in_array( $k, array( 'add', 'edit', 'reply', 'review' ) ) )
			{
				switch ( $club->type )
				{
					case $club::TYPE_PUBLIC:
						$insert[ 'perm_' . $v ] = 'ca';
						break;
					case $club::TYPE_CLOSED:
						$insert[ 'perm_' . $v ] = ( $this->isPublic() == 2 ) ? "*" : "cm,c{$club->id}";
						break;
					case $club::TYPE_OPEN:
					case $club::TYPE_PRIVATE:
					case $club::TYPE_READONLY:
						$insert[ 'perm_' . $v ] = "cm,c{$club->id}";
						break;
				}
			}
			else
			{
				switch ( $club->type )
				{
					case $club::TYPE_PUBLIC:
					case $club::TYPE_READONLY:
						$insert[ 'perm_' . $v ] = 'ca';
						break;
					
					case $club::TYPE_OPEN:
					case $club::TYPE_CLOSED:
					case $club::TYPE_PRIVATE:
						$insert[ 'perm_' . $v ] = "cm,c{$club->id}";
						break;
				}
			}
		}

		/* Insert */
		$permId = Db::i()->insert( 'core_permission_index', $insert );
		
		/* Update tags permission cache */
		if ( isset( static::$permissionMap['read'] ) )
		{
			Db::i()->update( 'core_tags_perms', array( 'tag_perm_text' => $insert[ 'perm_' . static::$permissionMap['read'] ] ), array( 'tag_perm_aap_lookup=?', md5( static::$permApp . ';' . static::$permType . ';' . $this->_id ) ) );
		}

		/* Make sure this object resets the permissions internally */
		$this->_permissions = array_merge( array( 'perm_id' => $permId ), $insert );
		
		/* Update search index */
		$this->updateSearchIndexPermissions();
	}

	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULL to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @param	array|NULL			$limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	array
	 */
	public static function rootsWithClubs( ?string $permissionCheck='view', Member $member=NULL, mixed $where=array(), array|null $limit=NULL ): array
	{
		/* Will we need to check permissions? */
		$usingPermssions = ( in_array( 'IPS\Node\Permissions', class_implements( get_called_class() ) ) and $permissionCheck !== NULL );
		if ( $usingPermssions )
		{
			$member = $member ?: Member::loggedIn();
		}

		if( static::$databaseColumnParent !== NULL )
		{
			$where[] = array( static::$databasePrefix . static::$databaseColumnParent . '=?', static::$databaseColumnParentRootValue );
		}
		
		$order = static::$databasePrefix . static::clubIdColumn();

		if( static::$databaseColumnOrder !== NULL )
		{
			$order .= ', ' . static::$databasePrefix . static::$databaseColumnOrder;
		}

		return static::nodesWithPermission( $usingPermssions ? $permissionCheck : NULL, $member, $where, $order, $limit );
	}

	/**
	 * @brief	Cached club nodes
	 */
	protected static array|null $cachedClubNodes = NULL;
	
	/**
	 * Fetch All Nodes in Clubs
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @return	array
	 */
	public static function clubNodes( string|null $permissionCheck='view', Member|null $member=NULL, mixed $where=array() ): array
	{
		if( static::$cachedClubNodes === NULL )
		{
			$clubIdColumn = static::clubIdColumn();

			$where[] = array( static::$databasePrefix . $clubIdColumn . ' IS NOT NULL' );
			
			$member = $member ?: Member::loggedIn();

			if( $member->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) )
			{
				static::$cachedClubNodes = static::nodesWithPermission( $permissionCheck, $member, $where );
			}
			else
			{
				static::$cachedClubNodes = array();
			}

			/* Preload the clubs so we don't query each one individually later */
			$clubIds = array();

			foreach( static::$cachedClubNodes as $node )
			{
				if ( $node->$clubIdColumn )
				{
					$clubIds[] = $node->$clubIdColumn;
				}
			}

			if( count( $clubIds ) )
			{
				foreach( Db::i()->select( '*', 'core_clubs', array( 'id IN(' . implode( ',', $clubIds ) . ')' ) ) as $clubData )
				{
					Club::constructFromData( $clubData );
				}
			}
		}

		return static::$cachedClubNodes;
	}
	
	/**
	 * Check Moderator Permission
	 *
	 * @param string $type		'edit', 'hide', 'unhide', 'delete', etc.
	 * @param	Member|NULL			$member		The member to check for or NULL for the currently logged in member
	 * @param string $class		The class to check against
	 * @return	bool
	 */
	public function clubContainerPermission( string $type, Member|null $member, string $class ): bool
	{
		if ( Settings::i()->clubs )
		{
			$clubIdColumn	= $this->clubIdColumn();
			
			$class = $class ?: static::$contentItemClass;
			$title = $class::$title;

			if ( $this->$clubIdColumn and $club = $this->club() )
			{
				if ( in_array( $club->memberStatus( $member ), array( Club::STATUS_LEADER, Club::STATUS_MODERATOR ) ) )
				{
					if ( in_array( $type, explode( ',', Settings::i()->clubs_modperms ) ) )
					{
						return TRUE;
					}
				}
				elseif ( $member->modPermission( "can_{$type}_{$title}" ) and is_array( $member->modPermission( static::$modPerm ) ) and $member->modPermission('can_access_all_clubs') )
				{
					if ( in_array( $type, explode( ',', Settings::i()->clubs_modperms ) ) )
					{
						return TRUE;
					}
				}
			}		
		}

		return false;
	}
	
	/**
	 * Is public
	 *
	 * @return	int 0 = not public, 1 = anyone can view, 2 = anyone can participate
	 */
	public function isPublic(): int
	{
		try
		{
			return (int) Db::i()->select( 'public', 'core_clubs_node_map', array( 'club_id=? AND node_class=? AND node_id=?', $this->club()->id, get_called_class(), $this->_id ) )->first();
		}
		catch ( UnderflowException $e )
		{
			return 0;
		}
	}
}