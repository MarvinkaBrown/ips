<?php
/**
 * @brief		Contact Us extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		29 Sep 2016
 */

namespace IPS\core\extensions\core\ContactUs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Email as EmailClass;
use IPS\Extensions\ContactUsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Stack;
use IPS\Member;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Contact Us extension
 */
class Email extends ContactUsAbstract
{
	/**
	 * Process Form
	 *
	 * @param	Form		$form	    The form
	 * @param	array                   $formFields Additional Configuration Formfields
	 * @param	array                   $options    Type Radio Form Options
	 * @param	array                   $toggles    Type Radio Form Toggles
	 * @param	array                   $disabled   Type Radio Form Disabled Options
	 * @return	void
	 */
	public function process( Form &$form, array &$formFields, array &$options, array &$toggles, array &$disabled  ) : void
	{
		$options['contact_internal'] = 'contact_internal';
		$options['contact_emails'] = 'contact_emails';
		$toggles['contact_emails'] = array( 'contact_emails' );

		$formFields[] = new Stack( 'contact_emails', explode( ',', Settings::i()->contact_emails ), FALSE, array( 'stackFieldType' => 'Email', 'maxItems' => 5 ),NULL ,NULL ,NULL, 'contact_emails' );
	}

	/**
	 * Allows extensions to do something before the form is shown... e.g. add your own custom fields, or redirect the page
	 *
	 * @param	Form		$form	    The form
	 * @return	void
	 */
	public function runBeforeFormOutput( Form $form ) : void
	{

	}

	/**
	 * Handle the Form
	 *
	 * @param	array                   $values     Values from form
	 * @return	bool
	 */
	public function handleForm( array $values ) : bool
	{
		if ( Settings::i()->contact_type == 'contact_internal' OR Settings::i()->contact_type == 'contact_emails' )
		{
			$fromName = ( Member::loggedIn()->member_id ) ? Member::loggedIn()->name : $values['contact_name'];
			$fromEmail = ( Member::loggedIn()->member_id ) ? Member::loggedIn()->email : $values['email_address'];
			$content = $values['contact_text'];
			$referrer = $values['contact_referrer'] ?? NULL;

			if ( Settings::i()->contact_type == 'contact_internal' )
			{
				$sender = Settings::i()->email_in;
			}
			else
			{
				$sender = explode( ',', Settings::i()->contact_emails );
			}
			$mail = EmailClass::buildFromTemplate( 'core', 'contact_form', array( Member::loggedIn(), $fromName, $fromEmail, $content, $referrer ), EmailClass::TYPE_TRANSACTIONAL );
			$mail->send( $sender , array(), array(), NULL, $fromName, array( 'Reply-To' => EmailClass::encodeHeader( $fromName, ( Member::loggedIn()->member_id ? Member::loggedIn()->email : $values['email_address'] ) ) ), FALSE );

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}


}