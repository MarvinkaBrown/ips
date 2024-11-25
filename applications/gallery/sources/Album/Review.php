<?php
/**
 * @brief		Album Review Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		17 Mar 2017
 */

namespace IPS\gallery\Album;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Content\EditHistory;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Hideable;
use IPS\Content\Reactable;
use IPS\Content\Reportable;
use IPS\Content\Review as ContentReview;
use IPS\Content\Shareable;
use IPS\Http\Url;
use IPS\Http\Url\Exception;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Album Review Model
 */
class Review extends ContentReview implements Embeddable,
	Filter
{
	use	Reactable,
		Reportable,
		Shareable,
		EditHistory,
		Hideable;

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static ?string $itemClass = 'IPS\gallery\Album\Item';

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'gallery_album_reviews';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'review_';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'				=> 'album_id',
		'author'			=> 'author',
		'author_name'		=> 'author_name',
		'content'			=> 'content',
		'date'				=> 'date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_member_name'	=> 'edit_member_name',
		'edit_show'			=> 'edit_show',
		'rating'			=> 'rating',
		'votes_total'		=> 'votes_total',
		'votes_helpful'		=> 'votes_helpful',
		'votes_data'		=> 'votes_data',
		'approved'			=> 'approved',
		'author_response'	=> 'author_response',
	);

	/**
	 * @brief	Application
	 */
	public static string $application = 'gallery';

	/**
	 * @brief	Title
	 */
	public static string $title = 'gallery_album_review';

	/**
	 * @brief	Icon
	 */
	public static string $icon = 'images';

	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'gallery-album-review';

	/**
	 * Get URL for doing stuff
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 * @throws	BadMethodCallException
	 * @throws	Exception
	 */
	public function url( ?string $action='find' ): Url
	{
		return parent::url( $action )->setQueryString( 'tab', 'reviews' );
	}

	/**
	 * Do stuff after creating (abstracted as comments and reviews need to do different things)
	 *
	 * @return	void
	 */
	public function postCreate(): void
	{
		parent::postCreate();

		$this->item()->asNode()->setLastReview( $this );
		$this->item()->asNode()->save();
	}

	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		return 'album_review';
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'gallery', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'gallery' )->embedAlbumReview( $this, $this->item(), $this->url()->setQueryString( $params ) );
	}

	/**
	 * Get the container item class to use for mod permission checks
	 *
	 * @return	string|NULL
	 * @note	By default we will return NULL and the container check will execute against Node::$contentItemClass, however
	 *	in some situations we may need to override this (i.e. for Gallery Albums)
	 */
	protected static function getContainerModPermissionClass(): ?string
	{
		return 'IPS\gallery\Album\Item';
	}
}