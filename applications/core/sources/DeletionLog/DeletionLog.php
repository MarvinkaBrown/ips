<?php
/**
 * @brief		Deletion Log Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Nov 2016
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Search\Index;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecord;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function class_exists;
use function defined;
use function get_class;
use function in_array;
use function is_array;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Deletion Log Model
 */
class DeletionLog extends ActiveRecord
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_deletion_log';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'dellog_';
	
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
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->deleted_date = DateTime::create();
	}
	
	/**
	 * Set deleted date
	 *
	 * @param	DateTime	$time	A DateTime object
	 * @return	void
	 */
	public function set_deleted_date( DateTime $time ) : void
	{
		$this->_data['deleted_date'] = $time->getTimestamp();
	}
	
	/**
	 * Get deleted date
	 *
	 * @return	DateTime
	 */
	public function get_deleted_date() : DateTime
	{
		return DateTime::ts( $this->_data['deleted_date'] );
	}
	
	/**
	 * Set Deleted By
	 *
	 * @param	Member		$member	The member
	 * @return	void
	 */
	public function set_deleted_by( Member $member ) : void
	{
		$this->_data['deleted_by']			= $member->member_id;
		$this->_data['deleted_by_name']		= $member->real_name;
		$this->_data['deleted_by_seo_name']	= $member->members_seo_name;
	}
	
	/**
	 * @brief	Deleted By Cache
	 */
	protected ?Member $_deletedBy = NULL;
	
	/**
	 * Get Deleted By
	 *
	 * @return	Member
	 */
	public function get__deleted_by() : Member
	{
		if ( $this->_deletedBy === NULL AND $this->_data['deleted_by'] !== NULL )
		{
			$this->_deletedBy = Member::load( $this->_data['deleted_by'] );
		}
		return $this->_deletedBy;
	}
	
	/**
	 * Get Permissions
	 *
	 * @return	array|string
	 */
	public function get_content_permissions() : array|string
	{
		if ( $this->_data['content_permissions'] == '*' )
		{
			return $this->_data['content_permissions'];
		}
		
		$perms = explode( ',', $this->_data['content_permissions'] );
		
		$return = array( 'members' => array(), 'groups' => array() );
		foreach( $perms AS $perm )
		{
			if ( substr( $perm, 0, 1 ) == 'm' )
			{
				$return['members'][] = str_replace( 'm', '', $perm );
			}
			else
			{
				$return['groups'][] = $perm;
			}
		}
		
		return $return;
	}
	
	/**
	 * Get the date the content will be permanently removed on
	 *
	 * @return	DateTime
	 */
	public function get_deletion_date() : DateTime
	{
		return $this->deleted_date->add( new DateInterval( 'P' . Settings::i()->dellog_retention_period . 'D' ) );
	}
	
	/**
	 * Set Content and Member
	 *
	 * @param	Content		$content	The content being deleted.
	 * @param	Member|NULL	$member		The member performing the deletion, NULL for the currently logged in member, or FALSE for no member (i.e. system task)
	 * @return	void
	 * @note Convenience ftw
	 */
	public function setContentAndMember( Content $content, ?Member $member = NULL ) : void
	{
		if( $member === NULL )
		{
			$member = Member::loggedIn();
		}

		$idField = $content::$databaseColumnId;
		
		$item = $content;
		if ( $content instanceof Comment )
		{
			$item = $content->item();
		}
		
		/* Content Data */
		$this->content_class		= get_class( $content );
		$this->content_id			= $content->$idField;
		$this->content_title		= $item->mapped('title');
		$this->content_seo_title	= Friendly::seoTitle( $item->mapped('title') );
		$this->content_permissions	= $item->deleteLogPermissions();

		try
		{
			$this->content_container_id		= $item->container()->_id;
			$this->content_container_class	= $item::$containerNodeClass;
		}
		catch( BadMethodCallException | OutOfRangeException $e )
		{
			$this->content_container_id		= 0;
			$this->content_container_class	= NULL;
		}
		
		/* Member Data */
		if( $member )
		{
			$this->deleted_by	= $member;
		}
	}
	
	/**
	 * Save
	 *
	 * @return    void
	 */
	public function save(): void
	{
		if ( !$this->id )
		{
			$contentClass	= $this->content_class;
			if( Content\Search\SearchContent::isSearchable( $contentClass ) )
			{
				/* @var Content $contentClass */
				$content		= $contentClass::load( $this->content_id );
				Index::i()->removeFromSearchIndex( $content );
			}
		}
		
		parent::save();
	}
	
	/**
	 * Load and check perms
	 *
	 * @param	int		$id	ID
	 * @return	static
	 * @throws	OutOfRangeException
	 */
	public static function loadAndCheckPerms( int $id ) : static
	{
		$obj = parent::load( $id );
		
		if ( !$obj->canView() )
		{
			throw new OutOfRangeException;
		}
		
		return $obj;
	}
	
	/**
	 * Load from content
	 *
	 * @param	Content		$content	The content object
	 * @param	Member|null		$member		Member for permission checking
	 * @return	static
	 * @throws OutOfRangeException
	 */
	public static function loadFromContent( Content $content, ?Member $member = NULL ): static
	{
		$member = $member ?: Member::loggedIn();
		
		try
		{
			$idColumn = $content::$databaseColumnId;
			$log = static::constructFromData( Db::i()->select( '*', 'core_deletion_log', array( "dellog_content_class=? AND dellog_content_id=?", get_class( $content ), $content->$idColumn ) )->first() );
		}
		catch( UnderflowException $e )
		{
			throw new OutOfRangeException;
		}
		
		if ( !$log->canView( $member ) )
		{
			throw new OutOfRangeException;
		}
		
		return $log;
	}
	
	/**
	 * Can View
	 *
	 * @param	Member|NULL	$member	The member, or NULL for currently logged in
	 * @return	bool
	 */
	public function canView( ?Member $member = null ) : bool
	{
		$member = $member ?: Member::loggedIn();
		
		if ( !is_array( $this->content_permissions ) AND $this->content_permissions == '*' )
		{
			return TRUE;
		}
		
		if ( in_array( $member->member_id, $this->content_permissions['members'] ) )
		{
			return TRUE;
		}
		
		if ( $member->inGroup( $this->content_permissions['groups'] ) )
		{
			return TRUE;
		}

		if( $member->modPermission( 'can_access_all_clubs' ) AND in_array('cm', $this->content_permissions['groups'] ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * URL
	 *
	 * @param	string|NULL		$action	"action" parameter or NULL to go to the content
	 * @return	Url
	 */
	public function url( string|null $action=NULL ): Url
	{
		if ( $action === NULL )
		{
			if ( $object = $this->object() )
			{
				return $object->url()->setQueryString( 'showDeleted', 1 );
			}
			else
			{
				return Url::internal( "app=core&module=modcp&controller=modcp&tab=deleted", 'front', 'modcp_deleted' )->setQueryString( array( 'id' => $this->id, 'action' => $action ) );
			}
		}
		else
		{
			return Url::internal( "app=core&module=modcp&controller=modcp&tab=deleted", 'front', 'modcp_deleted' )->setQueryString( array( 'id' => $this->id, 'action' => $action ) );
		}
	}

	/**
	 * The content object
	 *
	 * @return ?Content
	 */
	public function object() : ?Content
	{
		$class	= $this->content_class;
		if ( class_exists( $this->content_class ) )
		{
			/* @var Content $class */
			try
			{
				return $class::load( $this->content_id );
			}
			catch( UnderflowException $e )
			{
				/* Orphaned item */
				$this->delete();
				return null;
			}
		}
		else
		{
			/* Content class doesn't exist anymore */
			$this->delete();
		}

		return null;
	}

	/**
	 * Get title wrapper for items and nodes
	 *
	 * @return string
	 */
	public function objectTitle() : string
	{
		$object = $this->object();

		if ( $object instanceof Item )
		{
			return $object->mapped('title');
		}
		else if ( $object instanceof Comment )
		{
			return $object->item()->mapped('title');
		}

		return '';
	}

	/**
	 * Get title wrapper for items and nodes
	 *
	 * @return Model|null
	 */
	public function objectContainer() : ?Model
	{
		$object = $this->object();

		if ( $object instanceof Item )
		{
			return $object->containerWrapper() ? $object->container() : null;
		}
		else if ( $object instanceof Comment )
		{
			return $object->item()->containerWrapper() ? $object->item()->container() : null;
		}

		return null;
	}

	/**
	 * Mass update permissions for a specific node
	 * 
	 * @param	string				$class	The Node Class to update
	 * @param	int					$id		The ID of the container
	 * @param	string|array		$perms	The permissions to update with
	 * @return	void
	 */
	public static function updateNodePermissions( string $class, int $id, string|array $perms ) : void
	{
		if ( is_array( $perms ) )
		{
			$perms = implode( ',', $perms );
		}

		Db::i()->update( 'core_deletion_log', array(
			'dellog_content_permissions' => $perms
		), array( "dellog_content_container_id=? AND dellog_content_container_class=?", $id, $class ) );
	}
}