<?php

/**
 * @brief        Team
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        1/8/2024
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Assignments\Assignment;
use IPS\Data\Store;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use OutOfRangeException;
use function count;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Team extends ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_members_teams';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'team_';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 * @note	If using this, declare a static $multitonMap = array(); in the child class to prevent duplicate loading queries
	 */
	protected static array $databaseIdFields = array( 'team_name' );

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'teams' );

	/**
	 * @return array
	 */
	public function get_members() : array
	{
		return isset( $this->_data['members'] ) ? explode( ",", $this->_data['members'] ) : [];
	}

	/**
	 * @param array $val
	 * @return void
	 */
	public function set_members( array $val ) : void
	{
		$members = [];
		foreach( $val as $value )
		{
			if( $value instanceof Member )
			{
				$members[] = $value->member_id;
			}
		}

		$this->_data['members'] = count( $members ) ? implode( ",", $members ) : null;
	}

	/**
	 * Return all members that are part of this team
	 *
	 * @return Member[]
	 */
	public function members() : array
	{
		return iterator_to_array(
			new ActiveRecordIterator(
				Db::i()->select( '*', 'core_members', Db::i()->in( 'member_id', $this->members ), 'name' ),
				Member::class
			)
		);
	}

	/**
	 * Load all teams, use the cache
	 *
	 * @return array
	 */
	public static function teams() : array
	{
		try
		{
			$cache = Store::i()->teams;
		}
		catch( OutOfRangeException )
		{
			$cache = static::getStore();
		}

		$teams = [];
		foreach( $cache as $row )
		{
			$teams[ $row['team_id'] ] = static::constructFromData( $row );
		}
		return $teams;
	}

	/**
	 * @return Url
	 */
	public function acpUrl() : Url
	{
		return Url::internal( "app=core&module=staff&controller=teams&do=form&id=" . $this->id );
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete() : void
	{
		parent::delete();

		/* Delete any assignments */
		foreach( Assignment::getAssignments( $this ) as $assignment )
		{
			$assignment->delete();
		}
	}

	/**
	 * @var array|null
	 */
	protected static ?array $_teams = null;

	/**
	 * Attempt to load cached data
	 *
	 * @note	This should be overridden in your class if you define $cacheToLoadFrom
	 * @return    array
	 */
	public static function getStore(): array
	{
		if( static::$_teams === null )
		{
			static::$_teams = iterator_to_array( Db::i()->select( '*', static::$databaseTable, NULL, static::$databasePrefix . static::$databaseColumnId )->setKeyField( static::$databasePrefix . static::$databaseColumnId ) );
		}

		return static::$_teams;
	}
}