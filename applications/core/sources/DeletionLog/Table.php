<?php
/**
 * @brief		Deletion Log Table
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Nov 2016
 */

namespace IPS\core\DeletionLog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\Db;
use IPS\Helpers\Table\Table as TableHelper;
use IPS\Http\Url;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use function defined;
use function in_array;
use function strtolower;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Deletion Log Table
 */
class Table extends TableHelper
{
	/**
	 * @brief	Title
	 */
	public string $title = 'modcp_deleted';
	
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
	}
	
	/**
	 * Get rows
	 *
	 * @param	array|null	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( array $advancedSearchValues=NULL ): array
	{
		$this->sortBy	= in_array( $this->sortBy, $this->sortOptions ) ? $this->sortBy : 'dellog_deleted_date';
		$sortBy			= $this->sortBy . ' ' . ( $this->sortDirection and strtolower( $this->sortDirection ) == 'asc' ? 'asc' : 'desc' );
		
		/* Where Clause */
		$where = array();
		$where[] = array( Db::i()->findInSet( 'dellog_content_permissions', Member::loggedIn()->permissionArray() ) . " OR dellog_content_permissions=?", '*' );

		/* Return only content from enabled apps */
		$where[] = Db::i()->in( 'dellog_content_class', array_values( Content::routedClasses( TRUE, TRUE ) ) );


		if ( $advancedSearchValues )
		{
			if ( isset( $advancedSearchValues['dellog_content_class'] ) AND $advancedSearchValues['dellog_content_class'] != 'all' )
			{
				$where[] = array( "dellog_content_class=?", $advancedSearchValues['dellog_content_class'] );
			}
			
			if ( !empty( $advancedSearchValues['dellog_deleted_by'] ) )
			{
				$where[] = array( "dellog_deleted_by=?", $advancedSearchValues['dellog_deleted_by']->member_id );
			}
		}
		
		$count = Db::i()->select( 'COUNT(*)', 'core_deletion_log', $where )->first();
		
		$this->pages = ceil( $count / $this->limit );
		$it = new ActiveRecordIterator( Db::i()->select( '*', 'core_deletion_log', $where, $sortBy, array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ) ), 'IPS\core\DeletionLog' );
		
		$return = array();
		foreach( iterator_to_array( $it ) AS $row )
		{
			$return[ $row->id ] = $row;
		}
		
		return $return;
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
	
	/**
	 * Multimod Actions
	 *
	 * @return	array
	 */
	public function multimodActions(): array
	{
		return array(
			'restore',
			'restore_as_hidden',
			'delete'
		);
	}
	
	/**
	 * Can Moderate
	 *
	 * @param	NULL|string	$action	Action to take
	 * @return	bool
	 */
	public function canModerate( string $action=NULL ): bool
	{
		return TRUE;
	}
}