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

use IPS\Extensions\ContactUsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Url;
use IPS\Output;
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
class RedirectForm extends ContactUsAbstract
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
		$formFields[] = new Url( 'contact_redirect', Settings::i()->contact_redirect, FALSE, array( ),NULL ,NULL ,NULL, 'contact_redirect' );
		$options['contact_redirect'] = 'contact_redirect';
		$toggles['contact_redirect'] = array( 'contact_redirect' );
	}

	/**
	 * Allows extensions to do something before the form is shown... e.g. add your own custom fields, or redirect the page
	 *
	 * @param	Form		$form	    The form
	 * @return	void
	 */
	public function runBeforeFormOutput( Form $form ) : void
	{
		if ( Settings::i()->contact_type == 'contact_redirect' AND Settings::i()->contact_redirect != '' )
		{
			Output::i()->redirect( Settings::i()->contact_redirect );
		}
	}

	/**
	 * Handle the Form
	 *
	 * @param	array                   $values     Values from form
	 * @return	bool
	 */
	public function handleForm( array $values ): bool
	{
		return FALSE;
	}

}