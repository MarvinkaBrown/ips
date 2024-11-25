<?php
/**
 * @brief		Profile Completion Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 Jun 2018
 */

namespace IPS\core\extensions\core\ProfileSteps;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\ProfileStepsAbstract;
use IPS\Helpers\Form;
use IPS\Lang;
use IPS\Member;
use IPS\Member\ProfileStep;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile Completion Extension
 */
class Editor extends ProfileStepsAbstract
{
	/**
	 * Available Actions to complete steps
	 *
	 * @return	array	array( 'key' => 'lang_string' )
	 */
	public static function actions(): array
	{
		return array( 'custom' => 'complete_profile_custom_editor' );
	}

	/**
	 * Available sub actions to complete steps
	 *
	 * @return	array	array( 'key' => 'lang_string' )
	 */
	public static function subActions(): array
	{
		return array( 'custom' => array( 'complete_profile_custom_editor' ) );
	}

	/**
	 * Can the actions have multiple choices?
	 *
	 * @param	string		$action		Action key (basic_profile, etc)
	 * @return	boolean|null
	 */
	public static function actionMultipleChoice( string $action ): ?bool
	{
		return NULL;
	}

	/**
	 * Return all actions that can be reused
	 *
	 * @return array
	 */
	public static function allowMultiple() : array
	{
		return array( 'custom' );
	}
	
	/**
	 * Has a specific step been completed?
	 *
	 * @param	ProfileStep	$step   The step to check
	 * @param	Member|NULL		$member The member to check, or NULL for currently logged in
	 * @return	bool
	 */
	public function completed( ProfileStep $step, Member $member = NULL ): bool
	{
		$memberId = $member ? $member->member_id : Member::loggedIn()->member_id;

		return (bool) Db::i()->select( 'count(*)', 'core_profile_completion', array( 'member_id=? and step_id=?', $memberId, $step->id ) )->first();
	}
	
	/**
	 * Post ACP Save
	 *
	 * @param	ProfileStep		$step   The step
	 * @param	array						$values Form Values
	 * @return	void
	 */
	public function postAcpSave( ProfileStep $step, array $values ) : void
	{
		Lang::saveCustom( 'core', "profile_step_subaction_custom_" . $step->id, $values['step_subcompletion_act'] );
	}

	/**
	 * Wizard Steps
	 *
	 * @param	Member|NULL	$member	Member or NULL for currently logged in member
	 * @return	array|string
	 */
	public static function wizard( Member $member = NULL ): array|string
	{
		$member = $member ?: Member::loggedIn();
		$wizards = array();
		
		foreach( ProfileStep::loadAll() AS $step )
		{
			if ( $step->completion_act === 'custom' AND !$step->completed( $member ) )
			{
				$wizards[ $step->key ] = function( $data ) use ( $member, $step ) {
					$form = new Form( 'profile_profile_fields_' . $step->id, 'profile_complete_next' );
					
					$form->addHtml( $member->language()->addToStack( 'profile_step_subaction_custom_' . $step->id ) );
					
					/* Because there are no form elements, $values is an empty array - that's ok and means the form was submitted, while FALSE means it wasn't */
					if ( ( $values = $form->values() ) !== FALSE )
					{
						/* Store the flag that the step is done */
						Db::i()->insert( 'core_profile_completion', array( 'member_id' => $member->member_id, 'step_id' => $step->id, 'completed' => time() ) );

						return $values;
					}

					return $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'profileCompleteTemplate' ), $step );
				};
			}
		}

		if ( count( $wizards ) )
		{
			return $wizards;
		}

		return [];
	}

	/**
	 * Post Delete
	 *
	 * @param	ProfileStep $step		The step
	 * @return	void
	 */
	public function onDelete( ProfileStep $step ) : void
    {
    	Lang::deleteCustom( 'core', 'profile_step_subaction_custom' );
    }
}