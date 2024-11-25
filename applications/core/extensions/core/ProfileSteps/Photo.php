<?php
/**
 * @brief		Profile Completiong Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Aug 2018
 */

namespace IPS\core\extensions\core\ProfileSteps;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Extensions\ProfileStepsAbstract;
use IPS\File;
use IPS\File\Exception as FileException;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Image;
use IPS\Log;
use IPS\Member;
use IPS\Member\ProfileStep;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\Widget;
use function count;
use function defined;
use function in_array;
use function is_array;
use const IPS\PHOTO_THUMBNAIL_SIZE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile Photo Profile Completition Extension
 */
class Photo extends ProfileStepsAbstract
{
	/* !Extension Methods */
	
	/**
	 * Available Actions to complete steps
	 *
	 * @return	array	array( 'key' => 'lang_string' )
	 */
	public static function actions(): array
	{
		return array( 'profile_photo' => 'complete_profile_photo' );

	}
	
	/**
	 * Available sub actions to complete steps
	 *
	 * @return	array	array( 'key' => 'lang_string' )
	 */
	public static function subActions(): array
	{
		/* Basic stuff */
		$return['profile_photo'] = array(
			'photo'			=> 'complete_profile_profile_photo',
			'cover_photo'	=> 'complete_profile_cover_photo'
		);
		
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
	 * @note	This is intended for items which have their own independent settings and dedicated enable pages, such as Social Login integration
	 */
	public static function canBeRequired() : array
	{
		return array( 'profile_photo' );
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
		if ( array_key_exists( 'pp_photo_type', $values ) )
		{
			$photoVars = explode( ':', $member->group['g_photo_max_vars'] );
			
			$member->pp_photo_type = $values['pp_photo_type'];
			
			switch ( $values['pp_photo_type'] )
			{
				case 'custom':
					if ( $photoVars[0] and $values['member_photo_upload'] )
					{
						if ( (string) $values['member_photo_upload'] !== '' )
						{
							$member->pp_photo_type  = 'custom';
							$member->pp_main_photo  = (string) $values['member_photo_upload'];
							$member->photo_last_update = time();
							$member->pp_thumb_photo = NULL;
						}
					}
					break;
	
				case 'none':
					$member->pp_main_photo		= NULL;
					$member->photo_last_update 	= NULL;

					/* This is a bit of a special case, but if you choose 'none' on the form and continue, the software may think this is the next step still, since we treat 'none' as not having a photo set. To work around that scenario, we need to set a flag that we just explicitly selected 'none' so we know to continue to the next step. */
					if( !isset( $_SESSION['profileCompletionData'] ) )
					{
						$_SESSION['profileCompletionData'] = array();
					}

					$_SESSION['profileCompletionData'][] = 'photo-none';
					break;
			}
			
			if ( $member->pp_photo_type )
			{
				$member->logHistory( 'core', 'photo', array( 'action' => 'new', 'type' => $member->pp_photo_type ) );
			}
			else
			{
				$member->logHistory( 'core', 'photo', array( 'action' => 'remove' ) );
			}
		}
		
		if ( array_key_exists( 'complete_profile_cover_photo', $values ) )
		{
			$photo = $member->coverPhoto();
			try
			{
				$photo->delete();
			}
			catch ( Exception $e ) { }
			
			/* Make sure profile sync services are disabled */
			$profileSync = $member->profilesync;
			if ( isset( $profileSync['cover'] ) )
			{
				unset( $profileSync['cover'] );
				$member->profilesync = $member;
				$member->save();
			}
			
			$newPhoto = new CoverPhoto( $values['complete_profile_cover_photo'], 0 );

			$member->pp_cover_photo = (string) $newPhoto->file;
			$member->pp_cover_offset = $newPhoto->offset;
			
			if ( $newPhoto->file )
			{
				$member->logHistory( 'core', 'coverphoto', array( 'action' => 'new' ) );
			}
			else
			{
				$member->logHistory( 'core', 'coverphoto', array( 'action' => 'remove' ) );
			}
		}
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
		if ( !$member->member_id )
		{
			return FALSE;
		}
		
		static::$_member = $member;
		static::$_step = $step;
		
		foreach( $step->subcompletion_act as $item )
		{
			if ( ! static::$_member->group['g_edit_profile'] )
			{
				/* Member has no permission to edit profile */
				return TRUE;
			}

			$method = 'completed' . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $item ) ) );

