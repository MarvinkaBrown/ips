<?php
/**
 * @brief		Contact Form
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Nov 2013
 */

namespace IPS\core\modules\front\contact;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Email;
use IPS\Email as EmailClass;
use IPS\Helpers\Form\Text;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Contact Form
 */
class contact extends Controller
{
	/**
	 * @brief	Is this for displaying "content"? Affects if advertisements may be shown
	 */
	public bool $isContentPage = FALSE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !Member::loggedIn()->canUseContactUs() )
		{
			Output::i()->error( 'no_module_permission', '2S333/1', 403, '' );
		}

		/* Execute */
		parent::execute();
	}
	
	/**
	 * Method
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Get extensions */
		$extensions = Application::allExtensions( 'core', 'ContactUs', FALSE, 'core', 'InternalEmail', TRUE );

		/* Don't let robots index this page, it has no value */
		Output::i()->metaTags['robots'] = 'noindex';
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';

		$form = new Form( 'contact', 'send' );
		$form->hiddenValues['contact_referrer'] = (string) Request::i()->referrer();
		$form->class = 'ipsForm--vertical ipsForm--contact';
		
		$form->add( new Editor( 'contact_text', NULL, TRUE, array(
				'app'			=> 'core',
				'key'			=> 'Contact',
				'autoSaveKey'	=> 'contact-' . Member::loggedIn()->member_id,
		) ) );
		
		if ( !Member::loggedIn()->member_id )
		{
			$form->add( new Text( 'contact_name', NULL, TRUE ) );
			$form->add( new Email( 'email_address', NULL, TRUE, array( 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL ) ) );
			if ( Settings::i()->bot_antispam_type !== 'none' and Settings::i()->guest_captcha )
			{
				$form->add( new Captcha );
			}
		}
		foreach ( $extensions as $k => $class )
		{
			$class->runBeforeFormOutput( $form );
		}
		
		if ( $values = $form->values() )
		{
			if ( ! Member::loggedIn()->member_id AND Settings::i()->contact_email_verify )
			{
				$key = Login::generateRandomString( 32 );
				Db::i()->insert( 'core_contact_verify', array(
					'email_address'		=> $values['email_address'],
					'contact_data'		=> json_encode( $values ),
					'verify_key'		=> $key
				), TRUE );
				
				$email = EmailClass::buildFromTemplate( 'core', 'contact_verify', array( $values['email_address'], $key ), EmailClass::TYPE_TRANSACTIONAL );
				$email->send( $values['email_address'] );
				
				if ( Request::i()->isAjax() )
				{
					Output::i()->json( 'OK' );
				}
				
				Output::i()->title = Member::loggedIn()->language()->addToStack( 'contact_verify' );
				Output::i()->output = Theme::i()->getTemplate( 'system' )->contactVerify();
				return;
			}
			
			foreach ( $extensions as $k => $class )
			{
				if ( $handled = $class->handleForm( $values ) )
				{
					break;
				}
			}

			if( Request::i()->isAjax() )
			{
				Output::i()->json( 'OK' );
			}

			Output::i()->title		= Member::loggedIn()->language()->addToStack( 'message_sent' );
			Output::i()->output	= Theme::i()->getTemplate( 'system' )->contactDone();
		}
		else
		{
			Output::i()->title		= Member::loggedIn()->language()->addToStack( 'contact' );
			Output::i()->output	= Theme::i()->getTemplate( 'system' )->contact( $form );
		}
	}
	
	/**
	 * Confirm
	 *
	 * @return	void
	 */
	protected function confirm() : void
	{
		/* Show interstitial page to prevent email clients from auto-verifying. */
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		
		$form = new Form( 'form', NULL );
		$form->class = 'ipsForm--vertical ipsForm--contact-confirm';
		$form->actionButtons[] = Theme::i()->getTemplate( 'forms', 'core', 'global' )->button( 'contact_click_to_verify', 'submit', null, 'ipsButton ipsButton_primary ipsButton_fullWidth', array( 'tabindex' => '2', 'accesskey' => 's' ) );
		$form->hiddenValues['email'] = Request::i()->email;
		$form->hiddenValues['key'] = Request::i()->key;
		
		if ( $values = $form->values() )
		{
			try
			{
				$verify = Db::i()->select( '*', 'core_contact_verify', array( "email_address=?", $values['email'] ) )->first();
			}
			catch( UnderflowException )
			{
				Output::i()->error( 'node_error', '2C435/1', 404, '' );
			}
			
			if ( Login::compareHashes( $verify['verify_key'], $values['key'] ) === FALSE )
			{
				Output::i()->error( 'contact_verify_key_mismatch', '2C435/2', 403, '' );
			}
			
			/* Send it */
			$extensions = Application::allExtensions( 'core', 'ContactUs', FALSE, 'core', 'InternalEmail', TRUE );
			
			foreach( $extensions AS $k => $extension )
			{
				if ( $extension->handleForm( json_decode( $verify['contact_data'], true ) ) )
				{
					break;
				}
			}
			
			Db::i()->delete( 'core_contact_verify', array( "email_address=?", $values['email'] ) );
			
			Output::i()->sidebar['enabled'] = FALSE;
			Output::i()->bodyClasses[] = 'ipsLayout_minimal';
			Output::i()->title		= Member::loggedIn()->language()->addToStack( 'message_sent' );
			Output::i()->output	= Theme::i()->getTemplate( 'system' )->contactDone();
			return;
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'contact_verify' );
		Output::i()->output = Theme::i()->getTemplate( 'system' )->contactConfirmVerify( $form );
	}
}