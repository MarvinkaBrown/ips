<?php
/**
 * @brief		contactus
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Sep 2016
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * contactus
 */
class contactus extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'contactus_manage' );
		parent::execute();
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$form = $this->_getConfigForm();

		if ( $values = $form->values(true) )
		{
			/* If we unselect 'everyone' and save, an empty string is passed through, so the default value is picked up in Settings::changeValues(),
				which is the 'everyone' preference...the end result is that everyone gets rechecked when you uncheck it. To counter that, we'll store an invalid
				value here. */
			if( $values['contact_access'] === '' )
			{
				$values['contact_access'] = '-1';
			}

			$form->saveAsSettings( $values );

			Session::i()->log( 'acplogs__contactus_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=contactus' ), 'saved' );

		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'r__contactus' );
		Output::i()->output	.= Theme::i()->getTemplate( 'global' )->block( '', $form );
	}

	/**
	 * Build the configuration form
	 *
	 * @return Form
	 */
	protected function _getConfigForm() : Form
	{
		$form = new Form;
		$options = array();
		$toggles = array();
		$disabled = array();
		$formFields = array();

		$form->add( new CheckboxSet( 'contact_access', ( Settings::i()->contact_access == '*' ) ? '*' : explode( ',', Settings::i()->contact_access ), FALSE, array(
			'options' 	=> array_combine( array_keys( Group::groups() ), array_map( function( $_group ) { return (string) $_group; }, Group::groups() ) ),
			'multiple' 	=> true,
			'unlimited'		=> '*',
			'unlimitedLang'	=> 'everyone',
			'impliedUnlimited' => TRUE
		), NULL, NULL, NULL, 'contact_access' ) );

		/* Get extensions */
		$extensions = Application::allExtensions( 'core', 'ContactUs', FALSE, 'core', 'InternalEmail', TRUE );

		foreach ( $extensions as $k => $class )
		{
			$class->process( $form, $formFields, $options, $toggles, $disabled );
		}

		$form->add( new Radio( 'contact_type', Settings::i()->contact_type, FALSE, array(
			'options' => $options,
			'toggles' => $toggles,
			'disabled' => $disabled
		) ) );

		foreach ( $formFields AS $field )
		{
			$form->add( $field );
		}
		
		$form->add( new YesNo( 'contact_email_verify', Settings::i()->contact_email_verify, TRUE ) );

		return $form;
	}
	
	// Create new methods with the same name as the 'do' parameter which should execute it
}