<?php
/**
 * @brief		Warnings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Apr 2013
 */

namespace IPS\core\modules\admin\moderation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use UnderflowException;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Warnings
 */
class warnings extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\core\Warnings\Reason';

	/**
	 * Show the "add" button in the page root rather than the table root
	 */
	protected bool $_addButtonInRoot = FALSE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'warn_settings' );
		parent::execute();
	}
		
	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{		
		/* Init */
		$activeTab = Request::i()->tab ?: NULL;
		$activeTabContents = '';
		$tabs = array();
				
		/* Reasons */
		if ( Settings::i()->warn_on and Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'reasons_view' ) )
		{
			$tabs['reasons'] = 'warn_reasons';
			if ( $activeTab == 'reasons' )
			{
				parent::manage();
				$activeTabContents = Output::i()->output;
			}
		}
		
		/* Actions */
		if ( Settings::i()->warn_on and Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'actions_view' ) )
		{
			$tabs['actions'] = 'warn_actions';
			if ( $activeTab == 'actions' )
			{
				/* Init */
				$table = new \IPS\Helpers\Table\Db( 'core_members_warn_actions', Url::internal( 'app=core&module=moderation&controller=warnings' ) );
				$table->include = array( 'wa_points', 'wa_mq', 'wa_rpa', 'wa_suspend' );
				$table->sortBy        = $table->sortBy        ?: 'wa_points';
				$table->sortDirection = $table->sortDirection ?: 'asc';
				
				/* Row buttons */
				$table->rowButtons = function( $row )
				{
					$return = array();
					
					if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'actions_edit' ) )
					{
						$return['edit'] = array(
							'icon'	=> 'pencil',
							'link'	=> Url::internal( 'app=core&module=moderation&controller=warnings&do=actionForm&id=' ) . $row['wa_id'],
							'title'	=> 'edit',
							'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
						);
					}
					
					if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'actions_delete' ) )
					{
						$return['delete'] = array(
							'icon'	=> 'times-circle',
							'link'	=> Url::internal( 'app=core&module=moderation&controller=warnings&do=actionDelete&id=' ) . $row['wa_id'],
							'title'	=> 'delete',
							'data'	=> array( 'delete' => '' )
						);
					}
					
					return $return;
				};
				
				/* Parsers */
				$table->parsers = array(
					'wa_mq'	=> function( $val, $row )
					{
						return $val ? ( ( $val == -1 ) ? Member::loggedIn()->language()->addToStack('indefinitely') : ( Member::loggedIn()->language()->addToStack('for') . ' ' . $val . ' ' . ( $row['wa_mq_unit'] == 'd' ? Member::loggedIn()->language()->addToStack('days') : Member::loggedIn()->language()->addToStack('hours') ) ) ) : '-';
					},
					'wa_rpa'	=> function( $val, $row )
					{
						return $val ? ( ( $val == -1 ) ? Member::loggedIn()->language()->addToStack('indefinitely') : ( Member::loggedIn()->language()->addToStack('for') . ' ' . $val . ' ' . ( $row['wa_rpa_unit'] == 'd' ? Member::loggedIn()->language()->addToStack('days') : Member::loggedIn()->language()->addToStack('hours') ) ) ) : '-';
					},
					'wa_suspend'	=> function( $val, $row )
					{
						return $val ? ( ( $val == -1 ) ? Member::loggedIn()->language()->addToStack('indefinitely') : ( Member::loggedIn()->language()->addToStack('for') . ' ' . $val . ' ' . ( $row['wa_suspend_unit'] == 'd' ? Member::loggedIn()->language()->addToStack('days') : Member::loggedIn()->language()->addToStack('hours') ) ) ) : '-';
					}
				);
				
				/* Add button */
				if ( Member::loggedIn()->hasAcpRestriction( 'core', 'moderation', 'actions_add' ) )
				{
					$table->rootButtons = array(
						'add'	=> array(
							'icon'	=> 'plus',
							'link'	=> Url::internal( 'app=core&module=moderation&controller=warnings&do=actionForm' ),
							'title'	=> 'add',
							'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
						)
					);
				}
				
				/* Display */
				$activeTabContents = (string) $table;
			}
		}

		/* Settings */
		$tabs['settings'] = 'settings';
		if ( $activeTab == 'settings' )
		{
			$form = new Form;
		
			$form->add( new YesNo( 'warn_on', Settings::i()->warn_on, FALSE, array( 'togglesOn' => array(
				'warn_protected',
				'warn_show_own',
				'warnings_acknowledge'
			) ) ) );
			$form->add( new CheckboxSet( 'warn_protected', explode( ',', Settings::i()->warn_protected ), FALSE, array( 'options' => Group::groups( TRUE, FALSE ), 'parse' => 'normal', 'multiple' => TRUE ), NULL, NULL, NULL, 'warn_protected' ) );
			$form->add( new YesNo( 'warn_show_own', Settings::i()->warn_show_own, FALSE, array(), NULL, NULL, NULL, 'warn_show_own' ) );
			$form->add( new YesNo( 'warnings_acknowledge', Settings::i()->warnings_acknowledge, FALSE, array(), NULL, NULL, NULL, 'warnings_acknowledge' ) );
			
			if ( $values = $form->values() )
			{
				$form->saveAsSettings();
				Session::i()->log( 'acplog__warn_settings' );
				Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=warnings&tab=settings' ), 'saved' );
			}
			
			$activeTabContents = (string) $form;
		}		
				
		/* Add the blurb in */
		if ( $activeTab != 'settings' )
		{
			$activeTabContents = Theme::i()->getTemplate( 'forms' )->blurb( 'warn_' . $activeTab . '_blurb', TRUE, TRUE ) . $activeTabContents;
		}
				
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('warnings');
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'moderation_warnings_blurb' );
			Output::i()->output .= Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $activeTabContents, Url::internal( "app=core&module=moderation&controller=warnings" ) );
		}
	}
	
	/**
	 * Warn Action Form
	 *
	 * @return	void
	 */
	protected function actionForm() : void
	{
		$current = NULL;
		if ( Request::i()->id )
		{
			$current = Db::i()->select( '*', 'core_members_warn_actions', array( 'wa_id=?', Request::i()->id ) )->first();
			Dispatcher::i()->checkAcpPermission( 'actions_edit' );
		}
		
		if ( !$current )
		{
			Dispatcher::i()->checkAcpPermission( 'actions_add' );
		}
	
		$form = new Form;
		
		$form->add( new Number( 'wa_points', ( $current ? $current['wa_points'] : 0 ), TRUE ) );
		foreach ( array( 'mq', 'rpa', 'suspend' ) as $k )
		{
			$form->add( new Custom( 'wa_' . $k, ( $current ? array( $current[ 'wa_' . $k ], $current[ 'wa_' . $k . '_unit' ] ) : array( NULL, NULL ) ), FALSE, array(
				'getHtml'	=> function( $element )
				{
					return Theme::i()->getTemplate( 'members' )->warningTime( $element->name, $element->value, 'for', 'indefinitely' );
				},
				'formatValue'=> function( $element )
				{
					if ( isset( $element->value[3] ) )
					{
						$element->value[0] = -1;
						$element->value[1] = 'h';
						unset( $element->value[3] );
					}
					return $element->value;
				}
			) ) );
		}
		$form->add( new YesNo( 'wa_override', ( $current ? $current['wa_override'] : FALSE ) ) );
		
		if ( $values = $form->values() )
		{
			$save = array( 'wa_points' => $values['wa_points'], 'wa_override' => $values['wa_override'] );
			foreach ( array( 'mq', 'rpa', 'suspend' ) as $k )
			{
				$save[ 'wa_' . $k ] = intval( $values[ 'wa_' . $k ][0] );
				$save[ 'wa_' . $k . '_unit' ] = $values[ 'wa_' . $k ][1];
			}
						
			if ( $current )
			{
				Db::i()->update( 'core_members_warn_actions', $save, array( 'wa_id=?', $current['wa_id'] ) );
				Session::i()->log( 'acplog__wa_edited', array( $save['wa_points'] => FALSE ) );
			}
			else
			{
				Db::i()->insert( 'core_members_warn_actions', $save );
				Session::i()->log( 'acplog__wa_created', array( $save['wa_points'] => FALSE ) );
			}
			
			Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=warnings&tab=actions' ), 'saved' );
		}
		
		Output::i()->output .= Theme::i()->getTemplate( 'global' )->block( 'warn_action', $form, FALSE );
	}
	
	/**
	 * Delete Warn Action
	 *
	 * @return	void
	 */
	protected function actionDelete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'actions_delete' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$current = Db::i()->select( '*', 'core_members_warn_actions', array( 'wa_id=?', Request::i()->id ) )->first();
			
			Session::i()->log( 'acplog__wa_deleted', array( $current['wa_points'] => FALSE ) );
			Db::i()->delete( 'core_members_warn_actions', array( 'wa_id=?', Request::i()->id ) );
		}
		catch ( UnderflowException $e ) { }
				
		Output::i()->redirect( Url::internal( 'app=core&module=moderation&controller=warnings&tab=actions' ), 'saved' );
	}
}