<?php
/**
 * @brief		Manage Terms & Privacy Policy
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 Jun 2013
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * terms
 */
class terms extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'terms_manage' );
		parent::execute();
	}

	/**
	 * Manage Terms & Privacy Policy
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
 		$form = new Form;

 		static::buildForm( $form );
		
		if ( $values = $form->values() )
		{
			/* What were our previous values? */
			$existingPrivacyPolicy = iterator_to_array( Db::i()->select( 'word_custom', 'core_sys_lang_words', array( 'word_key=?', 'privacy_text_value' ) ) );
			$existingRegistrationTerms = iterator_to_array( Db::i()->select( 'word_custom', 'core_sys_lang_words', array( 'word_key=?', 'reg_rules_value' ) ) );
			
			/* Save */
			$values = static::processForm( $values );

			$form->saveAsSettings( $values );
			
			/* Log */
			Session::i()->log( 'acplogs__terms_edited' );
			
			/* Do we need to ask the admin if they want to ask members to reconfirm? */
			$changedPrivacyPolicy = $existingPrivacyPolicy != iterator_to_array( Db::i()->select( 'word_custom', 'core_sys_lang_words', array( 'word_key=?', 'privacy_text_value' ) ) );
			$changedRegistrationTerms = $existingRegistrationTerms != iterator_to_array( Db::i()->select( 'word_custom', 'core_sys_lang_words', array( 'word_key=?', 'reg_rules_value' ) ) );
			if ( $changedPrivacyPolicy or $changedRegistrationTerms )
			{
				Output::i()->redirect( Url::internal('app=core&module=settings&controller=terms&do=reconfirm')->setQueryString( array(
					'privacy'	=> intval( $changedPrivacyPolicy ),
					'reg'		=> intval( $changedRegistrationTerms )
				) ) );
			}
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_settings_terms');
		Output::i()->output	.= Theme::i()->getTemplate( 'global' )->block( 'menu__core_settings_terms', $form );
	}

	/**
	 * Process the text storage from submitting the form
	 *
	 * @param	array	$values		Values from form
	 * @return	array
	 */
	public static function processForm( array $values ) : array
	{
		foreach ( array( 'gl_guidelines' => 'guidelines_value', 'privacy_text' => 'privacy_text_value', 'reg_rules' => 'reg_rules_value', 'guest_terms_bar_text' => 'guest_terms_bar_text_value', 'cookie_3rdpartynotice' => 'cookie_3rdpartynotice_value' ) as $k => $v )
		{
			/* Guest terms bar text has special replacements that need to be swapped to sprintf parameters */
			if ( $k == 'guest_terms_bar_text' and $values['guest_terms_bar'] )
			{
				foreach( $values[ $k ] AS $lang_id => $text )
				{
					$values[ $k ][ $lang_id ] = str_replace( array( '{terms}', '{privacy}', '{guidelines}', '{cookies}' ), array( '%1$s', '%2$s', '%3$s', '%4$s' ), $text );
				}
			}
			
			Lang::saveCustom( 'core', $v, $values[ $k ] );
			unset( $values[ $k ] );
		}

		/* Update the essential cookie name list */
		unset( Store::i()->essentialCookieNames );

		return $values;
	}

	/**
	 * Build the form
	 *
	 * @param Form $form	Form to add settings to
	 * @return	void
	 */
	public static function buildForm( Form $form ) : void
	{
		$form->addHeader( 'terms_guidelines' );
		$form->add( new Radio( 'gl_type', Settings::i()->gl_type, FALSE, array(
				'options' => array(
						'internal' => 'gl_internal',
						'external' => 'gl_external',
						'none' => "gl_none" ),
				'toggles' => array(
						'internal'	=> array( 'gl_guidelines_id' ),
						'external'	=> array( 'gl_link' ),
						'none'		=> array(),
				)
		) ) );
		
		$form->add( new Translatable( 'gl_guidelines', NULL, FALSE, array( 'app' => 'core', 'key' => 'guidelines_value', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'Guidelines', 'attachIds' => array( NULL, NULL, 'gl_guidelines' ) ) ), NULL, NULL, NULL, 'gl_guidelines_id' ) );
		$form->add( new FormUrl( 'gl_link', Settings::i()->gl_link, FALSE, array(), NULL, NULL, NULL, 'gl_link'  ) );
		$form->addHeader( 'terms_privacy');
		$form->add( new Radio( 'privacy_type', Settings::i()->privacy_type, FALSE, array(
				'options' => array(
						'internal' => 'privacy_internal',
						'external' => 'privacy_external',
						'none' => "privacy_none" ),
				'toggles' => array(
						'internal'	=> array( 'privacy_text_id', 'privacy_show_processors' ),
						'external'	=> array( 'privacy_link' ),
						'none'		=> array(),
				)
		) ) );
		
		$form->add( new Translatable( 'privacy_text', NULL, FALSE, array( 'app' => 'core', 'key' => 'privacy_text_value', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'Privacy', 'attachIds' => array( NULL, NULL, 'privacy_text' ) ) ), NULL, NULL, NULL, 'privacy_text_id' ) );
		$form->add( new YesNo( 'privacy_show_processors', Settings::i()->privacy_show_processors, FALSE, array(), NULL, NULL, NULL, 'privacy_show_processors' ) );
		
		$form->add( new FormUrl( 'privacy_link', Settings::i()->privacy_link, FALSE, array(), NULL, NULL, NULL, 'privacy_link' ) );
			
		$form->addHeader( 'terms_registration' );
		$form->add( new Translatable( 'reg_rules', NULL, FALSE, array( 'app' => 'core', 'key' => 'reg_rules_value', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'RegistrationRules', 'attachIds' => array( NULL, NULL, 'reg_rules' ) ) ), NULL, NULL, NULL, 'reg_rules_id' ) );
		
		$form->addHeader( 'guest_terms_options' );
		$form->add( new YesNo( 'guest_terms_bar', Settings::i()->guest_terms_bar, FALSE, array( 'togglesOn' => array( 'guest_terms_bar_text' ) ) ) );
		$form->add( new Translatable( 'guest_terms_bar_text', NULL, FALSE, array( 'app' => 'core', 'key' => 'guest_terms_bar_text_value', 'sprintf' => array( '{terms}', '{privacy}', '{guidelines}', '{cookies}' ) ), NULL, NULL, NULL, 'guest_terms_bar_text' ) );

		$form->addHeader( 'terms_cookies' );
		$form->add( new Translatable( 'cookie_3rdpartynotice', NULL, FALSE, array( 'app' => 'core', 'key' => 'cookie_3rdpartynotice_value', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => '3rdPartyCookies', 'attachIds' => array( NULL, NULL, 'privacy_text' ) ) ) ));
	}
	
	/**
	 * Ask the admin if they want users to re-confirm
	 *
	 * @return	void
	 */
	protected function reconfirm() : void
	{
		$form = new Form;
		
		$form->addMessage( 'admin_reconfirm_blurb' );
				
		if ( Request::i()->reg )
		{
			$form->add( new YesNo( 'admin_reconfirm_reg_terms', FALSE ) );
		}
		
		if ( Request::i()->privacy )
		{
			$form->add( new YesNo( 'admin_reconfirm_privacy', FALSE ) );
		}
		
		if ( $values = $form->values() )
		{
			if ( isset( $values['admin_reconfirm_reg_terms'] ) and $values['admin_reconfirm_reg_terms'] )
			{
				Member::updateAllMembers( array( "members_bitoptions2 = members_bitoptions2 | " . Member::$bitOptions['members_bitoptions']['members_bitoptions2']['must_reaccept_terms'] ) );
			}
			if ( isset( $values['admin_reconfirm_privacy'] ) and $values['admin_reconfirm_privacy'] )
			{
				Member::updateAllMembers( array( "members_bitoptions2 = members_bitoptions2 | " . Member::$bitOptions['members_bitoptions']['members_bitoptions2']['must_reaccept_privacy'] ) );
			}
			
			Output::i()->redirect( Url::internal('app=core&module=settings&controller=terms') );
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_settings_terms');
		Output::i()->output	= $form;
	}
}