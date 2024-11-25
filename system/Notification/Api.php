<?php
/**
 * @brief		Provides notifications results in  API-friendly format
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 July 2014
 */

namespace IPS\Notification;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * API Notification Model
 */
class Api extends Inline
{
	/**
	 * Get sent time
	 *
	 * @return	int
	 */
	public function get_sent(): int
	{
		return $this->_data['sent_time'];
	}
	
	/**
	 * Get updated time
	 *
	 * @return	int
	 */
	public function get_updated(): int
	{
		return $this->_data['updated_time'];
	}

	/**
	 * Get notification app
	 *
	 * @return	string
	 */
	public function get_app(): string
	{
		return $this->_data['notification_app'];
	}
}