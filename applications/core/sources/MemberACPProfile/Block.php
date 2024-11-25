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

use IPS\Member;
use IPS\Output;
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
abstract class Block
{
	/**
	 * @brief	Member
	 */
	protected ?Member $member = null;
	
	/**
	 * Constructor
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function __construct( Member $member )
	{
		$this->member = $member;
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public static function title() : string
	{
		$class = get_called_class();
		$exploded = explode( '\\', $class );
		return Member::loggedIn()->language()->addToStack( 'memberACPProfileTitle_' . $exploded[1] . '_' . $exploded[5] );
	}
	
	/**
	 * Get Output
	 *
	 * @return	string
	 */
	abstract public function output() : string;
	
	/**
	 * Edit Window
	 *
	 * @return	string|null
	 */
	public function edit()  : ?string
	{
		Output::i()->error( 'node_error', '2C114/T', 404, '' );
	}
}