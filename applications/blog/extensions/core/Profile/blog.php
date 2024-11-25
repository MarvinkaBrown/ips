<?php
/**
 * @brief		Profile extension: Blogs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		02 Apr 2014
 */

namespace IPS\blog\extensions\core\Profile;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Blog\ProfileTable;
use IPS\Db;
use IPS\Extensions\ProfileAbstract;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Profile extension: Blogs
 */
class Blog extends ProfileAbstract
{
	/**
	 * Is there content to display?
	 *
	 * @return	bool
	 */
	public function showTab(): bool
	{
		$where = array(
			array( '(' . Db::i()->findInSet( 'blog_groupblog_ids', $this->member->groups ) . ' OR ' . 'blog_member_id=? )', $this->member->member_id ),
			array( 'blog_disabled=0' )
		);
		
		if ( Member::loggedIn()->member_id )
		{
			$where[] = array( '( blog_social_group IS NULL OR blog_member_id=? OR blog_social_group IN(?) )', Member::loggedIn()->member_id, Db::i()->select( 'group_id', 'core_sys_social_group_members', array( 'member_id=?', Member::loggedIn()->member_id ) ) );
		}
		else
		{
			$where[] = array( 'blog_social_group IS NULL' );
		}
		
		return (bool) Db::i()->select( 'COUNT(*)', 'blog_blogs', $where )->first();
	}
	
	/**
	 * Display
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$table = new ProfileTable( $this->member->url() );
		$table->setOwner( $this->member );
		$table->tableTemplate	= array( Theme::i()->getTemplate( 'global', 'blog' ), 'profileBlogTable' );
		$table->rowsTemplate		= array( Theme::i()->getTemplate( 'global', 'blog' ), 'profileBlogRows' );
		
		return (string) $table;
	}
}