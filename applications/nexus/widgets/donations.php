<?php
/**
 * @brief		donations Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		07 Apr 2015
 */

namespace IPS\nexus\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use IPS\Settings;
use IPS\Theme;
use IPS\Widget\Customizable;
use IPS\Widget\StaticCache;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * donations Widget
 */
class donations extends StaticCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'donations';
	
	/**
	 * @brief	App
	 */
	public string $app = 'nexus';

	/**
	 * Constructor
	 *
	 * @param String $uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param array|string|null $access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation			Orientation (top, bottom, right, left)
	 * @param string $layout
	 * @return	void
	 */
	public function __construct(string $uniqueKey, array $configuration, array|string $access=null, string $orientation=null, string $layout='table' )
	{
		parent::__construct( $uniqueKey, $configuration, $access, $orientation, $layout );

		$member = Member::loggedIn();

		$theme = $member->skin ?: Theme::defaultTheme();
		$this->cacheKey = "widget_{$this->key}_" . $this->uniqueKey . '_' . md5( json_encode( $configuration ) . "_" . $member->language()->id . "_" . $member->member_id ? 1 : 0 . "_" . $theme . "_" . $orientation );
	}

	/**
	 * Initialise this widget
	 *
	 * @return void
	 */ 
	public function init(): void
	{
		parent::init();
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		/* Do we have any donation goals to show? */
		if( !Settings::i()->donation_goals )
		{
			return "";
		}

		return $this->output();
	}
}