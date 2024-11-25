<?php
/**
 * @brief		downloadsReviewFeed Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		13 Jul 2015
 */

namespace IPS\downloads\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\WidgetComment;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * downloadsReviewFeed Widget
 */
class downloadsReviewFeed extends WidgetComment
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'downloadsReviewFeed';

	/**
	 * @brief	App
	 */
	public string $app = 'downloads';



	/**
	 * Class
	 */
	protected static string $class = 'IPS\downloads\File\Review';

	/**
	 * @brief	Moderator permission to generate caches on [optional]
	 */
	protected array $moderatorPermissions	= array( 'can_view_hidden_content', 'can_view_hidden_downloads_file_review' );

	/**
	 * Init the widget
	 *
	 * @return	void
	 */
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'downloads', 'front' ) );

		parent::init();
	}
}