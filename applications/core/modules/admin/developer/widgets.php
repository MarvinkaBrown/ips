<?php
/**
 * @brief		widgets
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use IPS\Developer\Controller;
use IPS\Output;
use IPS\Widget;
use function defined;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * widgets
 */
class widgets extends Controller
{
	/**
	 * @var bool
	 */
	public static bool $csrfProtected = true;

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->output = Widget::devTable(
			ROOT_PATH . "/applications/{$this->application->directory}/data/widgets.json",
			$this->url,
			ROOT_PATH . "/applications/{$this->application->directory}/widgets",
			$this->application->directory,
			$this->application->directory,
			$this->application->directory
		);
	}
}