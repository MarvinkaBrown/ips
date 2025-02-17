<?php
/**
 * @brief		venues
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		24 Feb 2017
 */

namespace IPS\calendar\modules\admin\calendars;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Node\Model;
use IPS\Node\Controller;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * venues
 */
class venues extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\calendar\Venue';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'venues_manage' );
		parent::execute();
	}
	
	/**
	 * Allow overloading to change how the title is displayed in the tree
	 *
	 * @param	$node    Model    Node
	 * @return string
	 */
	protected static function nodeTitle( Model $node ): string
	{
		return $node->_title;
	}
}