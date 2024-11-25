<?php
/**
 * @brief		Report Comment Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Jul 2013
 */

namespace IPS\core\Reports;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Comment as ContentComment;
use IPS\Http\Url;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Report Comment Model
 */
class Comment extends ContentComment
{
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_rc_comments';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = '';
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static string $itemClass = 'IPS\core\Reports\Report';
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'core';
	
	/**
	 * @brief	[Content\Comment]	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'			=> 'rid',
		'date'			=> 'comment_date',
		'content'		=> 'comment',
		'author'		=> 'comment_by',
		'author_name'	=> 'author_name',
		'ip_address'	=> 'ip_address',
	);
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'report_comment';

	/**
	 * Get mapped value
	 *
	 * @param string $key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( string $key ): mixed
	{
		/* Get the reported content items title */
		if ( $key === 'title' )
		{
			return $this->item()->mapped( 'title' );
		}

		return parent::mapped( $key );
	}

	/**
	 * Can view this entry
	 *
	 * @param Member|NULL $member		The member or NULL for currently logged in member.
	 * @return	bool
	 */
	public function canView( Member|null $member = null ): bool
	{
		$member = $member ?: Member::loggedIn();

		$return = parent::canView( $member );

		if( $return AND $this->item()->canView( $member ) )
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Get URL for doing stuff
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 */
	public function url( ?string $action='find' ): Url
	{
		$url = parent::url( $action );
		$idColumn = static::$databaseColumnId;
		
		if ( isset( $url->queryString['do'] ) )
		{
			return $url->stripQueryString( 'do' )->setQueryString( array( 'action' => $url->queryString['do'], 'comment' => $this->$idColumn ) );
		}
		return $url;
	}

	/**
	 * @brief	Value to set for the 'tab' parameter when redirecting to the comment (via _find())
	 */
	public static ?array $tabParameter	= array( 'activeTab' => 'comments' );
}
