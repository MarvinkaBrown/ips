<?php
/**
 * @brief		Group Form: Core: Social
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Mar 2013
 */

namespace IPS\core\extensions\core\GroupForm;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\Application\Module;
use IPS\Extensions\GroupFormAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Settings;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group Form: Core: Social
 */
class Social extends GroupFormAbstract
{
	/**
	 * Process Form
	 *
	 * @param	Form		$form	The form
	 * @param	Group		$group	Existing Group
	 * @return	void
	 */
	public function process( Form $form, Group $group ) : void
	{
		/* Profiles */
		if ( $group->canAccessModule( Module::get( 'core', 'members', 'front' ) ) )
		{
			$form->addHeader( 'group_profiles' );
			if ( $group->g_id != Settings::i()->guest_group )
			{
				$form->add( new YesNo( 'g_edit_profile', $group->g_id ? $group->g_edit_profile : 1, FALSE, array( 'togglesOn' => array( 'gbw_allow_upload_bgimage', 'g_photo_max_vars_size', 'g_photo_max_vars_wh', 'g_upload_animated_photos' ) ) ) );
				$photos = ( $group->g_id ? explode( ':', $group->g_photo_max_vars ) : array( 50, 150, 150 ) );
				$form->add( new Number( 'g_photo_max_vars_size', $photos[0], FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'g_photo_max_vars_none', 'unlimitedToggleOn' => FALSE, 'unlimitedToggles' => array( 'g_photo_max_vars_wh', 'g_upload_animated_photos' ) ), NULL, NULL, 'kB', 'g_photo_max_vars_size' ) );
				$form->add( new Number( 'g_photo_max_vars_wh', $photos[1], FALSE, array(), NULL, NULL, 'px', 'g_photo_max_vars_wh' ) );
				$form->add( new YesNo( 'g_upload_animated_photos', $group->g_id ? $group->g_upload_animated_photos : TRUE, FALSE, array(), NULL, NULL, NULL, 'g_upload_animated_photos' ) );
	
				$form->add( new YesNo( 'gbw_allow_upload_bgimage', $group->g_id ? ( $group->g_bitoptions['gbw_allow_upload_bgimage'] ) : TRUE, FALSE, array( 'togglesOn' => array( 'g_max_bgimg_upload' ) ), NULL, NULL, NULL, 'gbw_allow_upload_bgimage' ) );
				$form->add( new Number( 'g_max_bgimg_upload', $group->g_id ? $group->g_max_bgimg_upload : -1, FALSE, array( 'unlimited' => -1 ), function( $value ) {
					if( !$value )
					{
						throw new InvalidArgumentException('form_required');
					}
				}, NULL, 'kB', 'g_max_bgimg_upload' ) );
			}
			$form->add( new YesNo( 'g_view_displaynamehistory' , $group->g_view_displaynamehistory, FALSE ) );
		}
	
		/* Personal Conversations */
		if ( $group->g_id != Settings::i()->guest_group and $group->canAccessModule( Module::get( 'core', 'messaging', 'front' ) ) )
		{
			$form->addHeader( 'personal_conversations' );
			$form->add( new Number( 'g_pm_perday', $group->g_pm_perday, FALSE, array( 'unlimited' => -1, 'min' => 0 ), NULL, NULL, NULL, 'g_pm_perday' ) );
			$form->add( new Number( 'g_pm_flood_mins', $group->g_pm_flood_mins, FALSE, array( 'unlimited' => -1, 'min' => 0 ), NULL, NULL, NULL, 'g_pm_flood_mins' ) );
			$form->add( new Number( 'g_max_mass_pm', $group->g_max_mass_pm, FALSE, array( 'unlimited' => -1, 'max' => 500, 'min' => 0 ), NULL, NULL, NULL, 'g_max_mass_pm' ) );
			$form->add( new Number( 'g_max_messages', $group->g_max_messages, FALSE, array( 'unlimited' => -1, 'min' => 0 ), NULL, NULL, NULL, 'g_max_messages' ) );
			if ( Settings::i()->attach_allowed_types != 'none' )
			{
				$form->add( new YesNo( 'g_can_msg_attach', $group->g_can_msg_attach, FALSE, array(), NULL, NULL, NULL, 'g_can_msg_attach' ) );
			}
			$form->add( new YesNo( 'gbw_pm_override_inbox_full', $group->g_id ? ( $group->g_bitoptions['gbw_pm_override_inbox_full'] ) : TRUE ) );
		}
		
		/* Column does not have a default value, so for a new group we have to explicitly set something */
		$group->g_club_allowed_nodes = $group->g_club_allowed_nodes ?: '';

