<?php
/**
 * @brief		Facebook share link
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Sept 2013
 * @see			<a href='https://developers.facebook.com/docs/reference/plugins/like/'>Facebook like button documentation</a>
 */

namespace IPS\Content\ShareServices;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\ShareServices;
use IPS\core\ShareLinks\Service;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Member\Group;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Facebook share link
 */
class Facebook extends ShareServices
{
	/**
	 * Add any additional form elements to the configuration form. These must be setting keys that the service configuration form can save as a setting.
	 *
	 * @param	Form				$form		Configuration form for this service
	 * @param	Service	$service	The service
	 * @return	void
	 */
	public static function modifyForm( Form &$form, Service $service ): void
	{
		$form->add( new Select( 'fbc_bot_group', Settings::i()->fbc_bot_group, TRUE, array( 'options' => array_combine( array_keys( Group::groups() ), array_map( function( $_group ) { return (string) $_group; }, Group::groups() ) ) ) ) );
	}

	/**
	 * Return the HTML code to show the share link
	 *
	 * @return	string
	 */
	public function __toString(): string
	{
		return Theme::i()->getTemplate( 'sharelinks', 'core' )->facebook( urlencode( $this->url ) );
	}
}