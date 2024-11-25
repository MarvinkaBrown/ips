<?php
/**
 * @brief		subscriptions Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	nexus
 * @since		16 Feb 2018
 */

namespace IPS\nexus\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use IPS\nexus\Subscription;
use IPS\Widget;
use IPS\Widget\Customizable;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * subscriptions Widget
 */
class subscriptions extends Widget implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'subscriptions';
	
	/**
	 * @brief	App
	 */
	public string $app = 'nexus';
	
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
		/* If we already have one, don't show it */
		if ( Subscription::loadByMember( Member::loggedIn(), true ) )
		{
			return '';
		}
		
		return $this->output();
	}
}