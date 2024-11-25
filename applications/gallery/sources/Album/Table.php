<?php
/**
 * @brief		Table Builder for Gallery albums
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		17 Mar 2014
 */

namespace IPS\gallery\Album;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\gallery\Album;
use IPS\gallery\Image;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Table Builder for Gallery albums
 */
class Table extends Content
{
	/**
	 * @brief	Container
	 */
	protected ?Model $container = NULL;

	/**
	 * @brief	Additional CSS classes to apply to columns
	 */
	public array $classes = array( 'cGalleryAlbums' );

	/**
	 * @brief	Pagination parameter
	 */
	protected string $paginationKey	= 'albumPage';

	/**
	 * @brief	Table resort parameter
	 */
	public string $resortKey			= 'albumResort';
	
	/**
	 * @brief	No Moderate
	 */
	public bool $noModerate = TRUE;

	/**
	 * @brief	Where clauses
	 */
	public ?array $where;

	/**
	 * Constructor
	 *
	 * @param	Url|NULL		$url			Base URL (defaults to container URL)
	 * @param	Model|NULL	$container		The container
	 * @return	void
	 */
	public function __construct( Url $url=NULL, Model $container=NULL )
	{
		/* Set container */
		if ( $container !== NULL )
		{
			if ( !$this->sortBy and $container->_sortBy )
			{
				$this->sortBy = Album::$databasePrefix . $container->_sortBy;
				$this->sortDirection = $container->_sortOrder;
			}
			
			if ( !$this->filter )
			{
				$this->filter = $container->_filter;
			}
			
			if ( $this->sortBy === 'album_name' )
			{
				$this->sortDirection = 'asc';
			}
		}

		/* Init */
		parent::__construct( '\\IPS\\gallery\\Album\\Item', ( $url !== NULL ) ? $url : $container->url(), NULL, $container );
		
		$this->rowsTemplate = array( Theme::i()->getTemplate( 'browse', 'gallery' ), 'albums' );
		
		/* If we can't moderate in this category, restrict results */
		if( $container === NULL OR !Image::modPermission( 'edit', NULL, $container ) )
		{
			if( count( Member::loggedIn()->socialGroups() ) )
			{
				$this->where[]	= array( '( album_type=1 OR ( album_type=2 AND album_owner_id=? ) OR ( album_type=3 AND ( album_owner_id=? OR ( album_allowed_access IS NOT NULL AND album_allowed_access IN(' . implode( ',', Member::loggedIn()->socialGroups() ) . ') ) ) ) )', Member::loggedIn()->member_id, Member::loggedIn()->member_id );
			}
			else
			{
				$this->where[]	= array( '( album_type=1 OR ( album_type IN (2,3) AND album_owner_id=? ) )', Member::loggedIn()->member_id );
			}
		}
		else
		{
			$this->where[]	= array( 'album_type<>4' );
		}
		
		/* Set available sort options */
		foreach ( array( 'name', 'count_comments', 'count_imgs' ) as $k ) 
		{
			if( $k == 'count_comments' AND ( $container === NULL OR !$this->container->allow_comments ) )
			{
				continue;
			}

			$this->sortOptions[ 'album_' . $k ] = 'album_' . $k;
		}

		unset( $this->sortOptions['date'] );
		unset( $this->sortOptions['title'] );

		if ( !$this->sortBy )
		{
			$this->sortBy = 'album_last_img_date';
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
		$this->where[]	= array( 'album_owner_id=?', $member->member_id );
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
	 * Does the user have permission to use the multi-mod checkboxes?
	 *
	 * @param string|null $action		Specific action to check (hide/unhide, etc.) or NULL for a generic check
	 * @return	bool
	 */
	public function canModerate( string $action=NULL ): bool
	{
		return FALSE;
	}

	/**
	 * Return the sort direction to use for links
	 *
	 * @note	Abstracted so other table helper instances can adjust as needed
	 * @param string $column		Sort by string
	 * @return	string [asc|desc]
	 */
	public function getSortDirection( string $column ): string
	{
		if( $column == 'album_name' )
		{
			return 'asc';
		}

		return 'desc';
	}
}