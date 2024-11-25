<?php
/**
 * @brief		Profile Completion Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Nov 2016
 */

namespace IPS\core\extensions\core\ProfileSteps;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\ProfileFields\Field;
use IPS\Data\Store;
use IPS\Db;
use IPS\Extensions\ProfileStepsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\ProfileStep;
use IPS\Output;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile fields extension
 */
class ProfileFields extends ProfileStepsAbstract
{
	/**
	 * Available parent actions to complete steps
	 *
	 * @return	array	array( 'key' => 'lang_string' )
	 */
	public static function actions(): array
	{
		$return = array();
		
		if ( Field::fieldData() )
		{
			$return['profile_fields'] = 'complete_profile_app__core_ProfileFields';
		}
		
		return $return;
	}
	
	/**
	 * Available sub actions to complete steps
	 *
	 * @return	array	array( 'key' => 'lang_string' )
	 */
	public static function subActions(): array
	{
		$return = array();
		
		foreach( Field::fieldData() AS $fieldData )
		{
			foreach( $fieldData AS $id => $field )
			{
				$field = Field::constructFromData( $field );
				if ( !$field->admin_only AND $field->member_edit )
				{
					$return['profile_fields'][ 'core_pfield_' . $field->_id ] = 'core_pfield_' . $field->_id;
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * Can the actions have multiple choices?
	 *
	 * @param	string		$action		Action key (basic_profile, etc)
	 * @return	bool|null
	 */
	public static function actionMultipleChoice( string $action ): ?bool
	{
		return TRUE;
	}

	/**
	 * Can be set as required?
	 *
	 * @return	array
	 * @note	This is intended for items which have their own independent settings and dedicated enable pages, such as MFA and Social Login integration
	 */
	public static function canBeRequired(): array
	{
		return array( 'profile_fields' );
	}
	
	/**
	 * Has a specific step been completed?
	 *
	 * @param	ProfileStep	$step	The step to check
	 * @param	Member|NULL		$member	The member to check, or NULL for currently logged in
	 * @return	bool
	 */
	public function completed( ProfileStep $step, Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		
		if ( ! $member->group['g_edit_profile'] )
		{
			/* Member has no permission to edit profile */
			return TRUE;
		}
		
		/* Does the member have any profile fields? */
		if ( ! count( $member->profileFields( Field::PROFILE_COMPLETION ) ) )
		{
			return FALSE;
		}
		
		$done = 0;
		foreach( $step->subcompletion_act as $item )
		{
			$fieldId = substr( $item, 12 );
			foreach( $member->profileFields( Field::PROFILE_COMPLETION, TRUE ) AS $group => $field )
			{
				foreach( $field AS $key => $value )
				{
					if ( $key == 'core_pfield_' . $fieldId )
					{
						if ( $value or $value === "0" )
						{
							$done++;
						}
					}
				}
			}
		}
		
		return ( $done === count( $step->subcompletion_act ) );
	}
	
	/**
	 * Action URL
	 *
	 * @param	string				$action	The action
	 * @param	Member|NULL	$member	The member, or NULL for currently logged in
	 * @return	Url|null
	 */
	public function url( string $action, Member $member = NULL ): ?Url
	{
		return Url::internal( "app=core&module=members&controller=profile&do=edit&id={$member->member_id}", 'front', 'edit_profile', $member->members_seo_name );
	}
	
	/**
	 * Post ACP Save
	 *
	 * @param	ProfileStep		$step	The step
	 * @param	array						$values	Form Values
	 * @return	void
	 */
	public function postAcpSave( ProfileStep $step, array $values ) : void
	{
		$subActions = static::subActions()['profile_fields'];
		
		/* If we are going to add a profile field to a step, or even require it, we need to make sure the actual field is updated */
		foreach( $subActions AS $key )
		{
			if ( in_array( $key, $values['step_subcompletion_act'] ) )
			{
				$fieldId = substr( $key, 12 );
				$update = array();
				$update['pf_show_on_reg'] = 1;
				$update['pf_not_null'] = $step->required;
				
				Db::i()->update( 'core_pfields_data', $update, array( "pf_id=?", $fieldId ) );
			}
		}
		
		unset( Store::i()->profileFields );
	}
	
	/**
	 * Format Form Values
	 *
	 * @param	array				$values	The form values
	 * @param	Member			$member	The member
	 * @param	Form	$form	The form object
	 * @return	void
	 */
	public static function formatFormValues( array $values, Member $member, Form $form ) : void
	{
		$profileFields = array();
		foreach ( Field::roots() as $field )
		{
			if ( isset( $values[ "core_pfield_{$field->_id}"] ) )
			{
				if( $field->required and ( $values[ "core_pfield_{$field->_id}" ] === NULL ) )
				{
					Output::i()->error( 'reg_required_fields', '1C223/5', 403, '' );
				}
				
				$helper = $field->buildHelper();
				$profileFields[ "field_{$field->_id}" ] = $helper::stringValue( $values[ "core_pfield_{$field->_id}" ] );
				
				if ( $helper instanceof Editor )
				{
					$field->claimAttachments( $member->member_id );
				}
			}
		}
		
		if ( count( $profileFields ) )
		{
			/* Use insert into ... on duplicate key update here to cover both cases where the row exists or does not exist */
			Db::i()->insert( 'core_pfields_content', array_merge( array( 'member_id' => $member->member_id ), $profileFields ), true );

			/* Track and sync the changed custom fields */
			$member->changedCustomFields = $profileFields;
			$member->save();
		}
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

		$profileFields = array();
		foreach( ProfileStep::loadAll() AS $step )
		{
			try
			{
				$values = Db::i()->select( '*', 'core_pfields_content', array( 'member_id = ?', $member->member_id ) )->first();

				foreach( $values as $k => $v )
				{
					if( $k == 'member_id' )
					{
						continue;
					}

					$profileFields[ 'core_p' . $k ] = $v;
				}
			}
			catch( UnderflowException $e ){}

			if ( $step->completion_act === 'profile_fields' AND ! $step->completed( $member ) )
			{
				$wizards[ $step->key ] = function( $data ) use ( $member, $step, $profileFields ) {
					$form = new Form( 'profile_profile_fields_' . $step->id, 'profile_complete_next' );
					
					foreach( $step->subcompletion_act as $item )
					{
						$id		= substr( $item, 12 );
						$field	= Field::loadWithMember( $id, NULL, NULL, $member );

						$value = $profileFields['core_pfield_' . $id] ?? NULL;

						if ( is_array( $value ) and $field->multiple )
						{
							$value = implode( ',', array_keys( explode( '<br>', $value ) ) );
						}
						
						$form->add( $field->buildHelper( $value ) );
					}
					
					if ( $values = $form->values() )
					{
						static::formatFormValues( $values, $member, $form );
						$member->save();
						
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
	 * @param	ProfileStep		$step	The step
	 * @return	void
	 */
	public function onDelete( ProfileStep $step ) : void
	{
		$subActions = static::subActions()['profile_fields'];

		$notChangeableFields = array(
			'CheckboxSet',
			'Radio'
		);

		/* If we are going to add a profile field to a step, or even require it, we need to make sure the actual field is updated */
		foreach( $subActions AS $key )
		{
			if ( in_array( $key, $step->subcompletion_act ) )
			{
				$fieldId = substr( $key, 12 );
				$update = array();
				
				try
				{
					$field 	= Field::load( $fieldId );
	
					if ( in_array( $field->type, $notChangeableFields ) )
					{
						/* reset the pf_not_null field to 0 if this field can't be set via the fields form */
						if ( $step->required )
						{
							$update['pf_not_null'] = 0;
							Db::i()->update( 'core_pfields_data', $update, array( "pf_id=?", $fieldId ) );
						}
					}
				}
				catch( OutOfRangeException $e ) { }
			}
		}

		unset( Store::i()->profileFields );
	}
	
	/**
	 * Resyncs when something external happens
	 *
	 * @param	ProfileStep		$step	The step
	 * @return void
	 */
	public function resync( ProfileStep $step ) : void
	{
		$subActions = array();
		
		foreach( $step->subcompletion_act as $item )
		{
			$fieldId = substr( $item, 12 );
			try
			{
				Field::load( $fieldId );
				$subActions[] = $item;
			}
			catch( OutOfRangeException $e )
			{
				/* No longer exists.. */
			}
		}
		
		if ( count( $subActions ) and count( $subActions ) != $step->subcompletion_act )
		{
			$step->subcompletion_act = $subActions;
			$step->save();
		}
		else if ( ! count( $subActions ) )
		{
			/* No fields left, so delete this */
			$step->delete();
		}
	 }
}