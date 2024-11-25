<?php
/**
 * @brief		ACP Member Profile: Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\MemberACPProfile;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Theme;
use function defined;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Block
 */
abstract class LazyLoadingBlock extends Block
{
	/**
	 * Get Output
	 *
	 * @return	string
	 */
	public function output() : string
	{
		return Theme::i()->getTemplate('memberprofile')->lazyLoad( $this->member, get_called_class() );
	}
	
	/**
	 * Get Real Output
	 *
	 * @return	string
	 */
	abstract public function lazyOutput() : string;
}