<?php
/**
 * @brief		Blog Table Helper
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		18 Mar 2014
 */

namespace IPS\blog\Blog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Blog;
use IPS\Db;
use IPS\Helpers\Table\Table as TableHelper;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Blog Table Helper
 */
class Table extends TableHelper
{
	/**
	 * @brief	Container
	 */
	protected ?Model $container = NULL;

	/**
	 * @brief	Where clauses
	 */
	public ?array $where;

	/**
	 * Constructor
	 *
	 * @param	Url|null	$url	Base URL
	 * @return	void
	 */
	public function __construct( Url $url=NULL )
	{
		/* Init */	
		parent::__construct( $url );
		$this->rowsTemplate = array( Theme::i()->getTemplate( 'browse', 'blog' ), 'rows' );
				
		/* Set available sort options */
		foreach ( array( 'last_edate', 'rating_total', 'num_views' ) as $k ) 
		{
			$this->sortOptions[ $k ] = 'blog_' . $k;
		}

		if ( !$this->sortBy )
		{
			$this->sortBy = 'blog_last_edate';
		}
	}

	/**
	 * Set owner
	 *
	 * @param	Member	$member		The member to filter by
	 * @return	void
	 */
	public function setOwner( Member $member ) : void
	{
		$this->where[]	= array( '(' . Db::i()->findInSet( 'blog_groupblog_ids', $member->groups ) . ' OR ' . 'blog_member_id=? )', $member->member_id );
	}

	/**
	 * Get rows
	 *
	 * @param	array|null	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( array $advancedSearchValues = NULL ): array
	{
		/* Check sortBy */
		$this->sortBy	= in_array( $this->sortBy, $this->sortOptions ) ? $this->sortBy : 'blog_name';

		/* What are we sorting by? */
		$sortBy = 'blog_pinned DESC, ' . $this->sortBy . ' ' . ( ( $this->sortDirection and mb_strtolower( $this->sortDirection ) == 'asc' ) ? 'asc' : 'desc' );

		/* Specify filter in where clause */
		$where = isset( $this->where ) ? is_array( $this->where ) ? $this->where : array( $this->where ) : array();
		$where[] = array( 'blog_disabled=0' );
		if ( $this->filter and isset( $this->filters[ $this->filter ] ) )
		{
			$where[] = is_array( $this->filters[ $this->filter ] ) ? $this->filters[ $this->filter ] : array( $this->filters[ $this->filter ] );
		}
		
		/* Exclude private blogs unless we have permission to view them */
		if ( Member::loggedIn()->member_id )
		{
			$where[] = array( '( blog_social_group IS NULL OR blog_member_id=? OR blog_social_group IN(?) )', Member::loggedIn()->member_id, Db::i()->select( 'group_id', 'core_sys_social_group_members', array( 'member_id=?', Member::loggedIn()->member_id ) ) );
		}
		else
		{
			$where[] = array( 'blog_social_group IS NULL' );
		}
		
		/* Exclude club blogs unless we have permission to view them */
		if ( Settings::i()->club_nodes_in_apps )
		{
			if ( Member::loggedIn()->member_id )
			{
				$where[] = array(
					'( blog_club_id IS NULL OR blog_club_id IN(?) OR blog_club_id IN(?) )',
					Db::i()->select( 'id', 'core_clubs', array( Db::i()->in( 'type', array( Club::TYPE_PUBLIC, Club::TYPE_OPEN, Club::TYPE_READONLY ) ) ) ),
					Db::i()->select( 'club_id', 'core_clubs_memberships', array( "member_id=? AND status IN('" . Club::STATUS_MEMBER . "','" . Club::STATUS_MODERATOR . "','" . Club::STATUS_LEADER . "')", Member::loggedIn()->member_id ) ),
				);
			}
			else
			{
				$where[] = array(
					'( blog_club_id IS NULL OR blog_club_id IN(?) )',
					Db::i()->select( 'id', 'core_clubs', array( Db::i()->in( 'type', array( Club::TYPE_PUBLIC, Club::TYPE_OPEN, Club::TYPE_READONLY ) ) ) ),
				);
			}
		}
		else
		{
			$where[] = array( 'blog_club_id IS NULL' );
		}

		/* Get Count */
		$count = Db::i()->select( 'COUNT(*) as cnt', 'blog_blogs', $where )->first();
  		$this->pages = ceil( $count / $this->limit );

		/* Get results */
		$it = Db::i()->select( '*', 'blog_blogs', $where, $sortBy, array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ) );
		$rows = iterator_to_array( $it );

		foreach( $rows as $index => $row )
		{
			$rows[ $index ]	= Blog::constructFromData( $row );
		}

		/* Return */
		return $rows;
	}

	/**
	 * Return the table headers
	 *
	 * @param	array|NULL	$advancedSearchValues	Advanced search values
	 * @return	array
	 */
	public function getHeaders( array $advancedSearchValues=NULL ): array
	{
		return array();
	}
}