			if ( method_exists( $this, $method ) )
			{
				return static::$method();
			}
			else
			{
				Log::debug( "missing_profile_step_method", 'profile_completion' );
			}
		}

		return TRUE;
	}
	
	/**
	 * Wizard Steps
	 *
	 * @param	Member|NULL	$member	Member or NULL for currently logged in member
	 * @return	array|string
	 */
	public static function wizard( Member $member = NULL ): array|string
	{
		static::$_member = $member ?: Member::loggedIn();

		return static::wizardPhoto();
	}

	/**
	 * Extra Step - useful for steps that require additional input after save
	 *
	 * @param	Member|NULL	$member	Member or NULL for currently logged in member
	 * @return	array
	 */
	public static function extraStep( ?Member $member = NULL ) : array
	{
		static::$_member = $member ?: Member::loggedIn();

		$return = array();

		if( !static::completedCrop() )
		{
			$return = static::wizardCrop();
		}

		return $return;
	}

	/**
	 * Extra Step title
	 *
	 * @return	string
	 */
	public static function extraStepTitle() : string
	{
		return 'profile_step_title_crop';
	}
	
	/* !Completed Utility Methods */
	
	/**
	 * @brief	Member
	 */
	protected static ?Member $_member = NULL;
	
	/**
	 * @brief	Step
	 */
	protected static ?ProfileStep $_step = NULL;
	
	/**
	 * Added a photo?
	 *
	 * @return	bool
	 */
	protected static function completedPhoto() : bool
	{
		if ( !static::$_member->pp_photo_type )
		{
			return FALSE;
		}
		
		if ( static::$_member->pp_photo_type === 'none' )
		{
			/* We just specified 'none' so we should continue now and act as if this is done */
			if( isset( $_SESSION['profileCompletionData'] ) AND is_array( $_SESSION['profileCompletionData'] ) AND in_array( 'photo-none', $_SESSION['profileCompletionData'] ) )
			{
				return TRUE;
			}

			return FALSE;
		}
		
		if ( static::$_member->pp_photo_type === 'letter' )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Added a photo?
	 *
	 * @return	bool
	 */
	protected static function completedCrop() : bool
	{
		if ( !static::$_member->pp_thumb_photo )
		{
			return FALSE;
		}

		return TRUE;
	}
	
	/**
	 * Added a cover photo?
	 *
	 * @return	bool
	 */
	protected static function completedCoverPhoto() : bool
	{
		return (bool) static::$_member->pp_cover_photo;
	}
	
	/* !Wizard Utility Methods */
	
	/**
	 * Wizard: Basic Profile
	 *
	 * @return	array
	 */
	protected static function wizardPhoto() : array
	{
		$member = static::$_member;
		$wizards = array();

		foreach( ProfileStep::loadAll() AS $step )
		{
			$include = array();
			if ( $step->completion_act === 'profile_photo' )
			{
				foreach( $step->subcompletion_act as $item )
				{
					switch( $item )
					{
						case 'photo':
							if ( !static::completedPhoto() )
							{
								$include['photo'] = $step;
							}
						break;
						
						case 'cover_photo':
							if ( !static::completedCoverPhoto() )
							{
								$include['cover_photo'] = $step;
							}
						break;
					}
				}
				
				if ( count( $include ) )
				{
					$wizards[ $step->key ] = function( $data ) use ( $member, $include, $step ) {
						$form = new Form( 'profile_generic_' . $step->id, 'profile_complete_next' );
						
						if ( isset( $include['photo'] ) )
						{
							static::photoForm( $form, $include['photo'], $member );
						}
						
						if ( isset( $include['cover_photo'] ) )
						{
							static::coverPhotoForm( $form, $include['cover_photo'], $member );
						}
						
						/* The forms are built immediately after posting which means it resubmits with empty values which confuses some form elements */
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
		}

		return $wizards;
	}

	/**
	 * Wizard: Crop
	 *
	 * @return	array
	 */
	protected static function wizardCrop() : array
	{
		$member = static::$_member;
		$wizards = array();
		foreach( ProfileStep::loadAll() AS $step )
		{
			if ( $step->completion_act === 'profile_photo' )
			{
				$wizards[ 'profile_step_title_crop' ] = function( $data ) use ( $member, $step ) {
					/* We just specified 'none' so we should continue now and act as if this is done */
					if( isset( $_SESSION['profileCompletionData'] ) AND is_array( $_SESSION['profileCompletionData'] ) AND in_array( 'photo-none', $_SESSION['profileCompletionData'] ) )
					{
						return array();
					}

					$form = new Form( 'profile_generic_crop', 'profile_complete_next' );

					static::cropForm( $form, $step, $member );

					/* The forms are built immediately after posting which means it resubmits with empty values which confuses some form elements */
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

		return $wizards;
	}
	
	/* !Misc Utility Methods */
	
	/**
	 * Photo Form
	 *
	 * @param	Form		$form	The form
	 * @param	ProfileStep	$step	The step
	 * @param	Member				$member	The member
	 * @return	void
	 */
	protected static function photoForm( Form $form, ProfileStep $step, Member $member ) : void
	{
		$photoVars = explode( ':', $member->group['g_photo_max_vars'] );
					
		$toggles = array( 'custom' => array( 'member_photo_upload' ) );

		$options = array();
		$defaultType =  ( $member->pp_photo_type == 'letter' ) ? 'none' : $member->pp_photo_type;

		if( $step->required AND $defaultType == 'none' )
		{
			$defaultType = 'custom';
		}

		if ( $photoVars[0] )
		{
			$options['custom'] = 'member_photo_upload';
		}
			
		if ( !$step->required )
		{
			$options['none'] = 'member_photo_none';
		}

		/* Create that selection */
		if( count( $options ) > 1 )
		{
			$form->add( new Radio( 'pp_photo_type', 'none', $step->required, array( 'options' => $options, 'toggles' => $toggles ) ) );
		}
		else
		{
			$form->hiddenValues['pp_photo_type']  = ( $photoVars[0] and !$defaultType ) ? 'custom' : $defaultType;
		}
		
		if ( $photoVars[0] )
		{
			$form->add( new Upload( 'member_photo_upload', NULL, FALSE, array( 'supportsDelete' => FALSE, 'image' => array( 'maxWidth' => $photoVars[1], 'maxHeight' => $photoVars[2] ), 'allowStockPhotos' => TRUE, 'storageExtension' => 'core_Profile', 'maxFileSize' => $photoVars[0] / 1024 ), function( $val ) use ( $member ) {
				if( Request::i()->pp_photo_type == 'custom' AND !$val )
				{
					throw new DomainException('form_required');
				}

				if( $val instanceof File )
				{
					try
					{
						$image = Image::create( $val->contents() );
						if( $image->isAnimatedGif and !$member->group['g_upload_animated_photos'] )
						{
							throw new DomainException('member_photo_upload_no_animated');
						}
					} catch ( FileException $e ){}

				}
			}, NULL, NULL, 'member_photo_upload' ) );
		}
	}

	/**
	 * Crop Form
	 *
	 * @param	Form		$form	The form
	 * @param	ProfileStep	$step	The step
	 * @param	Member				$member	The member
	 * @return	void
	 */
	protected static function cropForm( Form $form, ProfileStep $step, Member $member ) : void
	{
		/* Get the photo */
		try
		{
			$original = File::get( 'core_Profile', $member->pp_main_photo );
			$image = Image::create( $original->contents() );
		}
		catch( Exception $e )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&do=completion', 'front', 'settings' )->setQueryString('_moveToStep', $step->getNextStep() ) );
		}

		/* Work out which dimensions to suggest */
		if ( $image->width < $image->height )
		{
			$suggestedWidth = $suggestedHeight = $image->width;
		}
		else
		{
			$suggestedWidth = $suggestedHeight = $image->height;
		}

		/* Build form */
		$form->class = 'ipsForm--noLabels';
		$form->add( new Custom('photo_crop', array( 0, 0, $suggestedWidth, $suggestedHeight ), FALSE, array(
			'getHtml'	=> function( $field ) use ( $original, $member )
			{
				return Theme::i()->getTemplate('members', 'core', 'global')->photoCrop( $field->name, $field->value, $member->url()->setQueryString( 'do', 'cropPhotoGetPhoto' )->csrf() );
			}
		) ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			try
			{
				/* Create new file */
				$image->cropToPoints( $values['photo_crop'][0], $values['photo_crop'][1], $values['photo_crop'][2], $values['photo_crop'][3] );

				/* Delete the current thumbnail */
				if ( $member->pp_thumb_photo )
				{
					try
					{
						File::get( 'core_Profile', $member->pp_thumb_photo )->delete();
					}
					catch ( Exception $e ) { }
				}

				/* Save the new */
				$cropped = File::create( 'core_Profile', $original->originalFilename, (string) $image );
				$member->pp_thumb_photo = (string) $cropped->thumbnail( 'core_Profile', PHOTO_THUMBNAIL_SIZE, PHOTO_THUMBNAIL_SIZE );
				$member->save();

				/* Delete the temporary full size cropped image */
				$cropped->delete();

				/* Edited member, so clear widget caches (stats, widgets that contain photos, names and so on) */
				Widget::deleteCaches();

			}
			catch ( Exception $e )
			{
				$form->error = Member::loggedIn()->language()->addToStack('photo_crop_bad');
			}
		}
	}
	
	/**
	 * Cover Photo Form
	 *
	 * @param	Form		$form	The form
	 * @param	ProfileStep	$step	The step
	 * @param	Member				$member	The member
	 * @return	void
	 */

	protected static function coverPhotoForm( Form $form, ProfileStep $step, Member $member ) : void
	{
		$photo = $member->coverPhoto();

		$form->add( new Upload( 'complete_profile_cover_photo', NULL, $step->required, array( 'allowStockPhotos' => TRUE, 'image' => TRUE, 'minimize' => TRUE, 'maxFileSize' => ( $photo->maxSize and $photo->maxSize != -1 ) ? $photo->maxSize / 1024 : NULL, 'storageExtension' => 'core_Profile' ) ) );
				
	}
}