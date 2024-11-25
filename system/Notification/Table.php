<?php
/**
 * @brief		Notifications Table Helper
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 July 2014
 */

namespace IPS\Notification;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Db;
use IPS\Helpers\Table\Table as TableHelper;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use LogicException;
use RuntimeException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notifications Table Helper
 */
class Table extends TableHelper
{
	/**
	 * @brief	Sort options
	 */
	public array $sortOptions = array( 'updated_time' );
	
	/**
	 * @brief	Rows
	 */
	protected static ?array $rows = null;
	
	/**
	 * @brief	WHERE clause
	 */
	protected ?array $where = array();
	
	/**
	 * Constructor
	 *
	 * @param	Url|null $url	Base URL
	 * @return	void
	 */
	public function __construct( Url $url=NULL )
	{
		/* Init */	
		parent::__construct( $url );

		$this->tableTemplate = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'notificationsTable' );
		$this->rowsTemplate = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'notificationsRows' );
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
			static::$rows = array();
			
			/* Check sortBy */
			$this->sortBy = in_array( $this->sortBy, $this->sortOptions ) ? $this->sortBy : 'updated_time';
	
			/* What are we sorting by? */
			$sortBy = $this->sortBy . ' ' . ( ( $this->sortDirection and mb_strtolower( $this->sortDirection ) == 'asc' ) ? 'asc' : 'desc' );
	
			/* Specify filter in where clause */
			$where = isset( $this->where ) ? is_array( $this->where ) ? $this->where : array( $this->where ) : array();

			/* Limit applications */
			$where[] = array( "notification_app IN('" . implode( "','", array_keys( Application::enabledApplications() ) ) . "')" );
			
			if ( $this->filter and isset( $this->filters[ $this->filter ] ) )
			{
				$where[] = is_array( $this->filters[ $this->filter ] ) ? $this->filters[ $this->filter ] : array( $this->filters[ $this->filter ] );
			}
	
			/* Get Count */
			$count = Db::i()->select( 'COUNT(*) as cnt', 'core_notifications', $where )->first();
	  		$this->pages = ceil( $count / $this->limit );
	
			/* Get results */
			$it = Db::i()->select( '*', 'core_notifications', $where, $sortBy, array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ) );
			$rows = iterator_to_array( $it );
	
			foreach( $rows as $index => $row )
			{
				try
				{
					$notification   = Inline::constructFromData( $row );
					static::$rows[ $index ]	= array( 'notification' => $notification, 'data' => $notification->getData() );
				}
				catch( LogicException | RuntimeException $e ){}
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
