<?php
/**
 * @brief		Member ACP Profile - Content Statistics Tab: Gallery
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Nov 2017
 */

namespace IPS\gallery\extensions\core\MemberACPProfileContentTab;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\Block;
use IPS\Db;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output\Plugin\Filesize;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member ACP Profile - Content Statistics Tab: Gallery
 */
class Gallery extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$imageCount = Db::i()->select( 'COUNT(*)', 'gallery_images', array( 'image_member_id=?', $this->member->member_id ) )->first();
		$diskspaceUsed = Db::i()->select( 'SUM(image_file_size)', 'gallery_images', array( 'image_member_id=?', $this->member->member_id ) )->first();
		$numberOfViews = Db::i()->select( 'COUNT(*)', 'gallery_bandwidth', array( 'member_id=?', $this->member->member_id ) )->first();
		$bandwidthUsed = Db::i()->select( 'SUM(bsize)', 'gallery_bandwidth', array( 'member_id=?', $this->member->member_id ) )->first();
		
		$allImages = Db::i()->select( 'COUNT(*)', 'gallery_images' )->first();
		$totalFilesize = Db::i()->select( 'SUM(image_file_size)', 'gallery_images' )->first();
		$allBandwidth = Db::i()->select( 'COUNT(*)', 'gallery_bandwidth' )->first();
		$totalBandwidth = Db::i()->select( 'SUM(bsize)', 'gallery_bandwidth' )->first();
		
		return (string) Theme::i()->getTemplate( 'stats', 'gallery' )->information( $this->member, Theme::i()->getTemplate( 'global', 'core' )->definitionTable( array(
			'images_submitted'		=> 
				Member::loggedIn()->language()->addToStack( 'images_stat_of_total', FALSE, array( 'sprintf' => array(
				Member::loggedIn()->language()->formatNumber( $imageCount ),
				Member::loggedIn()->language()->formatNumber( ( ( $allImages ? ( 100 / $allImages ) : 0 ) * $imageCount ), 2 ) ) )
			),
			'gdiskspace_used'		=> 
				Member::loggedIn()->language()->addToStack( 'images_stat_of_total', FALSE, array( 'sprintf' => array(
				Filesize::humanReadableFilesize( $diskspaceUsed ? :0 ),
				Member::loggedIn()->language()->formatNumber( ( ( $totalFilesize ? ( 100 / $totalFilesize ) : 0 ) * $diskspaceUsed ), 2 ) ) )
			),
			'gaverage_filesize'		=> 
				Member::loggedIn()->language()->addToStack( 'images_stat_average' , FALSE, array( 'sprintf' => array(
				Filesize::humanReadableFilesize( Db::i()->select( 'AVG(image_file_size)', 'gallery_images', array( 'image_member_id=?', $this->member->member_id ) )->first() ? :0),
				Filesize::humanReadableFilesize( Db::i()->select( 'AVG(image_file_size)', 'gallery_images' )->first() ) ) ? :0)
			),
			'number_of_views'		=> 
				Member::loggedIn()->language()->addToStack( 'images_stat_of_total', FALSE, array( 'sprintf' => array(
				Member::loggedIn()->language()->formatNumber( $numberOfViews ),
				Member::loggedIn()->language()->formatNumber( ( ( $allBandwidth ? ( 100 / $allBandwidth ) : 0 ) * $imageCount ), 2 ) ))
			),
			'gallery_bandwidth_used'		=> 
				Member::loggedIn()->language()->addToStack( 'images_stat_of_total_link', FALSE, array( 'htmlsprintf' => array(
				Filesize::humanReadableFilesize( $bandwidthUsed ? :0 ),
				Member::loggedIn()->language()->formatNumber( ( ( $totalBandwidth ? ( 100 / $totalBandwidth ) : 0 ) * $bandwidthUsed ), 2 ),
				Theme::i()->getTemplate( 'stats', 'gallery' )->bandwidthButton( $this->member ) ) )
			)
		) ) );
	}
	
	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		$bandwidthChart = new Database( Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\gallery\\extensions\\core\\MemberACPProfileContentTab\\Gallery&id={$this->member->member_id}&chart=downloads&_graph=1" ), 'gallery_bandwidth', 'bdate', '', array( 'vAxis' => array( 'title' => Member::loggedIn()->language()->addToStack( 'filesize_raw_k' ) ) ), 'LineChart', 'daily' );
		$bandwidthChart->groupBy = 'bdate';
		$bandwidthChart->where[] = array( 'member_id=?', $this->member->member_id );
		$bandwidthChart->addSeries( Member::loggedIn()->language()->addToStack('bandwidth_use_gallery'), 'number', 'ROUND((SUM(bsize)/1024),2)', FALSE );
		return ( Request::i()->isAjax() and isset( Request::i()->_graph ) ) ? (string) $bandwidthChart : Theme::i()->getTemplate( 'stats', 'gallery' )->graphs( (string) $bandwidthChart );
	}
}