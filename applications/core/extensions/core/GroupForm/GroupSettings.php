<?php
/**
 * @brief		Group Form: Core
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Mar 2013
 */

namespace IPS\core\extensions\core\GroupForm;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\Application\Module;
use IPS\Extensions\GroupFormAbstract;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\Image;
use IPS\Lang;
use IPS\Member\Group;
use IPS\Session\Front;
use IPS\Session\Store;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group Form: Core
 */
class GroupSettings extends GroupFormAbstract
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
		/* Group title */
		$form->addHeader( 'group_details' );
		$form->add( new Translatable( 'g_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( !$group->g_id ) ? NULL : "core_group_{$group->g_id}" ) ) );
		
		/* Group Icon */
		$icon = NULL;
		if ( $group->g_icon )
		{
			try
			{
				$icon = File::get( 'core_Theme', $group->g_icon );
			}
			catch ( Exception $e ) { }
		}
		$form->add( new Upload( 'g_icon', $icon, FALSE, array( 'storageExtension' => 'core_Theme', 'allowedFileTypes' => Image::supportedExtensions() ) ) );

		/* Prefix/Suffix */
		$form->add( new Custom( 'g_prefixsuffix', array( 'prefix' => $group->prefix, 'suffix' => $group->suffix ), FALSE, array(
			'getHtml'	=> function( $element )
			{
				$color = NULL;
				if ( preg_match( '/^<span style=\'color:#((?:(?:[a-f0-9]{3})|(?:[a-f0-9]{6})))\'>$/i', $element->value['prefix'], $matches ) and $element->value['suffix'] === '</span>' )
				{
					$color = '#' . $matches[1];
					$element->value['prefix'] = NULL;
					$element->value['suffix'] = NULL;
				}
								
				return Theme::i()->getTemplate( 'members', 'core' )->prefixSuffix( $element->name, $color, $element->value['prefix'], $element->value['suffix'] );
			},
			'formatValue' => function( $element )
			{
				if ( !empty( $element->value['prefix'] ) or !empty( $element->value['suffix'] ) )
				{
					return array( 'prefix' => $element->value['prefix'], 'suffix' => $element->value['suffix'] );
				}
				elseif ( isset( $element->value['color'] ) AND $element->value['color'] )
				{
					$color = mb_strtolower( $element->value['color'] );
					if ( mb_substr( $color, 0, 1 ) !== '#' )
					{
						$color = '#' . $color;
					}

					if( !in_array( $color, array( '#fff', '#ffffff', '#000', '#000000' ) ) )
					{
						return array( 'prefix' => "<span style='color:{$color}'>", 'suffix' => '</span>' );
					}
				}
				
				return array( 'prefix' => '', 'suffix' => '' );
			}
		) ) );
		
		if ( $group->g_id != Settings::i()->guest_group )
		{
			$form->add( new YesNo( 'g_promote_exclude', $group->g_id ? !$group->g_promote_exclude : FALSE, FALSE ) );
		}
		
		/* Can access site? */
		$form->addHeader( 'permissions' );
		$tabs = array();
		foreach ( Application::allExtensions( 'core', 'GroupForm', FALSE ) as $key => $class )
		{
			if ( $key != 'core_GroupSettings' )
			{
				$tabs[] = $form->id . '_tab_group__' . $key;
			}
		}

		$form->add( new YesNo( 'g_view_board', $group->g_id ? $group->g_view_board : TRUE, FALSE, array( 'togglesOn' => array_merge( $tabs, array( 'group_username', 'g_use_search', "gbw_cannot_be_ignored", "{$form->id}_header_group_signatures", 'g_use_signatures', "{$form->id}_header_group_staff", 'g_access_offline', 'g_search_flood', 'gbw_cannot_be_ignored' ) ) ) ) );
		$form->add( new YesNo( 'g_access_offline', $group->g_access_offline, FALSE, array(), NULL, NULL, NULL, 'g_access_offline' ) );

		/* Usernames */
		if( $group->g_id != Settings::i()->guest_group )
		{
			$defaultValues = array( 
				$group->g_dname_changes, 
				$group->g_displayname_unit, 
				$group->g_bitoptions['gbw_displayname_unit_type'], 
				$group->g_dname_date ?: 1, 
				'canchange'		=> (bool) $group->g_dname_changes,
				'unlimited'		=> ( $group->g_dname_changes == -1 AND !$group->g_dname_date ),
				'always'		=> ( !$group->g_dname_changes AND !$group->g_bitoptions['gbw_displayname_unit_type'] ),
			);

			$form->add( new Custom( 'group_username', $defaultValues, FALSE, array(
				'getHtml' => function( $element ) use ( $defaultValues )
				{
					$values = array_merge( $defaultValues, $element->value );

					return Theme::i()->getTemplate( 'members' )->usernameChanges( $element->name, $values );
				},
				'formatValue' => function( $element )
				{
					$value = $element->value;

					if ( !isset( $value['canchange'] ) OR !$value['canchange'] )
					{
						$value[0] = 0;
					}
					else
					{
						if ( isset( $value['unlimited'] ) AND $value['unlimited'] )
						{
							$value[0] = -1;
							$value[3] = 0;
						}
						elseif ( !isset( $value[0] ) )
						{
							$value[0] = 0;
						}
						
						if ( isset( $value['always'] ) AND $value['always'] )
						{
							$value[1] = 0;
							$value[2] = 0;
						}
					}

					return $value;
				},
				'validate' => function( $element )
				{
					$value = $element->value;

					if( ( isset( $value[1] ) AND $value[1] < 0 ) OR ( isset( $value[2] ) AND $value[2] < 0 ) OR ( isset( $value[3] ) AND $value[3] < 0 ) )
					{
						throw new DomainException( 'only_positive_values' );
					}
				}
			), NULL, NULL, NULL, 'group_username' ) );
		}
		
		/* Search */
		if ( $group->canAccessModule( Module::get( 'core', 'search', 'front' ) ) )
		{
			$form->add( new Interval( 'g_search_flood', $group->g_id ? $group->g_search_flood : 0, FALSE, array( 'valueAs' => Interval::SECONDS, 'unlimited' => 0 ) ) );
		}
		
		$form->add( new YesNo( 'gbw_change_layouts', $group->g_id ? ( $group->g_bitoptions['gbw_change_layouts'] ) : false, false, array(), NULL, NULL, NULL, 'gbw_change_layouts' ) );
		
		/* Privacy */
		$form->addHeader( 'group_privacy' );
		if( $group->g_id != Settings::i()->guest_group )
		{
			$form->add( new Radio( 'g_hide_online_list', $group->g_hide_online_list, FALSE, array( 'options' => array(
				0	=> 'group_allow_anon_logins',
				1	=> 'group_force_anon_logins',
				2	=> 'group_disallow_anon_logins'
			) ), NULL, NULL, NULL, 'g_hide_online_list' ) );
		}
		$form->add( new YesNo( 'gbw_hide_group', !$group->g_bitoptions['gbw_hide_group'], FALSE, array(), NULL, NULL, NULL, 'gbw_hide_group' ) );
		
		if ( Settings::i()->ignore_system_on )
		{
			$form->add( new YesNo( 'can_be_ignored', $group->g_id ? ( !$group->g_bitoptions['gbw_cannot_be_ignored'] ) : TRUE, FALSE, array(), NULL, NULL, NULL, 'gbw_cannot_be_ignored' ) );
		}

		$form->add( new YesNo( 'can_post_anonymously', $group->g_id ? ( $group->g_bitoptions['gbw_can_post_anonymously'] ) : FALSE, FALSE, array(), NULL, NULL, NULL, 'gbw_can_post_anonymously' ) );
		
		/* Signatures */
		if( Settings::i()->signatures_enabled AND $group->g_id != Settings::i()->guest_group )
		{
			$form->addHeader( 'group_signatures' );
			$signatureLimits = ( $group->g_id ) ? explode( ':', $group->g_signature_limits ) : array( 0, '', '', '', '', '' );
			$form->add( new YesNo( 'g_use_signatures', !$signatureLimits[0], FALSE, array( 'togglesOn' => array( 'g_signature_limit', 'g_sig_max_images', 'g_sig_max_image_size', 'g_sig_max_urls', 'g_sig_max_lines' ) ), NULL, NULL, NULL, 'g_use_signatures' ) );
			$form->add( new Custom( 'g_signature_limit', array( $group->g_sig_unit, $group->g_bitoptions['gbw_sig_unit_type'] ), FALSE, array( 'getHtml' => function( $element )
			{
				if( !isset( $element->value[0] ) )
				{
					$element->value[0] = 0;
				}

				if( !isset( $element->value[1] ) )
				{
					$element->value[1] = 0;
				}

				return Theme::i()->getTemplate( 'members' )->signatureLimits( $element->name, $element->value );
			} ), NULL, NULL, NULL, 'g_signature_limit' ) );
			
			/* We need a special check for 0 here, as that indicates the user is not allowed to use images at all - the ternary condition here will always show the field as "unlimited" in this case */
			$form->add( new Number( 'g_sig_max_images', ( $signatureLimits[1] OR (int) $signatureLimits[1] === 0 ) ? $signatureLimits[1] : '', FALSE, array( 'unlimited' => '' ), NULL, NULL, NULL, 'g_sig_max_images' ) );


			$form->add( new WidthHeight( 'g_sig_max_image_size', array( $signatureLimits[2] ?: '', $signatureLimits[3] ?: '' ), FALSE, array( 'unlimited' => array( '', '' ) ), NULL, NULL, NULL, 'g_sig_max_image_size' ) );
			$form->add( new Number( 'g_sig_max_urls', ( $signatureLimits[4] OR (int) $signatureLimits[4] === 0 ) ? $signatureLimits[4] : '', FALSE, array( 'unlimited' => '' ), NULL, NULL, NULL, 'g_sig_max_urls' ) );
			$form->add( new Number( 'g_sig_max_lines', $signatureLimits[5] ?? '', FALSE, array( 'unlimited' => '', 'min' => 1 ), NULL, NULL, NULL, 'g_sig_max_lines' ) );
		}
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
		/* Group title */
		Lang::saveCustom( 'core', "core_group_{$group->g_id}", $values['g_title'] );
		
		/* Group Icon */
		$group->g_icon = NULL;
		if ( $values['g_icon'] instanceof File )
		{
			$group->g_icon = (string) $values['g_icon'];
		}
		
		/* Prefix/Suffix */
		$group->prefix = $values['g_prefixsuffix']['prefix'];
		$group->suffix = $values['g_prefixsuffix']['suffix'];

		/* Group promotion */
		if ( isset( $values['g_promote_exclude'] ) )
		{
			$group->g_promote_exclude = !$values['g_promote_exclude'];
		}

		/* Username changes */
		if( $group->g_id != Settings::i()->guest_group )
		{
	        $group->g_dname_changes = ( isset( $values['group_username']['canchange'] ) and $values['group_username']['canchange'] ) ? (int) $values['group_username'][0] : 0;
	        $group->g_displayname_unit = ( isset( $values['group_username']['canchange'] ) ) ? (int) $values['group_username'][1] : 0;
	        $group->g_bitoptions['gbw_displayname_unit_type'] = ( isset( $values['group_username'][2] ) ) ? $values['group_username'][2] : 0;
	        $group->g_dname_date = ( isset( $values['group_username']['canchange'] ) ) ? (int) $values['group_username'][3] : 1;
		}

		/* Signatures */
		if( Settings::i()->signatures_enabled AND $group->g_id != Settings::i()->guest_group )
		{
            if( isset( $values['g_use_signatures'] ) )
			{
				$group->g_signature_limits = implode( ':', array( (int) !$values['g_use_signatures'], $values['g_sig_max_images'], $values['g_sig_max_image_size'][0], $values['g_sig_max_image_size'][1], $values['g_sig_max_urls'], $values['g_sig_max_lines'] ) );

                if( !isset( $values['g_signature_limit'][3] ) and $values['g_use_signatures'] )
                {
                    $group->g_sig_unit = $values['g_signature_limit'][0];
                    $group->g_bitoptions['gbw_sig_unit_type'] = $values['g_signature_limit'][1];
                }
                else
                {
                    $group->g_sig_unit = 0;
                }
			}
			else
			{
				$group->g_signature_limits = '0:::::';
				$group->g_sig_unit = 0;
			}
		}
						
		/* Other */
		$group->g_view_board = $values['g_view_board'];
		if ( isset( $values['g_search_flood'] ) )
		{
			$group->g_search_flood = $values['g_search_flood'];
		}

		if ( isset( $values['gbw_hide_group'] ) )
		{
			$group->g_bitoptions['gbw_hide_group'] = !$values['gbw_hide_group'];
		}

		$group->g_access_offline = $values['g_access_offline'];
		if ( isset( $values['can_be_ignored'] ) )
		{
			$group->g_bitoptions['gbw_cannot_be_ignored'] = !$values['can_be_ignored'];
		}
		
		if ( isset( $values['can_post_anonymously'] ) )
		{
			$group->g_bitoptions['gbw_can_post_anonymously'] = $values['can_post_anonymously'];
		}
		
		if ( isset( $values['gbw_change_layouts'] ) )
		{
			$group->g_bitoptions['gbw_change_layouts'] = $values['gbw_change_layouts'];
		}
	
		/* Update existing sessions if this setting is changed */
		if( isset( $values['g_hide_online_list'] ) AND $group->g_hide_online_list != (int) $values['g_hide_online_list'] )
		{
			/* We have to send a limit even though we want all records because otherwise the Database store does not return all columns */
			foreach( Store::i()->getOnlineUsers( 0, 'desc', array( 0, 5000 ), $group->g_id, TRUE ) as $sessionData )
			{
				/* Set public sessions to Anonymous */
				if( $values['g_hide_online_list'] == Front::LOGIN_TYPE_ANONYMOUS AND $sessionData['login_type'] != Front::LOGIN_TYPE_INCOMPLETE )
				{
					$sessionData['login_type'] = Front::LOGIN_TYPE_ANONYMOUS;
				}
				/* Set anonymous sessions to public */
				elseif( $sessionData['login_type'] == Front::LOGIN_TYPE_ANONYMOUS )
				{
					$sessionData['login_type'] = Front::LOGIN_TYPE_MEMBER;
				}

				Store::i()->updateSession( $sessionData );
			}
		}

		if ( isset( $values['g_hide_online_list'] ) )
		{
			$group->g_hide_online_list = $values['g_hide_online_list'];
		}
	}

	/**
	 * Delete
	 *
	 * @param	Group	$group	The group
	 * @return	void
	 */
	public function delete( Group $group ) : void
	{
		if ( $group->g_icon )
		{
			try
			{
				File::get( 'core_Theme', $group->g_icon )->delete();
			}
			catch( Exception $ex ) { }
		}
	}

	/**
	 * Copy Group
	 *
	 * @param	Group	$oldGroup	The old, just cloned group
	 * @param	Group	$newGroup	The new group
	 * @return	void
	 */
	public function cloneGroup( Group $oldGroup, Group $newGroup ) : void
	{
		if ( $oldGroup->g_icon )
		{
				try
				{
					$icon = File::get( 'core_Theme', $oldGroup->g_icon );
					$newIcon = File::create( 'core_Theme', $icon->originalFilename, $icon->contents() );
					$newGroup->g_icon = (string) $newIcon;
				}
				catch ( Exception $e )
				{
					$newGroup->g_icon = NULL;
				}

			$newGroup->save();
		}
	}

	/**
	 * Can this group be deleted?
	 *
	 * @param	Group	$group	The group
	 * @return	bool
	 */
	public function canDelete( Group $group ) : bool
	{
		if ( in_array( $group->g_id, array( Settings::i()->guest_group, Settings::i()->member_group, Settings::i()->admin_group ) ) )
		{
			return FALSE;
		}
		return TRUE;
	}
}