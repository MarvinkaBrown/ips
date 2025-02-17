<?php
/**
 * @brief		Editor Media: Files
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		26 Dec 2013
 */

namespace IPS\downloads\extensions\core\EditorMedia;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\EditorMediaAbstract;
use IPS\File;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Media: Files
 */
class Files extends EditorMediaAbstract
{
	/**
	 * Get Counts
	 *
	 * @param	Member	$member		The member
	 * @param	string		$postKey	The post key
	 * @param	string|null	$search		The search term (or NULL for all)
	 * @return	array|int		array( 'Title' => 0 )
	 */
	public function count( Member $member, string $postKey, string $search=NULL ): array|int
	{
		$where = array(
			array( "record_file_id IN(?) AND record_type=? AND record_backup=0", Db::i()->select( 'file_id', 'downloads_files', array( 'file_submitter=?', $member->member_id ) ), 'upload' ),
		);
		
		if ( $search )
		{
			$where[] = array( "record_realname LIKE ( CONCAT( '%', ?, '%' ) )", $search );
		}
						
		return Db::i()->select( 'COUNT(*)', 'downloads_files_records', $where )->first();
	}
	
	/**
	 * Get Files
	 *
	 * @param	Member	$member	The member
	 * @param	string|null	$search	The search term (or NULL for all)
	 * @param	string		$postKey	The post key
	 * @param	int			$page	Page
	 * @param	int			$limit	Number to get
	 * @return	array		array( 'Title' => array( (IPS\File, \IPS\File, ... ), ... )
	 */
	public function get( Member $member, ?string $search, string $postKey, int $page, int $limit ): array
	{
		$where = array(
			array( "record_file_id IN(?) AND record_type=? AND record_backup=0", Db::i()->select( 'file_id', 'downloads_files', array( 'file_submitter=?', $member->member_id ) ), 'upload' ),
		);
		
		if ( $search )
		{
			$where[] = array( "record_realname LIKE ( CONCAT( '%', ?, '%' ) )", $search );
		}
		
		$return = array();
		foreach ( Db::i()->select( '*', 'downloads_files_records', $where, 'record_time DESC', array( ( $page - 1 ) * $limit, $limit ) ) as $row )
		{
			$file = \IPS\downloads\File::load( $row['record_file_id'] );

			$obj = File::get( 'downloads_Files', $row['record_location'], $row['record_size'] );
			$obj->originalFilename = $row['record_realname'];
			$obj->contextInfo = $file->name;

			$screenshot = $file->primary_screenshot_thumb;
			if( $screenshot instanceof File )
			{
				$obj->screenshot = $screenshot;
			}

			$return[ (string) $file->url()->setQueryString( array( 'do' => 'download', 'r' => $row['record_id'] ) ) ] = $obj;
		}
				
		return $return;
	}
}