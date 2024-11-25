<?php
/**
 * @brief		downloadStats Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		09 Jan 2014
 */

namespace IPS\downloads\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\downloads\File;
use IPS\Output;
use IPS\Theme;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * downloadStats Widget
 */
class downloadStats extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'downloadStats';
	
	/**
	 * @brief	App
	 */
	public string $app = 'downloads';
		


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

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$stats = Db::i()->select( 'COUNT(*) AS totalFiles, SUM(file_comments) AS totalComments, SUM(file_reviews) AS totalReviews', 'downloads_files', array( "file_open=?", 1 ) )->first();
		$stats['totalAuthors'] = Db::i()->select( 'COUNT(DISTINCT file_submitter)', 'downloads_files' )->first();
		
		$latestFile = NULL;
		foreach ( File::getItemsWithPermission( array(), NULL, 1 ) as $latestFile )
		{
			break;
		}
		
		return $this->output( $stats, $latestFile );
	}
}