<?php
/**
 * @brief		Table Builder for followed content
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		16 Apr 2014
 */

namespace IPS\core\Followed;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content\Tag;
use IPS\Db;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Table Builder for followed content
 */
class Table extends \IPS\Helpers\Table\Db
{	
	/**
	 * @brief	Follow class data
	 */
	protected array $currentType = [];

	/**
	 * @var string
	 */
	protected string $className = '';
	
	/**
	 * Constructor
	 *
	 * @param	string	$className		Name of class to load data from
	 * @param	array	$currentType	Follow class data
	 */
	public function __construct( $className, $currentType )
	{
		$this->className	= $className;
		$this->currentType	= $currentType;
		$this->include		= array( 'follow_added', 'follow_app', 'follow_area', 'follow_rel_id', 'follow_notify_freq', 'follow_is_anon' );
		$this->classes[]	= 'ipsData--zebra';

		/* Allow to filter by digests only */
		$this->filters['digest']	= array( "follow_notify_freq IN('daily','weekly')" );

		$return	= parent::__construct( 'core_follow', Url::internal( 'app=core&module=system&controller=followed', 'front', 'followed_content' )->setQueryString( 'type', "{$currentType[0]}_{$currentType[1]}" ), array( 'follow_member_id=? AND follow_app=? AND follow_area=?', Member::loggedIn()->member_id, $currentType[0], $currentType[1] ) );

		if ( !$this->sortBy )
		{
			$this->sortBy = 'follow_added';
			$this->sortDirection = 'desc';
		}

		if( is_subclass_of( $className, 'IPS\Content\Item' ) )
		{
			/* @var array $databaseColumnMap */
			$this->rowsTemplate = $className::manageFollowRows();
			$containerClass = $className::$containerNodeClass;
			
			$this->joins = array(
				array( 'from' => $className::$databaseTable, 'where' => array( $className::$databaseTable . '.' . $className::$databasePrefix . $className::$databaseColumnId . '=core_follow.follow_rel_id AND core_follow.follow_app=?', $currentType[0] ) )
			);
			
			/* Exclude hidden items */
			if ( IPS::classUSesTrait( $className, 'IPS\Content\Hideable' ) and !$className::canViewHiddenItems() )
			{
				$authorCol = $className::$databaseTable . '.' . $className::$databasePrefix . $className::$databaseColumnMap['author'];
				if ( isset( $className::$databaseColumnMap['approved'] ) )
				{
					$col = $className::$databaseTable . '.' . $className::$databasePrefix . $className::$databaseColumnMap['approved'];
					$this->where[0] .= " AND ( {$col}=1 OR ( {$col}=0 AND {$authorCol}=" . Member::loggedIn()->member_id . " ) )";
				}
				elseif ( isset( $className::$databaseColumnMap['hidden'] ) )
				{
					$col = $className::$databaseTable . '.' . $className::$databasePrefix . $className::$databaseColumnMap['hidden'];
					$this->where[0] .= " AND ( {$col}=0 OR ( {$col}=1 AND {$authorCol}=" . Member::loggedIn()->member_id . " ) )";
				}
			}
			
			/* Additional where */
			$joinContainer = (bool )$className::$containerNodeClass;
			$joins = array();
			$extraWhere = $className::followWhere( $joinContainer, $joins );

			if ( $joinContainer )
			{
				$this->joins[] = array( 'from' => $containerClass::$databaseTable, 'where' => $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId . '=' . $className::$databaseTable . '.' . $className::$databasePrefix . $className::$databaseColumnMap['container'], 'type' => 'STRAIGHT_JOIN' );
			}
			
			/* Permissions */
			if( in_array( 'IPS\Node\Permissions', class_implements( $containerClass ) ) )
			{
				/* @var array $permissionMap */
				$this->where[0] .= ' AND (' . Db::i()->findInSet( 'core_permission_index.perm_' . $containerClass::$permissionMap['view'], Member::loggedIn()->permissionArray() ) . ' OR core_permission_index.perm_' . $containerClass::$permissionMap['view'] . "='*' )";
			}
			
			$this->joins[] = array( 'from' => 'core_permission_index', 'where' => array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId, $containerClass::$permApp, $containerClass::$permType ) );
			
			if ( $extraWhere )
			{
				if( isset( $extraWhere['item'] ) or isset( $extraWhere['container'] ) )
				{
					$this->where = array( $this->where );
					
					if( isset( $extraWhere['item'] ) )
					{
						$this->where	= array_merge( $this->where, $extraWhere['item'] );
					}

					if( isset( $extraWhere['container'] ) )
					{
						$this->where	= array_merge( $this->where, $extraWhere['container'] );
					}
				}
				else
				{
					$this->where	= array_merge( array( $this->where ), $extraWhere );
				}
			}
			
			$this->joins = array_merge( $this->joins, $joins );
		}
		elseif( $className == "\IPS\Member" )
		{
			$this->rowsTemplate = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'followedContentMemberRow' );
		}
		elseif( $className == "IPS\Content\Tag" )
		{
			$this->rowsTemplate = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'followedContentTagRow' );
		}
		else
		{
			/* @var Model $className */
			$this->rowsTemplate = $className::manageFollowNodeRow();

			$this->joins	= array(
				array( 'from' => $className::$databaseTable, 'where' => array( $className::$databaseTable . '.' . $className::$databasePrefix . $className::$databaseColumnId . '=follow_rel_id AND follow_app=?', $currentType[0] ) ),
				array( 'from' => 'core_permission_index', 'where' => array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $className::$databaseTable . '.' . $className::$databasePrefix . $className::$databaseColumnId, $className::$permApp, $className::$permType ) ),
			);

			/* Permission check? */
			if ( in_array( 'IPS\Node\Permissions', class_implements( $className ) ) )
			{
				/* @var array $permissionMap */
				$this->where[0] .= ' AND (' . Db::i()->findInSet( 'core_permission_index.perm_' . $className::$permissionMap['view'], Member::loggedIn()->permissionArray() ) . ' OR ' . 'core_permission_index.perm_' . $className::$permissionMap['view'] . "='*' )";
			}
			
			/* Additional where */
			if ( method_exists( $className, 'followWhere' ) )
			{
				$joins = array();
				$extraWhere	= $className::followWhere( $joins );
				$this->joins = array_merge( $this->joins, $joins );

				if( isset( $extraWhere['item'] ) )
				{
					$this->where	= array_merge( array( $this->where ), $extraWhere['item'] );

					if( isset( $extraWhere['container'] ) )
					{
						$this->where	= array_merge( $this->where, $extraWhere['container'] );
					}
				}
				else
				{
					$this->where	= array_merge( array( $this->where ), $extraWhere );
				}
			}
		}

		/* Do any multi-mod */
		if ( isset( Request::i()->modaction ) )
		{
			$this->multiMod();
		}

		return $return;
	}

	/**
	 * Get rows
	 *
	 * @param	array|null	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( array $advancedSearchValues = NULL ): array
	{
		$rows	= parent::getRows( $advancedSearchValues );

		foreach( $rows as $index => $row )
		{
			if( $row['follow_area'] == 'member' AND $row['follow_app'] == 'core' )
			{
				$object = Member::load( $row['follow_rel_id'] );
				$object->_followData = $row;
				$rows[ $index ]	= $object;
			}
			elseif( $row['follow_area'] == 'tag' and $row['follow_app'] == 'core' )
			{
				$object = Tag::load( $row['follow_rel_id'] );
				$object->_followData = $row;
				$rows[ $index ] = $object;
			}
			else 
			{
				foreach( Application::load( $row['follow_app'] )->extensions( 'core', 'ContentRouter' ) as $key => $router )
				{
					foreach( $router->classes as $class )
					{
						$followArea		= mb_strtolower( mb_substr( $class, mb_strrpos( $class, '\\' ) + 1 ) );
	
						if( $followArea == $row['follow_area'] AND $class::$application == $row['follow_app'] )
						{
							try
							{
								$object = $class::load( $row['follow_rel_id'] );
								$object->_followData = $row;
								$rows[ $index ]	= $object;
							}
							catch( OutOfRangeException $e )
							{
								unset( $rows[ $index ] );

								/* If the item doesn't exist we may as well clean up core_follow */
								Db::i()->delete( 'core_follow', array( array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', $row['follow_app'], $row['follow_area'], $row['follow_rel_id'] ) ) );
							}
						}
						else
						{
							$containers		= array();
	
							if( isset( $class::$containerNodeClass ) )
							{
								$containers[ $class::$containerNodeClass ]	= $class::$containerNodeClass;
							}
	
							if( isset( $class::$containerFollowClasses ) )
							{
								foreach( $class::$containerFollowClasses as $followClass )
								{
									$containers[ $followClass ]	= $followClass;
								}
							}
	
							foreach( $containers as $container )
							{
								$containerArea	= mb_strtolower( mb_substr( $container, mb_strrpos( $container, '\\' ) + 1 ) );
	
								if( $containerArea == $row['follow_area'] AND $class::$application == $row['follow_app'] )
								{
									try
									{
										$object = $container::load( $row['follow_rel_id'] );
										$object->_followData = $row;
										$rows[ $index ]	= $object;
									}
									catch( OutOfRangeException $e )
									{
										unset( $rows[ $index ] );
		
										/* If the item doesn't exist we may as well clean up core_follow */
										Db::i()->delete( 'core_follow', array( array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', $row['follow_app'], $row['follow_area'], $row['follow_rel_id'] ) ) );
									}
								}
							}
						}
					}
				}
			}
		}

		return $rows;
	}
	
	/**
	 * @brief	Return table filters
	 */
	public bool $showFilters	= TRUE;
	
	/**
	 * Saved Actions (for multi-moderation)
	 */
	public array $savedActions = array();
	
	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public function getFilters() : array
	{
		return array(
			'follow_freq_immediate', 'follow_freq_daily', 'follow_freq_weekly', 'follow_freq_none',
			'',
			'follow_privacy_1', 'follow_privacy_0'
		);
	}
	
	/**
	 * Does the user have permission to use the multi-mod checkboxes?
	 *
	 * @param	string|null		$action		Specific action to check (hide/unhide, etc.) or NULL for a generic check
	 * @return	bool
	 */
	public function canModerate( ?string $action=NULL ): bool
	{
		if ( $action === NULL )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * What multimod actions are available
	 *
	 * @param	object	$item	Item
	 * @return	array
	 */
	public function multimodActions( object $item ) : array
	{	
		$return = array();
			
		if ( $item->_followData )
		{
			if ( !( $item instanceof Member ) )
			{
				foreach ( array( 'immediate', 'daily', 'weekly', 'none' ) as $k )
				{
					if ( $item->_followData['follow_notify_freq'] != $k )
					{
						$return[] = 'follow_' . $k;
					}
				}
			}
						
			if ( $item->_followData['follow_is_anon'] )
			{
				$return[] = 'follow_public';
			}
			else
			{
				$return[] = 'follow_anonymous';
			}
						
			$return[] = 'unfollow';
		}
		
		return $return;
	}
	
	/**
	 * Multimod
	 *
	 * @return	void
	 */
	protected function multimod() : void
	{
		if( !is_array( Request::i()->moderate ) )
		{
			return;
		}

		Session::i()->csrfCheck();
		
		$where = array( array( 'follow_member_id=? AND follow_app=? AND follow_area=?', Member::loggedIn()->member_id, $this->currentType[0], $this->currentType[1] ), Db::i()->in( 'follow_rel_id', array_keys( Request::i()->moderate ) ) );
		
		$update = array();
		switch ( Request::i()->modaction )
		{
			case 'follow_immediate':
				Db::i()->update( 'core_follow', array( 'follow_notify_freq' => 'immediate' ), $where );
				break;
			case 'follow_daily':
				Db::i()->update( 'core_follow', array( 'follow_notify_freq' => 'daily' ), $where );
				break;
			case 'follow_weekly':
				Db::i()->update( 'core_follow', array( 'follow_notify_freq' => 'weekly' ), $where );
				break;
			case 'follow_none':
				Db::i()->update( 'core_follow', array( 'follow_notify_freq' => 'none' ), $where );
				break;
			
			case 'follow_public':
				Db::i()->update( 'core_follow', array( 'follow_is_anon' => 0 ), $where );
				break;
			case 'follow_anonymous':
				Db::i()->update( 'core_follow', array( 'follow_is_anon' => 1 ), $where );
				break;
				
			case 'unfollow':
				Db::i()->delete( 'core_follow', $where );
				break;
		}
		
		Output::i()->redirect( $this->baseUrl );
	}
}