<?php
/**
 * @brief		Subscriptions table
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		09 Feb 2018
 */

namespace IPS\nexus\Subscription;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Helpers\Table\Table as TableHelper;
use IPS\Http\Url;
use IPS\nexus\Subscription;
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
 * Subscriptions table
 */
class Table extends TableHelper
{
	/**
	 * @brief	Sort options
 	*/
	public array $sortOptions = array( 'sp_position' );

	/**
	 * @brief	Active Subscription
	 */
	public Subscription|null $activeSubscription = NULL;
	
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

		$this->tableTemplate = array( Theme::i()->getTemplate( 'subscription', 'nexus', 'front' ), 'table' );
		$this->rowsTemplate = array( Theme::i()->getTemplate( 'subscription', 'nexus', 'front' ), 'rows' );
	}

	/**
	 * Get rows
	 *
	 * @param	array|null	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( ?array $advancedSearchValues=NULL ): array
	{
		if ( static::$rows === NULL )
		{
			/* Check sortBy */
			$this->sortBy = in_array( $this->sortBy, $this->sortOptions ) ? $this->sortBy : 'sp_position';
	
			/* What are we sorting by? */
			$sortBy = $this->sortBy . ' ' . ( ( $this->sortDirection and mb_strtolower( $this->sortDirection ) == 'desc' ) ? 'desc' : 'asc' );
	
			/* Specify filter in where clause */
			$where = $this->where;
			
			if ( $this->filter and isset( $this->filters[ $this->filter ] ) )
			{
				$where[] = is_array( $this->filters[ $this->filter ] ) ? $this->filters[ $this->filter ] : array( $this->filters[ $this->filter ] );
			}

			// We want also the disabled but active subscription
			if( !$this->activeSubscription )
			{
				$where[] = array( 'sp_enabled=1' );
			}
			else
			{
				$where[] = array( '(sp_enabled=1 OR sp_id=?)', $this->activeSubscription->package_id );
			}
	
			/* Get Count */
			$count = Db::i()->select( 'COUNT(*) as cnt', 'nexus_member_subscription_packages', $where )->first();
	  		$this->pages = ceil( $count / $this->limit );
	
			/* Get results */
			$it = Db::i()->select( '*', 'nexus_member_subscription_packages', $where, $sortBy, array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ) );
			$rows = iterator_to_array( $it );

			static::$rows = array();
			foreach( $rows as $index => $row )
			{
				try
				{
					static::$rows[ $index ]	= Package::constructFromData( $row );
				}
				catch ( Exception ) { }
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