		/* Clubs */
		if ( Settings::i()->clubs and $group->g_id != Settings::i()->guest_group and $group->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) )
		{
			$form->addHeader( 'module__core_clubs' );
			
			$form->add( new CheckboxSet( 'g_create_clubs', $group->g_create_clubs ? explode( ',', $group->g_create_clubs ) : array(), FALSE, array(
				'options' => array(
					Club::TYPE_PUBLIC	=> 'club_type_public',
					Club::TYPE_OPEN		=> 'club_type_open',
					Club::TYPE_CLOSED	=> 'club_type_closed',
					Club::TYPE_PRIVATE	=> 'club_type_private',
					Club::TYPE_READONLY	=> 'club_type_readonly',
				),
			), NULL, NULL, NULL, 'g_create_clubs' ) );
			
			if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->clubs_paid_on )
			{
				$form->add( new YesNo( 'gbw_paid_clubs', $group->g_id ? ( $group->g_bitoptions['gbw_paid_clubs'] ) : FALSE ) );
			}

			$form->add( new YesNo( 'gbw_club_manage_indexing', $group->g_id ? ( $group->g_bitoptions['gbw_club_manage_indexing'] ) : FALSE ) );
			
			$form->add( new Number( 'g_club_limit', $group->g_club_limit ?: -1, FALSE, array( 'unlimited' => -1 ) ) );
			
			$availableClubNodes = array();
			foreach ( Club::availableNodeTypes() as $class )
			{
				$availableClubNodes[ $class ] = $class::clubAcpTitle();
			}
			$form->add( new CheckboxSet( 'g_club_allowed_nodes', $group->g_club_allowed_nodes == '*' ? array_keys( $availableClubNodes ) : explode( ',', $group->g_club_allowed_nodes ), FALSE, array( 'options' => $availableClubNodes ), NULL, NULL, NULL, 'g_club_allowed_nodes' ) );
		}
		
		/* Reputation */
		if ( Settings::i()->reputation_enabled )
		{
			$form->addHeader( 'reputation' );
		
			if( $group->g_id != Settings::i()->guest_group )
			{
				$form->add( new Number( 'g_rep_max_positive', $group->g_rep_max_positive, FALSE, array( 'unlimited' => -1, ), NULL, NULL, Member::loggedIn()->language()->addToStack('per_day') ) );
			}
			
			$form->add( new YesNo( 'gbw_view_reps', $group->g_id ? ( $group->g_bitoptions['gbw_view_reps'] ) : TRUE ) );
			$form->add( new YesNo( 'gbw_view_helpful', $group->g_id ? ( $group->g_bitoptions['gbw_view_helpful'] ) : TRUE ) );
		}

		$form->addHeader( 'follows' );
		$form->add( new YesNo( 'g_view_followers', $group->g_view_followers, false ) );
	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	Group	$group	The group
	 * @return	void
	 */
	public function save( array $values, Group $group ) : void
	{
		/* Init */
		$bwKeys	= array();
		$keys	= array();

		/* Display Name History */
		if ( array_key_exists( 'g_view_displaynamehistory', $values ) )
		{
			$group->g_view_displaynamehistory = $values['g_view_displaynamehistory'];
		}

		if( $group->g_id != Settings::i()->guest_group )
		{
			/* Helpful */
			$bwKeys[]	= 'gbw_view_helpful';

			/* Profiles */
			if ( $group->canAccessModule( Module::load( 'members', 'sys_module_key', array( 'sys_module_application=? AND sys_module_area=?', 'core', 'front' ) ) ) )
			{
				$bwKeys[]	= 'gbw_allow_upload_bgimage';
				$keys		= array_merge( $keys, array( 'g_edit_profile', 'g_max_bgimg_upload', 'g_upload_animated_photos' ) );
	
				/* Photos */
				$group->g_photo_max_vars = implode( ':', array( $values['g_photo_max_vars_size'], $values['g_photo_max_vars_wh'], $values['g_photo_max_vars_wh'] ) );
			}
				
			/* Personal messages */
			if ( $group->canAccessModule( Module::get( 'core', 'messaging', 'front' ) ) )
			{
				$bwKeys[]	= 'gbw_pm_override_inbox_full';
				$keys		= array_merge( $keys, array( 'g_pm_perday', 'g_pm_flood_mins', 'g_max_mass_pm', 'g_max_messages', 'g_can_msg_attach', 'g_max_notifications' ) );
			}
			
			/* Clubs */
			if ( Settings::i()->clubs and $group->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) )
			{
				$group->g_create_clubs = implode( ',', $values['g_create_clubs'] );
				$group->g_club_allowed_nodes = ( count( $values['g_club_allowed_nodes'] ) === count( Club::availableNodeTypes() ) ) ? '*' : implode( ',', $values['g_club_allowed_nodes'] );
				$group->g_club_limit = $values['g_club_limit'] == -1 ? NULL : $values['g_club_limit'];
				$bwKeys[] = 'gbw_club_manage_indexing';
				if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->clubs_paid_on )
				{
					$bwKeys[] = 'gbw_paid_clubs';
				}
			}
		}

		$group->g_view_followers = $values['g_view_followers'];
		
		/* Reputation */
		if ( Settings::i()->reputation_enabled )
		{
			$bwKeys[] = 'gbw_view_reps';

			if( $group->g_id != Settings::i()->guest_group )
			{
				$keys[] = 'g_rep_max_positive';
			}
		}

		/* Store bitwise options */
		foreach ( $bwKeys as $k )
		{
			$group->g_bitoptions[ $k ] = $values[ $k ];
		}

		/* Store other options */
		foreach ( $keys as $k )
		{
			if ( isset( $values[ $k ] ) )
			{
				$group->$k = $values[ $k ];
			}
		}
	}
}
