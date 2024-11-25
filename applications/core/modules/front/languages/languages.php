<?php
/**
 * @brief		languages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		30 Apr 2024
 */

namespace IPS\core\modules\front\languages;

use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * languages
 */
class languages extends \IPS\core\modules\admin\languages\languages
{

	/**
	 * @var string[] Allowed endpoints in the parent controller
	 */
	protected static array $allowedEndpoints = ['addWord'];

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !in_array( Request::i()->do, static::$allowedEndpoints ) )
		{
			Output::i()->error( 'no_module_permission', '2S107/2', 403, '' );
		}

		parent::execute();
	}
}