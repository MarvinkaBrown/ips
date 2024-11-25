<?php

/**
 * @brief        CustomFieldExtensionAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        4/11/2024
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\CustomField;
use IPS\Helpers\Form;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class CustomFieldAbstract
{
	/**
	 * The value that will be used for the "field type"
	 * This must be unique
	 *
	 * @return string
	 */
	abstract public static function fieldType() : string;

	/**
	 * Language string for the title of the field type.
	 * Will be displayed in the "Field Type" dropdown list when
	 * creating a custom field
	 *
	 * @return string
	 */
	abstract public static function fieldTypeTitle() : string;

	/**
	 * The class that should be called to render the field
	 * in a form. This class should extend FormAbstract (or an existing field)
	 * @see CustomField::buildHelper()
	 *
	 * @return string
	 */
	abstract public static function formClass() : string;

	/**
	 * Can be overridden by individual extension implementations
	 * to allow for a field to be available only in certain connditions
	 *
	 * @return bool
	 */
	public static function isEnabled() : bool
	{
		return true;
	}

	/**
	 * The database field type that will be used when adding a
	 * column to the database table
	 *
	 * @return string
	 */
	public static function columnDefinition() : string
	{
		return "TEXT";
	}

	/**
	 * Does the change mean wiping the value?
	 *
	 * @param CustomField $field
	 * @param string $newType	The new type
	 * @return	bool
	 */
	public static function canKeepValueOnChange( CustomField $field, string $newType ): bool
	{
		return $newType == $field->type;
	}

	/**
	 * A list of form elements that will be toggled on when this field
	 * type is selected
	 * @see CustomField::$additionalFieldToggles
	 *
	 * @return array
	 */
	public static function fieldTypeToggles() : array
	{
		return [];
	}

	/**
	 * Add fields to the field creation form
	 *
	 * @param Form $form
	 * @param CustomField	$field	The field currently being modified
	 * @return void
	 */
	public static function form( Form $form, CustomField $field ) : void
	{

	}

	/**
	 * Process values from the ACP Custom Field form
	 *
	 * @param array $values
	 * @return array
	 */
	public static function formatFormValues( array $values ) : array
	{
		return $values;
	}

	/**
	 * Return any options that will be passed to the Form Element
	 *
	 * @param CustomField $field
	 * @return array
	 */
	public static function formHelperOptions( CustomField $field ) : array
	{
		return [];
	}

	/**
	 * Display Value
	 * @see CustomField::displayValue()
	 *
	 * @param CustomField $field
	 * @param mixed|null $value The value
	 * @param bool $showSensitiveInformation If TRUE, potentially sensitive data (like passwords) will be displayed - otherwise will be blanked out
	 * @param string|null $separator Used to separate items when displaying a field with multiple values.
	 * @return string|null
	 */
	abstract public static function displayValue( CustomField $field, mixed $value=NULL, bool $showSensitiveInformation=FALSE, string $separator=NULL ): ?string;
}