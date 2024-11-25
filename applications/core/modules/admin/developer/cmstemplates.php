<?php
/**
 * @brief		cmstemplates
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use IPS\cms\Templates;
use IPS\Developer\Controller;
use IPS\Output;
use function defined;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * cmstemplates
 */
class cmstemplates extends Controller
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
		$templateConfigFile = ROOT_PATH . "/applications/{$this->application->directory}/dev/cmsTemplates.json";
		$preSelectedTemplates = array( 'templates_database' => [], 'templates_block' => [], 'templates_page' => [] );

		if( file_exists( $templateConfigFile ) )
		{
			$preSelectedTemplates = json_decode( file_get_contents( $templateConfigFile ), TRUE );
		}

		$form = Templates::exportForm( TRUE, $preSelectedTemplates );

		if( $values = $form->values() )
		{
			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/dev/cmsTemplates.json", $values );
		}

		Output::i()->output = (string) $form;
	}
}