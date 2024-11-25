<?php

/**
 * @brief        FormsAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        1/18/2024
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class FormsAbstract
{
	/**
	 * Identifies the form that will be extended.
	 * Valid values: registration|checkout
	 * @see Form::availableForExtension()
	 * @return string
	 */
	abstract public static function formType() : string;

	/**
	 * Return an array of fields that will be added to the form
	 * Additional parameters will be passed in depending on the form type
	 * registration: no additional parameters
	 * checkout: currently logged in member, current invoice
	 *
	 * @return array
	 */
	abstract public function formElements() : array;

	/**
	 * Handle the field values on save
	 * Additional parameters will be passed in depending on the form type
	 * registration: newly created member
	 * checkout: currently logged in member, current invoice
	 *
	 * @param array $values
	 * @return void
	 */
	abstract public function processFormValues( array $values ) : void;
}