<?php
/**
 * @brief		Blog Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		03 Mar 2014
 */

namespace IPS\blog\modules\admin\blogs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Settings as SettingsClass;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Blog settings
 */
class settings extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'settings_manage', 'blog' );
		parent::execute();
	}

	/**
	 * Manage Blog Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$form = new Form;
		
		$form->add( new YesNo( 'blog_enable_rating', SettingsClass::i()->blog_enable_rating ) );
		$form->add( new YesNo( 'blog_enable_sidebar', SettingsClass::i()->blog_enable_sidebar ) );

		$form->addHeader('blog_settings_rss');
		$form->add( new YesNo( 'blog_allow_rssimport', SettingsClass::i()->blog_allow_rssimport ) );
		$form->add( new YesNo( 'blog_allow_rss', SettingsClass::i()->blog_allow_rss ) );
		
		if ( $form->values() )
		{
			$form->saveAsSettings();

			Session::i()->log( 'acplogs__blog_settings' );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('settings');
		Output::i()->output = $form;
	}
}