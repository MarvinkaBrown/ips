<?php
/**
 * @brief		Admin CP Group Form
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		19 Nov 2013
 */

namespace IPS\downloads\extensions\core\GroupForm;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Extensions\GroupFormAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Member\Group;
use IPS\Settings;
use function defined;
use const IPS\CIC2;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Group Form
 */
class Downloads extends GroupFormAbstract
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
		$restrictions = $group->idm_restrictions ? json_decode( $group->idm_restrictions, TRUE ) : array();

		$form->addHeader( 'file_management' );
		$form->add( new YesNo( 'idm_view_downloads', $group->idm_view_downloads ) );
		$form->add( new YesNo( 'idm_view_approvers', $group->idm_view_approvers ) );
		if( $group->g_id != Settings::i()->guest_group )
		{
			$form->add( new YesNo( 'idm_bypass_revision', $group->idm_bypass_revision ) );
		}
				
		$form->addHeader( 'submission_permissions' );
		if ( Application::appIsEnabled( 'nexus' ) and $group->g_id != Settings::i()->guest_group )
		{
			if ( Settings::i()->idm_nexus_on )
			{
				$form->add( new YesNo( 'idm_add_paid', $group->idm_add_paid ) );
			}
			else
			{
				Member::loggedIn()->language()->words['idm_add_paid_desc'] = Member::loggedIn()->language()->addToStack( 'idm_add_paid_enable', FALSE );
				$form->add( new YesNo( 'idm_add_paid', FALSE, FALSE, array( 'disabled' => TRUE ) ) );
			}
		}
		$form->add( new YesNo( 'idm_bulk_submit', $group->idm_bulk_submit ) );
		$form->add( new YesNo( 'idm_linked_files', $group->idm_linked_files ) );
		$form->add( new YesNo( 'idm_import_files', $group->idm_import_files ) );

		$form->add( new Number( 'idm_max_size', $group->idm_max_size, FALSE, array( 'unlimited' => -1 ), NULL, NULL, Member::loggedIn()->language()->addToStack('idm_maxsize_suffix') ) );
		
		$form->addHeader( 'download_restrictions' );
		if ( Application::appIsEnabled( 'nexus' ) AND Settings::i()->idm_nexus_on )
		{
			$form->add( new YesNo( 'idm_paid_restrictions', $group->idm_paid_restrictions ) );
		}
		if( $group->g_id != Settings::i()->guest_group )
		{
			$form->add( new Number( 'min_posts', $restrictions['min_posts'] ?? 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'idm_throttling_unlimited' ), NULL, NULL, Member::loggedIn()->language()->addToStack('approved_posts_comments') ) );
		}
		if ( !CIC2 )
		{
			$form->add( new Number( 'idm_throttling', $group->idm_throttling, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'idm_throttling_unlimited' ), NULL, NULL, Member::loggedIn()->language()->addToStack('idm_throttling_suffix') ) );
		}

		$form->add( new Interval( 'idm_wait_period', $group->g_id ? $group->idm_wait_period : 0, FALSE, array( 'valueAs' => Interval::SECONDS, 'unlimited' => 0, 'unlimitedLang' => 'idm_throttling_unlimited' ) ) );
		$form->add( new Number( 'limit_sim', $restrictions['limit_sim'] ?? 0, FALSE, array( 'unlimited' => 0 ) ) );
		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on )
		{
			$form->add( new YesNo( 'idm_bypass_paid', $group->idm_bypass_paid ) );
		}
		
		$form->addHeader( 'bandwidth_limits' );
		$form->addMessage( 'downloads_requires_log' );
		$form->add( new Number( 'daily_bw', $restrictions['daily_bw'] ?? 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('kb_per_day') ) );
		$form->add( new Number( 'weekly_bw', $restrictions['weekly_bw'] ?? 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('kb_per_week') ) );
		$form->add( new Number( 'monthly_bw', $restrictions['monthly_bw'] ?? 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('kb_per_month') ) );
		$form->addHeader( 'download_limits' );
		$form->addMessage( 'downloads_requires_log' );
		$form->add( new Number( 'daily_dl', $restrictions['daily_dl'] ?? 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('downloads_per_day') ) );
		$form->add( new Number( 'weekly_dl', $restrictions['weekly_dl'] ?? 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('downloads_per_week') ) );
		$form->add( new Number( 'monthly_dl', $restrictions['monthly_dl'] ?? 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, Member::loggedIn()->language()->addToStack('downloads_per_month') ) );
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
		$group->idm_view_approvers = $values['idm_view_approvers'];
		if( $group->g_id != Settings::i()->guest_group )
		{
			$group->idm_bypass_revision = $values['idm_bypass_revision'];
		}
		$group->idm_view_downloads = $values['idm_view_downloads'];
		if ( !CIC2 )
		{
			$group->idm_throttling = $values['idm_throttling'];
		}
		$group->idm_wait_period = $values['idm_wait_period'];
		$group->idm_linked_files = $values['idm_linked_files'];
		$group->idm_import_files = $values['idm_import_files'];
		$group->idm_bulk_submit = $values['idm_bulk_submit'];
		$group->idm_max_size = $values['idm_max_size'];
		
		if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on )
		{
			if( $group->g_id != Settings::i()->guest_group )
			{
				$group->idm_add_paid = $values['idm_add_paid'];
			}
			$group->idm_bypass_paid = $values['idm_bypass_paid'];
			$group->idm_paid_restrictions = $values['idm_paid_restrictions'];
		}
		
		$restrictions = array();
		foreach ( array( 'limit_sim', 'daily_bw', 'weekly_bw', 'monthly_bw', 'daily_dl', 'weekly_dl', 'monthly_dl' ) as $k )
		{
			$restrictions[ $k ] = $values[ $k ];
		}
		
		if( $group->g_id != Settings::i()->guest_group )
		{
			$restrictions['min_posts'] = $values['min_posts'];
		}
		
		$group->idm_restrictions = json_encode( $restrictions );
	}
}