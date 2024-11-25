<?php
/**
 * @brief		Member Warnings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Jul 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Content\Controller;
use IPS\core\Warnings\Reason;
use IPS\core\Warnings\Warning;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Warnings
 */
class warnings extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\core\Warnings\Warning';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		parent::execute();
		
		if ( !Settings::i()->warn_on )
		{
			Output::i()->error( 'warning_system_disabled', '2C184/7', 403, '' );
		}
	}
	
	/**
	 * View List
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		/* Load the member */
		$member = Member::load( Request::i()->id );
		if ( !$member->member_id )
		{
			Output::i()->error( 'node_error', '2C135/A', 403, '' );
		}
		
		/* Check permission */
		if ( !( Settings::i()->warn_on AND ( Member::loggedIn()->modPermission('mod_see_warn') or ( Settings::i()->warn_show_own and Member::loggedIn()->member_id == $member->member_id ) ) ) )
		{
			Output::i()->error( 'no_module_permission', '2C135/9', 403, '' );
		}
		
		$table = new Content( 'IPS\core\Warnings\Warning', Url::internal( "app=core&module=system&controller=warnings&id={$member->member_id}", 'front', 'warn_list', $member->members_seo_name ), array( array( 'wl_member=?', $member->member_id ) ) );
		$table->rowsTemplate	  = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'warningRow' );

		Output::i()->title = Member::loggedIn()->language()->addToStack('members_warnings', FALSE, array( 'sprintf' => array( $member->name ) ) );
		Output::i()->breadcrumb[] = array( $member->url(), $member->name );
		
		if( !Request::i()->isAjax() )
		{
			Output::i()->output = Theme::i()->getTemplate( 'tables', 'core' )->container( (string) $table );
		}
		else
		{
			Output::i()->output = (string) $table;
		}
		return null;
	}
	
	/**
	 * View Warning
	 *
	 * @return	void
	 */
	protected function view() : void
	{		
		/* Load the member */
		$member = Member::load( Request::i()->id );
		if ( !$member->member_id )
		{
			Output::i()->error( 'node_error', '2C135/4', 403, '' );
		}
		
		/* Load it */
		try
		{
			$warning = Warning::loadAndCheckPerms( Request::i()->w );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C184/3', 404, '' );
		}
		
		/* Show it */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'view_warning_details' );
		Output::i()->breadcrumb[] = array( $member->url(), $member->name );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=system&controller=warnings&id={$member->member_id}", NULL, 'warn_list', $member->members_seo_name ), Member::loggedIn()->language()->addToStack('members_warnings', FALSE, array( 'sprintf' => array( $member->name ) ) ) );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'view_warning_details' ) );
		Output::i()->output = Theme::i()->getTemplate('modcp')->warnHovercard( $warning );
	}
		
	/**
	 * Warn
	 *
	 * @return	void
	 */
	protected function warn() : void
	{
		/* Load the member */
		$member = Member::load( Request::i()->id );
		if ( !$member->member_id )
		{
			Output::i()->error( 'node_error', '2C135/2', 403, '' );
		}
		
		/* Permission Check */
		if ( !Member::loggedIn()->canWarn( $member ) )
		{
			Output::i()->error( 'no_module_permission', '2C184/6', 403, '' );
		}

		/* Build the form */
		$form = Warning::create();
		$form->class = 'ipsForm--vertical ipsForm--warn';
		$form->attributes = array( 'data-controller' => 'core.front.modcp.warnForm', 'data-member' => $member->member_id );
		$form->hiddenValues['ref'] = Request::i()->ref;
		$form->hiddenValues['member'] = $member->member_id;
		
		/* Display */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_modcp.js', 'core' ) );
		$actions = Db::i()->select( '*', 'core_members_warn_actions', NULL, 'wa_points ASC' );
		if ( count( $actions ) )
		{
			$min = NULL;
			foreach ( $actions as $a )
			{
				if ( ( $a['wa_points'] - $member->warn_level ) > 1 )
				{
					$min = $a['wa_points'] - $member->warn_level;
				}
				break;
			}
			
			$form->addSidebar( Theme::i()->getTemplate( 'modcp' )->warnActions( $actions, $member, $min ) );
		}
		Output::i()->title = Member::loggedIn()->language()->addToStack('warn_member', FALSE, array( 'sprintf' => array( $member->name ) ) );
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Acknowledge Warning
	 *
	 * @return	void
	 */
	protected function acknowledge() : void
	{
		Session::i()->csrfCheck();
		
		/* Load the member */
		$member = Member::load( Request::i()->id );
		if ( !$member->member_id )
		{
			Output::i()->error( 'node_error', '2C184/4', 403, '' );
		}
		
		/* Load it */
		try
		{
			$warning = Warning::loadAndCheckPerms( Request::i()->w );
			
			if ( $warning->member !== $member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C184/5', 404, '' );
		}
				
		/* Acknowledge it */
		$warning->acknowledged = TRUE;
		$warning->save();
		$member->members_bitoptions['unacknowledged_warnings'] = (bool) Db::i()->select( 'COUNT(*)', 'core_members_warn_logs', array( "wl_member=? AND wl_acknowledged=0", $member->member_id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$member->save();
		
		/* Redirect */
		if ( $redirectTo = Request::i()->referrer() )
		{
			Output::i()->redirect( $redirectTo );
		}
		else
		{
			Output::i()->redirect( $warning->url() );
		}
	}
	
	/**
	 * Revoke Warning
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		$class	= static::$contentModel;
		try
		{
			/* @var Warning $class */
			$item	= $class::loadAndCheckPerms( Request::i()->w );
			$member	= Member::load( $item->member );
			
			if ( $item->canDelete() )
			{
				if ( isset( Request::i()->undo ) )
				{
					Session::i()->csrfCheck();
					if ( Request::i()->undo )
					{
						$item->undo();
					}
					$item->delete();
					Output::i()->redirect( $member->url(), 'warn_revoked' );
				}
				else
				{
					Output::i()->output = Theme::i()->getTemplate('modcp')->warningRevoke( $item );
				}
			}
			else
			{
				Output::i()->error( 'generic_error', '2C184/1', 403, '' );
			}
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C184/2', 404, '' );
		}
	}
	
	/**
	 * Add Warning Form - AJAX response to reason select
	 *
	 * @return	void
	 */
	protected function reasonAjax() : void
	{
		/* Check permission */
		if ( ! ( Settings::i()->warn_on AND Member::loggedIn()->modPermission('mod_see_warn') ) )
		{
			Output::i()->error( 'no_module_permission', '2C135/9', 403, '' );
		}
		
		$remove = array(
			'date'		=> NULL,
			'time'		=> NULL,
			'unlimited'	=> TRUE,
		);
		
		if ( Request::i()->id == 'other' )
		{
			Output::i()->json( array(
				'points'			=> 0,
				'points_override'	=> TRUE,
				'remove'			=> $remove,
				'remove_override'	=> TRUE,
				'notes'				=> NULL,
				'cheev_point_reduction' => 0,
				'cheev_override' => TRUE
			)	);
		}
		
		try
		{	
			$reason = Reason::load( Request::i()->id );
			
			/* Add in the remove properties */
			if ( $reason->remove AND $reason->remove != -1 )
			{
				$date = DateTime::create();
				if ( $reason->remove_unit == 'h' )
				{
					$date->add( new DateInterval( "PT{$reason->remove}H" ) );
				}
				else
				{
					$date->add( new DateInterval( "P{$reason->remove}D" ) );
				}
				
				$remove = array(
					'date'		=> $date->format( 'Y-m-d' ),
					'time'		=> $date->format( 'H:i' ),
					'unlimited'	=> FALSE
				);
			}
						
			Output::i()->json( array(
				'points'			=> $reason->points,
				'points_override'	=> $reason->points_override,
				'remove'			=> $remove,
				'remove_override'	=> $reason->remove_override,
				'notes'				=> $reason->notes,
				'cheev_point_reduction' => $reason->cheev_point_reduction,
				'cheev_override' => $reason->cheev_override
			)	);
		}
		
		catch ( OutOfRangeException $e )
		{
			Output::i()->json( array(
				'points'			=> 0,
				'points_override'	=> FALSE,
				'remove'			=> $remove,
				'remove_override'	=> FALSE,
				'notes'				=> NULL,
				'cheev_point_reduction' => 0,
				'cheev_override' => FALSE
			)	);
		}
	}
	
	/**
	 * Add Warning Form - AJAX response to points change
	 *
	 * @return	void
	 */
	protected function actionAjax() : void
	{
		$actions = array(
			'mq'	=> array(
				'date'		=> NULL,
				'time'		=> NULL,
				'unlimited'	=> FALSE,
			),
			'rpa'	=> array(
				'date'		=> NULL,
				'time'		=> NULL,
				'unlimited'	=> FALSE,
			),
			'suspend'	=> array(
				'date'		=> NULL,
				'time'		=> NULL,
				'unlimited'	=> FALSE,
			),
		);
		
		$member = Member::load( Request::i()->member );
		
		/* Check permission */
		if ( !( Settings::i()->warn_on AND ( Member::loggedIn()->modPermission('mod_see_warn') or ( Settings::i()->warn_show_own and Member::loggedIn()->member_id == $member->member_id ) ) ) )
		{
			Output::i()->error( 'no_module_permission', '2C135/9', 403, '' );
		}
		
		try
		{
			$action = Db::i()->select( '*', 'core_members_warn_actions', array( 'wa_points<=?', ( $member->warn_level + (int) Request::i()->points ) ), 'wa_points DESC', 1 )->first();
			foreach ( array( 'mq', 'rpa', 'suspend' ) as $k )
			{
				if ( $action[ 'wa_' . $k ] == -1 )
				{
					$actions[ $k ]['unlimited'] = TRUE;
				}
				elseif ( $action[ 'wa_' . $k ] )
				{
					$date = DateTime::ts( time() )->add( new DateInterval( $action[ 'wa_' . $k . '_unit' ] == 'h' ? "PT{$action[ 'wa_' . $k ]}H" : "P{$action[ 'wa_' . $k ]}D" ) );
					
					$actions[ $k ]['date'] = $date->format( 'Y-m-d' );
					$actions[ $k ]['time'] = $date->format( 'H:i' );
				}
			}
		}
		catch ( UnderflowException $e ) { }
		
		Output::i()->json( array(
			'actions'	=> $actions,
			'override'	=> isset( $action ) ? $action['wa_override'] : Member::loggedIn()->modPermission('warning_custom_noaction')
		)	);
	}
}