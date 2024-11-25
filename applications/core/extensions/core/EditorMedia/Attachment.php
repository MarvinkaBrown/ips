<?php
/**
 * @brief		Editor Media: Attachment
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		{date}
 */

namespace IPS\core\extensions\core\EditorMedia;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Application;
use IPS\Content;
use IPS\Db;
use IPS\Extensions\EditorMediaAbstract;
use IPS\File;
use IPS\Member;
use IPS\Node\Model;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Media: Attachments
 */
class Attachment extends EditorMediaAbstract
{
	/**
	 * Get Counts
	 *
	 * @param	Member	$member		The member
	 * @param	string		$postKey	The post key
	 * @param	string|null	$search		The search term (or NULL for all)
	 * @return    array|int
	 */
	public function count( Member $member, string $postKey, string $search=NULL ): array|int
	{		
		$where = array(
			array( "attach_member_id=?", $member->member_id ),
		);
		if ( $postKey )
		{
			$where[] = array( 'attach_post_key<>?', $postKey );
		}
		if ( $search )
		{
			$where[] = array( "attach_file LIKE ( CONCAT( '%', ?, '%' ) )", $search );
		}
		
		return Db::i()->select( 'COUNT(*)', 'core_attachments', $where )->first();
	}
	
	/**
	 * Get Files
	 *
	 * @param	Member	$member	The member
	 * @param	string|null	$search	The search term (or NULL for all)
	 * @param	string		$postKey	The post key
	 * @param	int			$page	Page
	 * @param	int			$limit	Number to get
	 * @return	array		array( 'Title' => array( 'http://www.example.com/file1.txt' => \IPS\File, 'http://www.example.com/file2.txt' => \IPS\File, ... ), ... )
	 */
	public function get( Member $member, ?string $search, string $postKey, int $page, int $limit ): array
	{
		$where = array(
			array( "attach_member_id=?", $member->member_id ),
		);
		if ( $postKey )
		{
			$where[] = array( 'attach_post_key<>?', $postKey );
		}
		if ( $search )
		{
			$where[] = array( "attach_file LIKE ( CONCAT( '%', ?, '%' ) )", $search );
		}
		
		$return = array();
		foreach ( Db::i()->select( 'core_attachments.*', 'core_attachments', $where, 'attach_date DESC', array( ( $page - 1 ) * $limit, $limit ) ) as $row )
		{			
			$url = Settings::i()->base_url . "applications/core/interface/file/attachment.php?id={$row['attach_id']}";
			if ( $row['attach_security_key'] )
			{
				$url .= "&key={$row['attach_security_key']}";
			}
			$obj = File::get( 'core_Attachment', $row['attach_location'] );
			$obj->originalFilename = $row['attach_file'];
			
			if( $row['attach_thumb_location'] )
			{
				$obj->attachmentThumbnailUrl = File::get( 'core_Attachment', $row['attach_thumb_location'] )->url;
			}

			$return[ $url ] = $obj;
		}
		
		return $return;
	}
	
	/**
	 * @brief	Loaded Extensions
	 */
	protected static array $loadedExtensions = array();
	
	/**
	 * @brief	Locations
	 */
	public static array $locations = array();
	
	/**
	 * Get locations
	 *
	 * @param	int	$attachId	The attachment ID
	 * @return	string
	 */
	public static function getLocations( int $attachId ) : string
	{
		if ( !isset( static::$locations[ $attachId ] ) )
		{
			static::$locations[ $attachId ] = array();
			
			$select = Db::i()->select( '*', 'core_attachments_map', array( 'attachment_id=?', $attachId ) );
			$count = $select->count();
			foreach ( $select as $map )
			{				
				if ( !isset( static::$loadedExtensions[ $map['location_key'] ] ) )
				{
					$exploded = explode( '_', $map['location_key'] );
					try
					{
						$extensions = Application::load( $exploded[0] )->extensions( 'core', 'EditorLocations' );
						if ( isset( $extensions[ $exploded[1] ] ) )
						{
							static::$loadedExtensions[ $map['location_key'] ] = $extensions[ $exploded[1] ];
						}
					}
					catch ( OutOfRangeException | UnexpectedValueException $e ) { }
				}
				
				if ( isset( static::$loadedExtensions[ $map['location_key'] ] ) )
				{

					if( method_exists( static::$loadedExtensions[$map['location_key']], 'attachmentLookup'))
					{
						try {
							$url = static::$loadedExtensions[$map['location_key']]->attachmentLookup($map['id1'], $map['id2'], $map['id3']);

							/* Test url() method to prevent BadMethodCallException from the template below - an attachment may be
								located within a Node class that doesn't support urls, such as CMS Blocks. */

							if ($url instanceof Content or $url instanceof Model){
								$url->url();
							}

							static::$locations[$attachId][] = $url;
						} catch (LogicException | BadMethodCallException $e) {
						}
					}
				}
			}
		}
		
		return Theme::i()->getTemplate( 'members', 'core', 'global' )->attachmentLocations( static::$locations[ $attachId ] );
	}
}