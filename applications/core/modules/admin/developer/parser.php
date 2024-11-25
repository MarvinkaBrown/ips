<?php
/**
 * @brief		parser
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		27 Mar 2024
 */

namespace IPS\core\modules\admin\developer;

use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Stack;
use IPS\Member;
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
 * parser
 */
class parser extends Controller
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
		$jsonFile = ROOT_PATH . "/applications/{$this->application->directory}/data/parser.json";
		$json = $this->_getJson( $jsonFile );

		$form = new Form( 'form' );
		$form->addMessage( Member::loggedIn()->language()->addToStack('editor_allowed_formmsg') );

		$form->add( new Stack( 'editor_allowed_classes', $json['css'] ?? [], false ) );
		$form->add( new Stack( 'editor_allowed_datacontrollers', $json['controllers'] ?? [], false ) );
		$form->add( new Stack( 'editor_allowed_iframe_bases', $json['iframe'] ?? [], false ) );
		if( $values = $form->values() )
		{
			$json = [
				'css' => $values['editor_allowed_classes'],
				'controllers' => $values['editor_allowed_datacontrollers'],
				'iframe' => $values['editor_allowed_iframe_bases']
			];

			$this->_writeJson( $jsonFile, $json );

			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}
}