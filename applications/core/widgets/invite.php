<?php
/**
 * @brief		invite Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 Aug 2019
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;
use IPS\Widget;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * invite Widget
 */
class invite extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'invite';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
		


	/**
	 * Initialise this widget
	 *
	 * @return void
	 */
	public function init(): void
	{
		// Use this to perform any set up and to assign a template that is not in the following format:
		$this->template( array( Theme::i()->getTemplate( 'widgets', $this->app, 'front' ), $this->key ) );

		parent::init();
	}
 	
 	 /**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( array $values ): array
 	{
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$subject = Member::loggedIn()->language()->addToStack('block_invite_subject', FALSE, array( 'sprintf' => array( Settings::i()->board_name ), 'rawurlencode' => TRUE ) );
		$url = Url::internal( "" );

		if( Settings::i()->ref_on and Member::loggedIn()->member_id )
		{
			$url = $url->setQueryString( array( '_rid' => Member::loggedIn()->member_id  ) );
		}

		return $this->output( $subject, urlencode( $url ) );
	}
}