<?php
/**
 * @brief		Promote Table Helper showing internally promoted items
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Feb 2017
 */

namespace IPS\core\Feature;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\Feature;
use IPS\Db;
use IPS\Helpers\Table\Table;
use IPS\Content\Tag as SystemTag;
use IPS\Http\Url;
use IPS\Member;
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
 * Promote Table Helper showing internally promoted items
 */
class PublicTable extends Table
{
	/**
	 * @brief	Sort options
	 */
	public array $sortOptions = array( 'promote_added' );
	
	/**
	 * @brief	Rows
	 */
	protected static ?array $rows = null;
	
	/**
	 * @brief	WHERE clause
	 */
	protected array $where = array();
	
	/**
	 * Constructor
	 *
	 * @param	Url|null	$url	Base URL
	 * @return	void
	 */
	public function __construct( ?Url $url=NULL )
	{
		/* Init */	
		parent::__construct( $url );

		$this->tableTemplate = array( Theme::i()->getTemplate( 'promote', 'core', 'front' ), 'promotePublicTable' );
		$this->rowsTemplate = array( Theme::i()->getTemplate( 'promote', 'core', 'front' ), 'promotePublicTableRows' );
	}

	/**
	 * Fetch all the tags > pinned items to show on a featured page
	 *
	 * @return array
	 */
	public function getPinnedTagItems(): array
	{
		$return = [];

		foreach( SystemTag::allEnabledTagsAsObjects() as $tag )
		{
			/* Get the pinned items */
			if ( $items = $tag->getPinnedItems() )
			{
				$return[] = array(
					'tag'	=> $tag,
					'items'	=> $items
				);
			}
		}

		return $return;
	}

	/**
	 * Set member
	 *
	 * @param	Member	$member		The member to filter by
	 * @return	void
	 */
	public function setMember( Member $member ) : void
	{
		$this->where[] = array( '`member`=?', $member->member_id );
	}

	/**
	 * Get rows
	 *
	 * @param	array|null	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( array $advancedSearchValues=NULL ): array
	{
		if ( static::$rows === NULL )
		{
			/* Check sortBy */
			$this->sortBy = in_array( $this->sortBy, $this->sortOptions ) ? $this->sortBy : 'promote_added';

			/* What are we sorting by? */
			$sortBy = $this->sortBy . ' ' . ( ( $this->sortDirection and mb_strtolower( $this->sortDirection ) == 'asc' ) ? 'asc' : 'desc' );

			/* Specify filter in where clause */
			$where = $this->where ?? array();
	
			if ( $this->filter and isset( $this->filters[ $this->filter ] ) )
			{
				$where[] = is_array( $this->filters[ $this->filter ] ) ? $this->filters[ $this->filter ] : array( $this->filters[ $this->filter ] );
			}
			
			$where[] = array( '( promote_hide=0 )' );
	
			/* Get Count */
			$count = Db::i()->select( 'COUNT(*) as cnt', 'core_content_promote', $where )->first();
	  		$this->pages = ceil( $count / $this->limit );
	
			/* Get results */
			$it = Db::i()->select( '*', 'core_content_promote', $where, $sortBy, array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ) );
			$rows = iterator_to_array( $it );

			static::$rows = array();
			foreach( $rows as $index => $row )
			{
				try
				{
					static::$rows[ $index ]	= Feature::constructFromData( $row );
				}
				catch ( Exception $e ) { }
			}
		}
		
		/* Return */
		return static::$rows;
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