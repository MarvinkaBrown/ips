<?php
/**
 * @brief		Profile extension: Gallery
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		02 Apr 2014
 */

namespace IPS\gallery\extensions\core\Profile;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\ProfileAbstract;
use IPS\gallery\Album\Table;
use IPS\gallery\Application;
use IPS\Member;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Profile extension: Gallery
 */
class gallery extends ProfileAbstract
{
	/**
	 * Is there content to display?
	 *
	 * @return	bool
	 */
	public function showTab(): bool
	{
		$where = array( array( 'album_owner_id=?', $this->member->member_id ) );
		
		if( count( Member::loggedIn()->socialGroups() ) )
		{
			$where[] = array( '( album_type=1 OR ( album_type=2 AND album_owner_id=? ) OR ( album_type=3 AND ( album_owner_id=? OR ( album_allowed_access IS NOT NULL AND album_allowed_access IN(' . implode( ',', Member::loggedIn()->socialGroups() ) . ') ) ) ) )', Member::loggedIn()->member_id, Member::loggedIn()->member_id );
		}
		else
		{
			$where[] = array( '( album_type=1 OR ( album_type IN (2,3) AND album_owner_id=? ) )', Member::loggedIn()->member_id );
		}

		$where[] = array( '(' . Db::i()->findInSet( 'core_permission_index.perm_view', Member::loggedIn()->groups ) . ' OR ' . 'core_permission_index.perm_view=? )', '*' );
		
		$select = Db::i()->select( 'COUNT(*)', 'gallery_albums', $where );
		$select->join( 'gallery_categories', array( "gallery_categories.category_id=gallery_albums.album_category_id" ) );
		$select->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=gallery_categories.category_id", 'gallery', 'category' ) );

		return (bool) $select->first();
	}
	
	/**
	 * Display
	 *
	 * @return	string
	 */
	public function render(): string
	{
		Application::outputCss();
		
		$table = new Table( $this->member->url()->setQueryString( 'tab', 'node_gallery_gallery') );
		$table->setOwner( $this->member );
		$table->limit = 10;
		$table->sortBy = 'album_last_img_date';
		$table->sortDirection = 'desc';
		$table->tableTemplate = array( Theme::i()->getTemplate( 'global', 'gallery' ), 'profileAlbumTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'browse', 'gallery' ), 'albums' );
		
		return (string) $table;
	}
}