<?php
/**
 * @brief		Group Form: Core: Content
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Mar 2013
 */

namespace IPS\core\extensions\core\GroupForm;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Extensions\GroupFormAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\IPS;
use IPS\Member;
use IPS\Member\Group;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group Form: Core: Content
 */
class Content extends GroupFormAbstract
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
		/* Set up available content classes for customizations - add in conversations which do not have a content router extension */
		$contentClasses = array( 'IPS\core\Messenger\Conversation' => 'personal_conversation_pl' );
		foreach ( Application::allExtensions( 'core', 'ContentRouter', $group ) as $ext )
		{
			foreach ( $ext->classes as $class )
			{
				if ( isset( $class::$databaseColumnMap['author'] ) )
				{
					$contentClasses[ $class ] = $class::$title . '_pl';
				}
			}
		}

		/* Uploading */
		if ( Settings::i()->attach_allowed_types != 'none' )
		{
			$form->addHeader( 'uploads' );
			$form->add( new YesNo( 'g_attach', ( $group->g_attach_max != 0 ), FALSE, array( 'togglesOn' => array( 'g_attach_max', 'g_attach_per_post', 'gbw_delete_attachments' ) ) ) );
			if( $group->g_id != Settings::i()->guest_group )
			{
				$form->add( new Number( 'g_attach_max', $group->g_id ? $group->g_attach_max : 500000, FALSE, array( 'unlimited' => -1 ), NULL, NULL, Member::loggedIn()->language()->addToStack( 'filesize_raw_k' ), 'g_attach_max' ) );
			}
			$form->add( new Number( 'g_attach_per_post', $group->g_id ? $group->g_attach_per_post : 50000, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack( 'g_attach_per_post_suffix' ), 'g_attach_per_post' ) );
			if( $group->g_id != Settings::i()->guest_group )
			{
				$form->add( new YesNo( 'gbw_delete_attachments', $group->g_bitoptions['gbw_delete_attachments'], FALSE, array(), NULL, NULL, NULL, 'gbw_delete_attachments' ) );
			}
		}
		
		/* Polls */
		if( $group->g_id != Settings::i()->guest_group )
		{
			$form->addHeader( 'polls' );
			$form->add( new YesNo( 'g_post_polls', $group->g_post_polls ) );
			$form->add( new YesNo( 'g_vote_polls', $group->g_vote_polls ) );
			$form->add( new YesNo( 'g_close_polls', $group->g_close_polls ) );
			$form->add( new Radio( 'g_poll_results', $group->g_poll_results, false, array(
				'options' => array(
					0 => 'g_poll_results_never',
					1 => 'g_poll_results_always',
					2 => 'g_poll_results_closed'
				)
			) ) );
		}
		
		/* Viewing */
		$form->addHeader( 'group_viewing_title' );

		$form->add( new YesNo( 'gbw_post_highlight', $group->g_id ? $group->g_bitoptions['gbw_post_highlight'] : FALSE ) );
		$form->add( new YesNo( 'gbw_hide_inline_modevents', $group->g_id ? !$group->g_bitoptions['gbw_hide_inline_modevents'] : TRUE ) );

		$groupItemPostedIn = 'disabled';
		if ( $group->g_bitoptions['gbw_posted_in'] )
		{
			$groupItemPostedIn = 'primary';
		}
		if ( $group->g_bitoptions['gbw_posted_in_secondary'] )
		{
			$groupItemPostedIn = 'both';
		}

		$form->add( new Radio( 'gbw_posted_in', $groupItemPostedIn, FALSE, array(
			'options'	=> array(
				'primary'  => 'gbw_posted_in_primary',
				'both'     => 'gbw_posted_in_secondary',
				'disabled' => 'gbw_posted_in_disabled'
			)
		) ) );

		/* Tags */
		$form->addHeader( 'tags' );

		if ( Settings::i()->tags_enabled )
		{
			$form->add( new YesNo( 'gbw_disable_tagging', $group->g_id ? !( $group->g_bitoptions['gbw_disable_tagging'] ) : TRUE ) );
			if ( Settings::i()->tags_can_prefix )
			{
				$form->add( new YesNo( 'gbw_disable_prefixes', $group->g_id ? !( $group->g_bitoptions['gbw_disable_prefixes'] ) : TRUE ) );
			}
		}

		/* Ratings */
		$form->addHeader('ratings');
		$form->add( new YesNo( 'g_topic_rate_setting', $group->g_topic_rate_setting, FALSE, array( 'togglesOn' => array( 'g_topic_rate_change' ) ) ) );
		$form->add( new YesNo( 'g_topic_rate_change', $group->g_topic_rate_setting == 2, FALSE, array(), NULL, NULL, NULL, 'g_topic_rate_change' ) );
		
		/* Editing */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_simplified_mode' ) or $group->g_id != Settings::i()->guest_group )
		{
			$form->addHeader( 'group_editing' );
		}

		if ( $group->g_id != Settings::i()->guest_group )
		{
			$form->add( new CheckboxSet( 'g_edit_posts', !$group->g_edit_posts ? 0 : ( $group->g_edit_posts == '1' ? '1' : explode( ',', $group->g_edit_posts ) ), FALSE, array(
				'options'	=> $contentClasses,
				'unlimited'	=> '1'
			) ) );
			$form->add( new Number( 'g_edit_cutoff', $group->g_edit_cutoff, FALSE, array( 'unlimited' => 0 ), NULL, Member::loggedIn()->language()->addToStack('g_edit_cutoff_prefix'), Member::loggedIn()->language()->addToStack('g_edit_cutoff_suffix'), 'g_edit_cutoff' ) );
			if ( Settings::i()->edit_log )
			{
				$form->add( new YesNo( 'g_append_edit', $group->g_append_edit, FALSE, array(), NULL, NULL, NULL, 'g_append_edit' ) );
			}


		}

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_simplified_mode' ) )
		{
			foreach ( ['g_editor_restriction', 'g_editor_comments_restriction'] as $editorRestrictionKey )
			{
				$form->add( new Select( $editorRestrictionKey, $group->$editorRestrictionKey ?? 'default', true, [
					'options' => [
						0 => 'g_editor_mode_sandboxed',
						1 => 'g_editor_mode_regular',
						2 => 'g_editor_mode_advanced',
					],
					'unlimited' 	=> 'default',
					'unlimitedLang' => 'g_editor_mode_default'
				] ) );
			}
		}

		/* Deleting */
		if( $group->g_id != Settings::i()->guest_group )
		{
			$form->addHeader( 'group_deleting' );
			$form->add( new CheckboxSet( 'g_hide_own_posts', !$group->g_hide_own_posts ? 0 : ( $group->g_hide_own_posts == '1' ? '1' : explode( ',', $group->g_hide_own_posts ) ), FALSE, array(
				'options'	=> array_filter( $contentClasses, function( $class ) {
					return IPS::classUsesTrait( $class, 'IPS\Content\hideable' );
				}, ARRAY_FILTER_USE_KEY ),
				'unlimited'	=> '1'
			) ) );
			$form->add( new CheckboxSet( 'g_delete_own_posts', !$group->g_delete_own_posts ? 0 : ( $group->g_delete_own_posts == '1' ? '1' : explode( ',', $group->g_delete_own_posts ) ), FALSE, array(
				'options'	=> $contentClasses,
				'unlimited'	=> '1'
			) ) );
		}
		
		/* Content Limits */
        if( $group->g_id != Settings::i()->guest_group )
        {
            $form->addHeader( 'group_content_limits' );
            $form->add( new Number( 'g_ppd_limit', $group->g_id ? $group->g_ppd_limit : 0, FALSE, array( 'unlimitedToggles' => array( 'g_ppd_unit' ), 'unlimited' => 0, 'unlimitedToggleOn' => FALSE ), NULL, NULL, Member::loggedIn()->language()->addToStack('per_day') ) );
            $form->add( new Custom( 'g_ppd_unit', array( ( $group->g_id ? $group->g_ppd_unit : 0 ), $group->g_bitoptions['gbw_ppd_unit_type'] ), FALSE, array( 'getHtml' => function( $element )
            {
                return Theme::i()->getTemplate( 'members' )->postingLimits( $element->name, $element->value ?? array( null, null ) );
            } ), NULL, NULL, NULL, 'g_ppd_unit' ) );
        }
        
		/* Moderation */
		$form->addHeader( 'group_moderation' );

		if( $group->g_id != Settings::i()->guest_group )
		{
			$form->add( new YesNo( 'gbw_immune_auto_mod', $group->g_bitoptions['gbw_immune_auto_mod'] ) );
			$form->add( new CheckboxSet( 'g_lock_unlock_own', !$group->g_lock_unlock_own ? 0 : ( $group->g_lock_unlock_own == '1' ? '1' : explode( ',', $group->g_lock_unlock_own ) ), FALSE, array(
				'options'	=> array_filter( $contentClasses, function( $class ) {
					return IPS::classUsesTrait( $class, 'IPS\Content\Lockable' );
				}, ARRAY_FILTER_USE_KEY ),
				'unlimited'	=> '1'
			) ) );
		}

		$form->add( new CheckboxSet( 'g_can_report', !$group->g_can_report ? 0 : ( $group->g_can_report == '1' ? '1' : explode( ',', $group->g_can_report ) ), FALSE, array(
			'options'	=> array_filter( array_merge( array( 'IPS\core\Messenger\Message' => 'personal_conversation_pl' ), $contentClasses ), function( $class ) {
				return IPS::classUsesTrait( $class, 'IPS\Content\Reportable' );
			}, ARRAY_FILTER_USE_KEY ),
			'unlimited'	=> '1'
		) ) );
		$form->add( new YesNo( 'g_avoid_flood', $group->g_avoid_flood ) );
		$form->add( new YesNo( 'g_avoid_q', $group->g_avoid_q, FALSE, array( 'togglesOff' => array( 'g_mod_preview' ), 'toggleValue' => FALSE ) ) );
		$form->add( new YesNo( 'g_mod_preview', $group->g_mod_preview, FALSE, array( 'togglesOn' => array( 'g_mod_post_unit' ) ), NULL, NULL, NULL, 'g_mod_preview' ) );
		if( $group->g_id != Settings::i()->guest_group )
		{
			$form->add( new Custom( 'g_mod_post_unit', [$group->g_mod_post_unit ?: 0, $group->g_bitoptions['gbw_mod_post_unit_type']], false, ['getHtml' => function ( $element ) {
				return Theme::i()->getTemplate( 'members' )->moderationLimits( $element->name, $element->value );
			}], null, null, null, 'g_mod_post_unit' ) );
		}
		$form->add( new YesNo( 'g_bypass_badwords', $group->g_bypass_badwords ) );
		if ( Settings::i()->ips_imagescanner_enable )
		{
			Member::loggedIn()->language()->words['g_bypass_badwords'] = Member::loggedIn()->language()->addToStack( 'g_bypass_badwords_images' );
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
		/* Posting limit */
        if( $group->g_id != Settings::i()->guest_group )
        {
            $group->g_ppd_limit = $values['g_ppd_limit'];
            $group->g_ppd_unit = ( is_array( $values['g_ppd_unit'] ) ) ? intval( $values['g_ppd_unit'][0] ) : 0;
            $group->g_bitoptions['gbw_ppd_unit_type'] = ( is_array( $values['g_ppd_unit'] ) ) ? $values['g_ppd_unit'][1] : 0;
        }
        
		/* Polls */
		if( $group->g_id != Settings::i()->guest_group )
		{
			$group->g_post_polls = $values['g_post_polls'];
			$group->g_vote_polls = $values['g_vote_polls'];
			$group->g_close_polls = $values['g_close_polls'];
			$group->g_poll_results = (int) $values['g_poll_results'];
		}

		/* Ratings */
		$group->g_topic_rate_setting = 0;
		if ( $values['g_topic_rate_setting'] )
		{
			if ( $values['g_topic_rate_change'] )
			{
				$group->g_topic_rate_setting = 2;
			}
			else
			{
				$group->g_topic_rate_setting = 1;
			}
		}
		
		/* If we can bypass the mod-queue, then the require approval setting is hidden so we need to turn that off */
		if( isset( $values['g_avoid_q'] ) AND $values['g_avoid_q'] )
		{
			$values['g_mod_preview'] = 0;
		}
		
		/* Mod Queue */
		if( $group->g_id != Settings::i()->guest_group )
		{
			$group->g_mod_post_unit = isset( $values[ 'g_mod_post_unit' ][ 2 ] ) ? 0 : $values[ 'g_mod_post_unit' ][ 0 ];
			$group->g_bitoptions['gbw_mod_post_unit_type'] = isset( $values['g_mod_post_unit'][2] ) ? 0 : $values['g_mod_post_unit'][1];
		}

	
		/* Bitwise */
		$values['gbw_disable_tagging'] = Settings::i()->tags_enabled ? !$values['gbw_disable_tagging'] : $group->g_bitoptions['gbw_disable_tagging'];
		$values['gbw_disable_prefixes'] = ( Settings::i()->tags_enabled AND Settings::i()->tags_can_prefix ) ? !$values['gbw_disable_prefixes'] : $group->g_bitoptions['gbw_disable_prefixes'];

		/* This is an inverse setting - the UI says "Show .." but the setting actually causes it to be hidden. */
		$values['gbw_hide_inline_modevents'] = !$values['gbw_hide_inline_modevents'];

		switch( $values['gbw_posted_in'] )
		{
			case 'primary':
				$values['gbw_posted_in'] = 1;
				$values['gbw_posted_in_secondary'] = 0;
				break;
			case 'both':
				$values['gbw_posted_in'] = 1;
				$values['gbw_posted_in_secondary'] = 1;
				break;
			case 'disabled':
				$values['gbw_posted_in'] = 0;
				$values['gbw_posted_in_secondary'] = 0;
				break;
		}

		$bwKeys = array( 'gbw_disable_tagging', 'gbw_disable_prefixes', 'gbw_delete_attachments', 'gbw_post_highlight', 'gbw_promote', 'gbw_immune_auto_mod', 'gbw_hide_inline_modevents', 'gbw_posted_in', 'gbw_posted_in_secondary' );

		foreach ( $bwKeys as $k )
		{
			if ( isset( $values[ $k ] ) )
			{
				$group->g_bitoptions[ $k ] = $values[ $k ];
			}
		}
		
		/* Other */
		if ( !$values['g_attach'] )
		{
			$values['g_attach_max'] = 0;
		}
		if ( !isset( $values['g_attach_max'] ) )
		{
			$values['g_attach_max'] = -1;
		}

		foreach ( array( 'g_attach_max', 'g_attach_per_post', 'g_edit_cutoff', 'g_append_edit', 'g_avoid_flood', 'g_avoid_q', 'g_mod_preview', 'g_bypass_badwords' ) as $k )
		{
			if ( isset( $values[ $k ] ) )
			{
				$group->$k = $values[ $k ];
			}
		}
		foreach ( array( 'g_edit_posts', 'g_hide_own_posts', 'g_delete_own_posts', 'g_lock_unlock_own', 'g_can_report', 'g_editor_restriction', 'g_editor_comments_restriction' ) as $k )
		{
			if ( in_array( $k, ['g_editor_restriction', 'g_editor_comments_restriction'] ) and array_key_exists( $k, $values ) and $values[$k] === 'default' )
			{
				$group->$k = null;
			}
			else if ( isset( $values[ $k ] ) )
			{
				if ( is_array( $values[ $k ] ) and count( $values[ $k ] ) )
				{
					$group->$k = implode( ',', $values[ $k ] );
				}
				else
				{
					$group->$k = intval( $values[ $k ] );
				}
			}
		}
	}
}