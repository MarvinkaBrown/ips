<?php
/**
 * @brief		topDownloads Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		09 Jan 2014
 */

namespace IPS\downloads\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\downloads\Category;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Theme;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * topDownloads Widget
 */
class topDownloads extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'topDownloads';
	
	/**
	 * @brief	App
	 */
	public string $app = 'downloads';
		


	/**
	 * @brief	Cache Expiration
	 * @note	We allow this cache to be valid for 48 hours
	 */
	public int $cacheExpiration = 172800;

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
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
 	{
		$form = parent::configuration( $form );
 		
		$form->add( new Number( 'number_to_show', $this->configuration['number_to_show'] ?? 5, TRUE ) );
		return $form;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$categories = array();

		foreach( Db::i()->select( 'perm_type_id', 'core_permission_index', array( 'app=? and perm_type=? and (' . Db::i()->findInSet( 'perm_' . Category::$permissionMap['read'], Member::loggedIn()->groups ) . ' OR ' . 'perm_' . Category::$permissionMap['read'] . '=? )', 'downloads', 'category', '*' ) ) as $category )
		{
			$categories[]	= $category;
		}

		if( !count( $categories ) )
		{
			return '';
		}

		foreach ( array( 'week' => 'P1W', 'month' => 'P1M', 'year' => 'P1Y', 'all' => NULL ) as $time => $interval )
		{			
			$where = array( array( 'file_cat IN(' . implode( ',', $categories ) . ')' ) );
			if ( $interval )
			{
				$where[] = array( 'dtime>?', DateTime::create()->sub( new DateInterval( $interval ) )->getTimestamp() );
			}
			
			$ids	= array();
			$cases	= array();

			foreach( Db::i()->select( 'dfid, count(*) AS downloads', 'downloads_downloads', $where, 'downloads DESC', $this->configuration['number_to_show'] ?? 5, array( 'dfid' ) )->join( 'downloads_files', 'dfid=file_id' ) as $download )
			{
				$ids[]		= $download['dfid'];
				$cases[]	= "WHEN file_id={$download['dfid']} THEN {$download['downloads']}";
			}

			if( count( $ids ) )
			{
				$$time = new ActiveRecordIterator(
					Db::i()->select(
						'*, CASE ' . implode( ' ', $cases ) . ' END AS file_downloads',
						'downloads_files',
						'file_id IN(' . implode( ',', $ids ) . ')',
						'file_downloads DESC'
					),
					'IPS\downloads\File'
				);
			}
			else
			{
				$$time = array();
			}
		}
		
		return $this->output( $week, $month, $year, $all );
	}
}