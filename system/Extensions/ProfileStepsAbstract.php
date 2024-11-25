<?php

/**
 * @brief        ProfileStepsAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/20/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\ProfileStep;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class ProfileStepsAbstract
{
	/**
	 * Available Actions to complete steps
	 *
	 * @return	array	array( 'key' => 'lang_string' )
	 */
	abstract public static function actions(): array;

	/**
	 * Available sub actions to complete steps
	 *
	 * @return	array	array( 'key' => 'lang_string' )
	 */
	abstract public static function subActions(): array;

	/**
	 * Can the actions have multiple choices?
	 *
	 * @param	string		$action		Action key (basic_profile, etc)
	 * @return	bool|null
	 */
	public static function actionMultipleChoice( string $action ): ?bool
	{
		return FALSE;
	}

	/**
	 * Has a specific step been completed?
	 *
	 * @param	ProfileStep	    $step   The step to check
	 * @param	Member|NULL		$member The member to check, or NULL for currently logged in
	 * @return	bool
	 */
	abstract public function completed( ProfileStep $step, ?Member $member = NULL ): bool;

	/**
	 * Can be set as required?
	 *
	 * @return	array
	 * @note	This is intended for items which have their own independent settings and dedicated enable pages, such as MFA and Social Login integration
	 */
	public static function canBeRequired(): array
	{
		return array();
	}

	/**
	 * Return all actions that can be reused
	 *
	 * @return array
	 */
	public static function allowMultiple() : array
	{
		return array();
	}

	/**
	 * Action URL
	 *
	 * @param	string				$action The action
	 * @param	Member|NULL	$member The member, or NULL for currently logged in
	 * @return	Url|null
	 */
	public function url( string $action, ?Member $member = NULL ): ?Url
	{
		return null;
	}

	/**
	 * Post ACP Save
	 *
	 * @param	ProfileStep		$step   The step
	 * @param	array			$values Form Values
	 * @return	void
	 */
	public function postAcpSave( ProfileStep $step, array $values ) : void
	{

	}

	/**
	 * Format Form Values
	 *
	 * @param	array      $values The values from the form
	 * @param	Member     $member The member
	 * @param	Form       $form   The form
	 * @return	void
	 */
	public static function formatFormValues( array $values, Member $member, Form $form ) : void
	{

	}

	/**
	 * Wizard Steps
	 *
	 * @param	Member|null	$member	Member or NULL for currently logged in member
	 * @return	array|string
	 */
	abstract public static function wizard( ?Member $member = NULL ): array|string;

	/**
	 * Post Delete
	 *
	 * @param	ProfileStep $step		The step
	 * @return	void
	 */
	public function onDelete( ProfileStep $step ) : void
	{

	}
}