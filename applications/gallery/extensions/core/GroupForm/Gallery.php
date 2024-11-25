<?php
/**
 * @brief		Admin CP Group Form
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery\extensions\core\GroupForm;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\GroupFormAbstract;
use IPS\gallery\Image;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Member\Group;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Group Form
 */
class Gallery extends GroupFormAbstract
{
	/**
	 * Process Form
	 *
	 * @param	Form		$form	The form
	 * @param	Group		$group	Existing Group
	 * @return	void
	 */
	public function process( Form $form, Group $group ) : void
	{
		if( $group->g_id != Settings::i()->guest_group )
		{
			$form->addHeader( 'gallery_album_permissions' );
			$form->add( new YesNo( 'g_create_albums', $group->g_create_albums, FALSE, array( 'togglesOn' => array( 'g_create_albums_private', 'g_create_albums_fo', 'g_album_limit', 'g_img_album_limit' ) ) ) );
			$form->add( new YesNo( 'g_create_albums_private', $group->g_create_albums_private, FALSE, array(), NULL, NULL, NULL, 'g_create_albums_private' ) );
			$form->add( new YesNo( 'g_create_albums_fo', $group->g_create_albums_fo, FALSE, array(), NULL, NULL, NULL, 'g_create_albums_fo' ) );
			$form->add( new Number( 'g_album_limit', $group->g_album_limit, FALSE, array( 'unlimited' => 0 ), NULL, NULL, NULL, 'g_album_limit' ) );
			$form->add( new Number( 'g_img_album_limit', $group->g_img_album_limit, FALSE, array( 'unlimited' => 0 ), NULL, NULL, NULL, 'g_img_album_limit' ) );
		}

		$form->addHeader( 'gallery_restrictions' );

		if( Settings::i()->gallery_use_watermarks )
		{
			$form->add( new Radio( 'g_download_original', $group->g_download_original, FALSE, array( 'options' => array(
				Image::DOWNLOAD_ORIGINAL_RAW			=> 'g_download_raw',
				Image::DOWNLOAD_ORIGINAL_WATERMARKED	=> 'g_download_watermarked',
				Image::DOWNLOAD_ORIGINAL_NONE			=> 'g_download_none'
			) ) ) );
		}
		else
		{
			$form->add( new YesNo( 'g_download_original', $group->g_download_original, FALSE ) );
		}

		$form->add( new YesNo( 'g_movies', $group->g_movies, FALSE, array( 'togglesOn' => array( 'g_movie_size' ) ) ) );
		$form->add( new Number( 'g_movie_size', $group->g_movie_size, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('gallery_suffix_kb'), 'g_movie_size' ) );
		$form->add( new Number( 'g_max_upload', $group->g_max_upload, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('gallery_suffix_kb') ) );

		$form->addMessage( 'gallery_requires_log' );
		$form->add( new Number( 'g_max_transfer', $group->g_max_transfer, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('gallery_suffix_kb_day') ) );
		$form->add( new Number( 'g_max_views', $group->g_max_views, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('gallery_suffix_day') ) );
	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	Group	$group	The group
	 * @return	void
	 */
	public function save( array $values, Group $group ) : void
	{
		if( $group->g_id != Settings::i()->guest_group )
		{
			$group->g_create_albums			= (int) $values['g_create_albums'];
			$group->g_create_albums_private	= (int) $values['g_create_albums_private'];
			$group->g_create_albums_fo		= (int) $values['g_create_albums_fo'];
			$group->g_album_limit			= (int) $values['g_album_limit'];
			$group->g_img_album_limit		= (int) $values['g_img_album_limit'];
		}

		$group->g_download_original		= (int) $values['g_download_original'];
		$group->g_movies				= (int) $values['g_movies'];
		$group->g_movie_size			= (int) $values['g_movie_size'];
		$group->g_max_upload			= (int) $values['g_max_upload'];
		$group->g_max_transfer			= (int) $values['g_max_transfer'];
		$group->g_max_views				= (int) $values['g_max_views'];
	}
}