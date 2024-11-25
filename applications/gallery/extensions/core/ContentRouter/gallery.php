<?php
/**
 * @brief		Content Router extension: Gallery
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Extensions\ContentRouterAbstract;
use IPS\gallery\Album\Item;
use IPS\gallery\Application;
use IPS\gallery\Image;
use IPS\gallery\Image\Table;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Router extension: Gallery
 */
class Gallery extends ContentRouterAbstract
{	
	/**
	 * @brief	Item Classes for embed only
	 */
	public array $embeddableContent = array( 'IPS\gallery\Album\Item' );
	
	/**
	 * @brief	Can be shown in similar content
	 */
	public bool $similarContent = TRUE;

	/**
	 * Constructor
	 *
	 * @param Member|Group|null $member If checking access, the member/group to check for, or NULL to not check access
	 */
	public function __construct( Member|Group $member = NULL )
	{
		if ( $member === NULL or $member->canAccessModule( Module::get( 'gallery', 'gallery', 'front' ) ) )
		{
			$this->classes[] = Image::class;
			$this->classes[] = Item::class;
		}
	}
	
	/**
	 * @brief	Owned Node Classes
	 */
	public array $ownedNodes = array( 'IPS\gallery\Album' );

	/**
	 * Use a custom table helper when building content item tables
	 *
	 * @param string $className The content item class
	 * @param Url $url The URL to use for the table
	 * @param array $where Custom where clause to pass to the table helper
	 * @return Table|null Custom table helper class to use
	 */
	public function customTableHelper( string $className, Url $url, array $where=array() ): ?Content
	{
		if( !in_array( $className, $this->classes ) or $className == 'IPS\gallery\Album\Item' )
		{
			return new Content( $className, $url, $where );
		}

		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_browse.js', 'gallery' ) );
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_global.js', 'gallery' ) );

		Application::outputCss();

		$table = new Table( $className, $url, $where, NULL, FALSE );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'browse', 'gallery' ), 'imageTable' );

		/* Get rows template */
		if( isset( Request::i()->cookie['thumbnailSize'] ) AND Request::i()->cookie['thumbnailSize'] == 'large' AND Request::i()->controller != 'search' )
		{
			$table->rowsTemplate = array( Theme::i()->getTemplate( 'browse', 'gallery' ), 'tableRowsLarge' );
		}
		else if( isset( Request::i()->cookie['thumbnailSize'] ) AND Request::i()->cookie['thumbnailSize'] == 'rows' AND Request::i()->controller != 'search' )
		{
			$table->rowsTemplate = array( Theme::i()->getTemplate( 'browse', 'gallery' ), 'tableRowsRows' );
		}
		else
		{
			$table->rowsTemplate = array( Theme::i()->getTemplate( 'browse', 'gallery' ), 'tableRowsThumbs' );
		}	

		return $table;
	}
}